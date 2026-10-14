<?php

declare(strict_types=1);

namespace Orchestra\Core\Authorization;

use InvalidArgumentException;
use LogicException;

/**
 * Collects the capabilities defined by the Orchestra plugins.
 *
 * Every plugin registers the capabilities it owns while the `orchestra_core_register_capabilities`
 * action runs. Afterwards the registry is sealed and synchronized onto the WordPress roles.
 *
 * The administrator role implicitly receives every registered capability.
 */
final class CapabilityRegistry
{
    private const NAME_PATTERN = '/^orchestra_[a-z0-9]+(?:_[a-z0-9]+)+$/';

    /** @var array<string, list<string>> capability => roles */
    private array $grants = [];

    /** @var array<string, callable(int, array<mixed>): list<string>> meta capability => mapper */
    private array $metaCapabilities = [];

    private bool $sealed = false;

    /**
     * Registers a primitive capability and grants it to the given roles.
     *
     * A capability registered without roles is available to administrators only.
     *
     * @param list<string> $roles Roles from {@see Roles::GRANTABLE}
     */
    public function register(string $capability, array $roles = []): self
    {
        $this->assertNotSealed();
        $this->assertValidName($capability);
        $this->assertUnused($capability);

        foreach ($roles as $role) {
            if (!\in_array($role, Roles::GRANTABLE, true)) {
                throw new InvalidArgumentException(\sprintf(
                    'Cannot grant "%s" to unknown role "%s". Grantable roles are: %s.',
                    $capability,
                    $role,
                    \implode(', ', Roles::GRANTABLE)
                ));
            }
        }

        $this->grants[$capability] = \array_values(\array_unique($roles));

        return $this;
    }

    /**
     * Registers a meta capability, i.e. a capability that is checked against a specific object
     * (`current_user_can($metaCapability, $objectId)`) and mapped onto primitive capabilities.
     *
     * @param callable(int $userId, array<mixed> $args): list<string> $mapper
     *        Receives the checked user's ID and the extra arguments passed to `current_user_can()`
     *        and returns the primitive capabilities the user needs. Return `['do_not_allow']` to deny.
     */
    public function registerMetaCapability(string $metaCapability, callable $mapper): self
    {
        $this->assertNotSealed();
        $this->assertValidName($metaCapability);
        $this->assertUnused($metaCapability);

        $this->metaCapabilities[$metaCapability] = $mapper;

        return $this;
    }

    /**
     * Prevents further registrations. Capabilities registered later would never reach the roles.
     */
    public function seal(): void
    {
        $this->sealed = true;
    }

    /**
     * @return array<string, list<string>> role => sorted capabilities, for every grantable role and the administrator
     */
    public function capabilitiesByRole(): array
    {
        $byRole = \array_fill_keys([...Roles::GRANTABLE, Roles::ADMINISTRATOR], []);

        foreach ($this->grants as $capability => $roles) {
            $byRole[Roles::ADMINISTRATOR][] = $capability;
            foreach ($roles as $role) {
                $byRole[$role][] = $capability;
            }
        }

        foreach ($byRole as $role => $capabilities) {
            \sort($capabilities);
            $byRole[$role] = $capabilities;
        }

        return $byRole;
    }

    /**
     * @return (callable(int, array<mixed>): list<string>)|null
     */
    public function metaCapabilityMapper(string $metaCapability): ?callable
    {
        return $this->metaCapabilities[$metaCapability] ?? null;
    }

    /**
     * A hash of the role assignments. It changes whenever a capability or a grant is added or removed.
     */
    public function fingerprint(): string
    {
        return \hash('sha256', \json_encode($this->capabilitiesByRole(), JSON_THROW_ON_ERROR));
    }

    private function assertNotSealed(): void
    {
        if ($this->sealed) {
            throw new LogicException(
                'Capabilities must be registered during the "orchestra_core_register_capabilities" action.'
            );
        }
    }

    private function assertValidName(string $capability): void
    {
        if (\preg_match(self::NAME_PATTERN, $capability) !== 1) {
            throw new InvalidArgumentException(\sprintf(
                'Invalid capability name "%s". Expected the pattern orchestra_{domain}_{resource}_{action}.',
                $capability
            ));
        }
    }

    private function assertUnused(string $capability): void
    {
        if (isset($this->grants[$capability]) || isset($this->metaCapabilities[$capability])) {
            throw new LogicException(\sprintf(
                'Capability "%s" is already registered. Every capability must have exactly one owner.',
                $capability
            ));
        }
    }
}
