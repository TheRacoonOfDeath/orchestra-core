# Development Guidelines

## General

- PHP 8.3 is the minimum version. Composer resolves dependencies against PHP 8.3
  (`config.platform.php`), so a newer local PHP cannot pull in incompatible packages.
- Every PHP file starts with `declare(strict_types=1);`.
- Follow PSR-12. It is enforced with PHP_CodeSniffer (`phpcs.xml.dist`).
- PHPStan runs at level 9 with WordPress stubs. Do not suppress errors; fix their cause.
- Variables, properties and methods use camelCase, even where WordPress uses snake_case.
- Prefer small final classes with constructor injection over static helpers, so they can be tested.

## Commands

| Command | Purpose |
|---------|---------|
| `composer lint` | Check coding standard |
| `composer fix` | Fix coding standard violations automatically |
| `composer analyse` | Run PHPStan |
| `composer test` | Run PHPUnit |
| `composer check` | All of the above; run before every commit |

Commit `composer.lock`. Releases are built from it (see [BUILD.md](../BUILD.md)).

## Tests

- Unit tests cover behaviour, not structure. Do not write tests that only check that a class or
  method exists or that repeat a configuration list.
- Code that calls WordPress functions is tested against small in-memory fakes
  (see `tests/Fakes`) or kept thin enough not to need tests (hook registration in `Plugin`).

## WordPress

- Use namespace prefixes.
- Never modify WordPress core.
- Prefer WordPress APIs.
- Check capabilities, never roles. For actions on a specific object, check a meta capability with
  the object ID (see [capabilities.md](capabilities.md)).
- Escape all output, verify nonces on every state-changing request.

## Namespaces

- Orchestra\Core
- Orchestra\Library
- Orchestra\Projects
- Orchestra\Members

## Plugins

Every plugin owns its data. Cross-plugin database access is forbidden; use public services instead.

### Depending on orchestra-core

- Declare the dependency in the plugin header: `Requires Plugins: orchestra-core`. WordPress then
  refuses to activate the plugin without core.
- Do **not** add `orchestra/core` as a runtime Composer dependency (`require`). Core's classes are
  loaded by the orchestra-core plugin itself; bundling a second copy would load conflicting versions.
  Add it to `require-dev` instead, from the GitHub repository, so PHPStan and tests know its
  classes. Release builds use `--no-dev` and do not contain it:

  ```json
  "repositories": [{"type": "vcs", "url": "https://github.com/TheRacoonOfDeath/orchestra-core"}],
  "require-dev": {"orchestra/core": "^0.2"}
  ```
- Only use core classes inside hooks (`plugins_loaded` or later), never while the plugin file is
  being loaded, because WordPress does not guarantee the plugin load order.
- Register capabilities in the `orchestra_core_register_capabilities` action.
- Each plugin provides an `uninstall.php` that removes its data. Deactivation never deletes data.
