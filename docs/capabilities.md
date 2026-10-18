# Roles and Capabilities

## Overview

Access control is built on WordPress roles and capabilities.

Ownership is split:

- **orchestra-core** owns the *roles* and the *mechanism*: it creates the roles, collects
  capabilities from all plugins and writes them onto the roles.
- **Every domain plugin** owns its *capabilities*: it defines them and decides which roles receive them.

Core therefore contains no domain-specific permission rules. Adding a feature to a domain plugin
never requires a change to orchestra-core.

Code always checks **capabilities**, never roles:

```php
current_user_can('orchestra_library_piece_edit');            // primitive capability
current_user_can('orchestra_members_profile_read', $userId); // meta capability for one object
```

---

## Roles

| Role | Slug | Constant |
|------|------|----------|
| Member | `orchestra_member` | `Roles::MEMBER` |
| Conductor | `orchestra_conductor` | `Roles::CONDUCTOR` |
| Organizer | `orchestra_organizer` | `Roles::ORGANIZER` |
| Administrator | `administrator` (WordPress) | `Roles::ADMINISTRATOR` |

- The slugs are prefixed to avoid collisions with roles of other plugins.
- Orchestra roles only receive the WordPress `read` capability in addition to Orchestra capabilities.
- Members have their own role. The WordPress `subscriber` role gets no Orchestra capabilities,
  so self-registered or unrelated accounts never see orchestra data.
- The administrator automatically receives **every** registered capability.

Permissions are global, not per project: a conductor has the conductor capabilities for all
projects. This is intentional (the orchestra has several conductors and organizers).

---

## Registering Capabilities

Plugins register their capabilities during the `orchestra_core_register_capabilities` action,
which orchestra-core fires on `init`. Registration must be unconditional (not only in admin, not
only for certain users), because the registered set is the source of truth for the roles.

```php
use Orchestra\Core\Authorization\CapabilityRegistry;
use Orchestra\Core\Authorization\OwnershipMapper;
use Orchestra\Core\Authorization\Roles;

add_action('orchestra_core_register_capabilities', static function (CapabilityRegistry $capabilities): void {
    $capabilities
        ->register('orchestra_library_piece_read', [Roles::MEMBER, Roles::CONDUCTOR, Roles::ORGANIZER])
        ->register('orchestra_library_piece_edit', [Roles::CONDUCTOR])
        ->register('orchestra_library_piece_delete'); // administrators only
});
```

The registry rejects:

- names not matching `orchestra_{domain}_{resource}_{action}` (lowercase, underscores),
- unknown roles, including `administrator` (it is implicit),
- a capability registered twice: every capability has exactly one owning plugin,
- registrations after the action has run.

---

## Ownership: Meta Capabilities

Some actions depend on *which* object is affected, e.g. "edit **your own** profile".
These are modelled the WordPress way, as meta capabilities that are checked with an object ID and
mapped onto primitive capabilities.

### Custom post types

Resources stored as custom post types (see [persistence.md](persistence.md)) use WordPress's built-in
mapping. Register the post type with `map_meta_cap => true` and point its `capabilities` at
Orchestra capability names:

```php
register_post_type('orchestra_piece', [
    'map_meta_cap' => true,
    'capabilities' => [
        'read'               => 'orchestra_library_piece_read',
        'edit_posts'         => 'orchestra_library_piece_edit',
        'edit_others_posts'  => 'orchestra_library_piece_edit',
        'publish_posts'      => 'orchestra_library_piece_edit',
        'delete_posts'       => 'orchestra_library_piece_delete',
        'delete_others_posts' => 'orchestra_library_piece_delete',
        // ...
    ],
]);

current_user_can('edit_post', $pieceId);
```

### Other objects

For objects that are not posts (users, custom table rows), register a meta capability with an
`OwnershipMapper`. The owner needs the `_own` capability, everybody else the `_others` capability:

```php
$capabilities
    ->register('orchestra_members_profile_read_own', [Roles::MEMBER, Roles::CONDUCTOR, Roles::ORGANIZER])
    ->register('orchestra_members_profile_read_others', [Roles::ORGANIZER])
    ->registerMetaCapability(
        'orchestra_members_profile_read',
        OwnershipMapper::forUsers('orchestra_members_profile_read_own', 'orchestra_members_profile_read_others')
    );

current_user_can('orchestra_members_profile_read', $userId);
```

For other objects, pass a closure that returns the owner's user ID:

```php
new OwnershipMapper(
    'orchestra_projects_participation_respond_own',
    'orchestra_projects_participation_manage',
    static fn (int $participationId): ?int => $participations->find($participationId)?->memberId,
);
```

The check is **denied** if no object ID is passed or the owner cannot be determined. A forgotten
argument never grants access.

Any other logic can be registered as a meta capability, too: the mapper is a callable
`(int $userId, array $args): list<string>` returning the required primitive capabilities
(`['do_not_allow']` denies).

---

## Lifecycle

| Event | Effect |
|-------|--------|
| First request after activating orchestra-core | Roles are created, capabilities assigned. |
| A domain plugin is activated, updated or deactivated | On the next request, its capabilities are added or removed. |
| orchestra-core is deactivated | Nothing changes. Roles and user assignments survive. |
| orchestra-core is deleted | `uninstall.php` removes the Orchestra roles, every capability Orchestra granted and its options. |

Core stores a fingerprint of the registered capabilities and only rewrites the roles when it
changes. It also remembers which capabilities it granted, so it removes exactly those when they are
no longer registered and leaves all other capabilities untouched.

Consequence: if an administrator manually revokes an Orchestra capability with a role editor plugin,
that change lasts until the registered capabilities change the next time. Change the plugin's
registration instead.

---

## Capability Catalogue

The capabilities planned for each plugin. Each plugin implements its own section.
Capabilities marked *meta* are checked with an object ID.

### orchestra-members

| Capability | Member | Conductor | Organizer |
|-----------|:------:|:---------:|:---------:|
| `orchestra_members_directory_read` (names of all members) | ✓ | ✓ | ✓ |
| `orchestra_members_profile_read` *meta* → `_read_own` / `_read_others` | own | own | own + others |
| `orchestra_members_profile_edit` *meta* → `_edit_own` / `_edit_others` | own | own | own |
| `orchestra_members_status_edit` (active / inactive) | | | ✓ |
| `orchestra_members_member_create` (add users as members; only administrators choose another role) | | ✓ | ✓ |
| `orchestra_members_email_send` (email the global groups) | | ✓ | ✓ |
| `orchestra_members_fields_manage` (choose the profile fields) | | | |

Conductors and organizers add people with the role Member (`orchestra_members_member_create`).
Choosing another role requires the WordPress capability `promote_users` (administrators).

Profiles contain sensitive personal data (e.g. dietary restrictions, financial support), so only
organizers and administrators may read other members' profiles.

### orchestra-library

| Capability | Member | Conductor | Organizer |
|-----------|:------:|:---------:|:---------:|
| `orchestra_library_piece_read` (pieces and parts) | ✓ | ✓ | ✓ |
| `orchestra_library_piece_edit` (create, edit, archive pieces and their parts) | | ✓ | |
| `orchestra_library_piece_delete` | | | |
| `orchestra_library_sheet_music_download` | ✓ | ✓ | ✓ |
| `orchestra_library_sheet_music_manage` (upload, replace, delete part sheet music and piece files) | | ✓ | |

Parts are always edited as part of their piece and need no separate capabilities.
Deleting pieces is reserved for administrators; pieces are archived instead, because projects
and assignment history refer to them.

### orchestra-projects

| Capability | Member | Conductor | Organizer |
|-----------|:------:|:---------:|:---------:|
| `orchestra_projects_project_read` | ✓ | ✓ | ✓ |
| `orchestra_projects_project_manage` (create, edit, change status, assign conductors) | | | ✓ |
| `orchestra_projects_project_delete` | | | |
| `orchestra_projects_program_manage` | | ✓ | ✓ |
| `orchestra_projects_participation_read` *meta* → `_read_own` / `_read_others` | own | others | own + others |
| `orchestra_projects_participation_respond` *meta* → `_respond_own` / `orchestra_projects_participation_manage` | own | | own + others |
| `orchestra_projects_participation_manage` (change any decision) | | | ✓ |
| `orchestra_projects_participation_export` (accepted participants with their profiles as CSV) | | | ✓ |
| `orchestra_projects_assignment_read` | ✓ | ✓ | ✓ |
| `orchestra_projects_assignment_manage` (create, edit, delete) | | ✓ | ✓ |
| `orchestra_projects_assignment_history_read` | | ✓ | ✓ |
| `orchestra_projects_email_send` (email the project and assignment groups) | | ✓ | ✓ |

Administrators have every capability.

Organizers may also play in projects, so they have the member capabilities for their own
participation. Conductors do not play.

Which projects members can see and when they can respond depends on the project status. These
rules are business rules of orchestra-projects, checked in addition to the capabilities.

---

## Adding or Changing Capabilities

1. Register the capability in the owning plugin's `orchestra_core_register_capabilities` callback.
2. Update the catalogue above.
3. Release the plugin. Roles are updated automatically on the next request.

Renaming a capability is a removal plus an addition. Search all plugins for its uses first.
