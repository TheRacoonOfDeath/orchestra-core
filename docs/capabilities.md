# Capabilities Management

## Overview

Orchestra implements a centralized capability system in `orchestra-core` that all plugins reference. Capabilities are organized by domain and follow a consistent naming convention.

Every capability follows the pattern: `orchestra_{domain}_{resource}_{action}`

Examples:
- `orchestra_library_piece_create`
- `orchestra_projects_participation_manage_own`
- `orchestra_members_profile_edit_any`

---

## Capability Naming Conventions

### Action Types

- `read` - View or access a resource
- `create` - Create a new resource
- `edit` - Modify a resource
- `delete` - Remove a resource
- `manage` - Combine multiple actions (typically for complex operations)
- `send` - Perform an action that affects others (e.g., send email)

### Ownership Qualifiers

Some capabilities have `_own` and `_any` variants:

- `_own` - User can only perform action on resources they own
- `_any` - User can perform action on any resource (admin/manager level)

Example:
```php
Capabilities::LIBRARY_PIECE_EDIT_OWN   // Edit only pieces you created
Capabilities::LIBRARY_PIECE_EDIT_ANY   // Edit any piece in the library
```

---

## Domains & Capabilities

### Members Domain

Manages member profiles and orchestra-specific user information.

| Capability | Description | Who Has It |
|-----------|-------------|-----------|
| `orchestra_members_profile_read` | View member profiles | Organizer, Conductor, Member, Admin |
| `orchestra_members_profile_edit_own` | Edit your own profile | Member, Admin |
| `orchestra_members_profile_edit_any` | Edit any member profile | Admin |

### Library Domain

Manages pieces, parts, and sheet music.

#### Pieces

| Capability | Description | Who Has It |
|-----------|-------------|-----------|
| `orchestra_library_piece_read` | View pieces | Organizer, Conductor, Member, Admin |
| `orchestra_library_piece_create` | Create new pieces | Conductor, Admin |
| `orchestra_library_piece_edit_own` | Edit your own pieces | Conductor, Admin |
| `orchestra_library_piece_edit_any` | Edit any piece | Conductor, Admin |
| `orchestra_library_piece_delete_own` | Delete your own pieces | Conductor, Admin |
| `orchestra_library_piece_delete_any` | Delete any piece | Admin |

#### Parts

| Capability | Description | Who Has It |
|-----------|-------------|-----------|
| `orchestra_library_part_read` | View parts | Organizer, Conductor, Member, Admin |
| `orchestra_library_part_create` | Create new parts | Conductor, Admin |
| `orchestra_library_part_edit_own` | Edit your own parts | Conductor, Admin |
| `orchestra_library_part_edit_any` | Edit any part | Conductor, Admin |
| `orchestra_library_part_delete_own` | Delete your own parts | Conductor, Admin |
| `orchestra_library_part_delete_any` | Delete any part | Admin |

#### Sheet Music

| Capability | Description | Who Has It |
|-----------|-------------|-----------|
| `orchestra_library_sheet_music_read` | Download/view sheet music | Organizer, Conductor, Member, Admin |
| `orchestra_library_sheet_music_upload` | Upload sheet music | Conductor, Admin |
| `orchestra_library_sheet_music_delete_own` | Delete your own uploads | Conductor, Admin |
| `orchestra_library_sheet_music_delete_any` | Delete any sheet music | Admin |

### Projects Domain

Manages projects, participation, assignments, and communications.

| Capability | Description | Who Has It |
|-----------|-------------|-----------|
| `orchestra_projects_project_read` | View projects | Organizer, Conductor, Member, Admin |
| `orchestra_projects_project_create` | Create new projects | Organizer, Admin |
| `orchestra_projects_project_edit` | Edit projects | Organizer, Admin |
| `orchestra_projects_project_delete` | Delete projects | Admin |
| `orchestra_projects_participation_read` | View participation records | Organizer, Conductor, Admin |
| `orchestra_projects_participation_manage_own` | Accept/decline your participation | Member, Admin |
| `orchestra_projects_participation_manage_any` | Manage any participation record | Organizer, Admin |
| `orchestra_projects_assignment_read` | View assignments | Organizer, Conductor, Admin |
| `orchestra_projects_assignment_create` | Create new assignments | Conductor, Admin |
| `orchestra_projects_assignment_edit` | Edit assignments | Conductor, Admin |
| `orchestra_projects_assignment_delete` | Delete assignments | Conductor, Admin |
| `orchestra_projects_email_send` | Send communications | Organizer, Conductor, Admin |
| `orchestra_projects_conductor_manage` | Assign/remove conductors from projects | Organizer, Admin |

---

## Role-to-Capability Mapping

### Administrator

Has **all capabilities**. Created via `Capabilities::administratorCapabilities()`.

### Organizer

Project managers who coordinate overall projects and participation.

**Read Access:**
- All library content (pieces, parts, sheet music)
- Member profiles
- All projects and participation records

**Write Access:**
- Create and edit projects
- Manage any participation record (unlock decisions, etc.)
- Assign/remove conductors
- Send communications

**Blocked:**
- Cannot create/edit library content (conductor responsibility)
- Cannot upload sheet music
- Cannot create assignments

### Conductor

Musical directors who manage library content and assignments.

**Read Access:**
- All library content (pieces, parts, sheet music)
- All projects and participation records
- All assignments

**Write Access:**
- Create and edit pieces/parts
- Upload sheet music
- Create and manage assignments
- Send communications

**Blocked:**
- Cannot create/edit projects
- Cannot modify participation decisions
- Cannot delete pieces

### Member

Orchestra musicians who participate in projects.

**Read Access:**
- Library pieces, parts, and sheet music
- Project information
- Own participation status
- Assignments

**Write Access:**
- Accept/decline own participation
- Edit own profile

**Blocked:**
- Cannot create projects
- Cannot create/edit library content
- Cannot create assignments
- Cannot send communications

---

## Usage Examples

### Check Capabilities in Code

```php
use Orchestra\Core\Capabilities;

// Check if current user has a capability
if (current_user_can(Capabilities::LIBRARY_PIECE_CREATE)) {
    // Show "Create Piece" button
}

// Check if a capability requires ownership verification
if (Capabilities::requiresOwnershipCheck($capability)) {
    // Verify user owns the resource before allowing action
}
```

### Get Capabilities for a Role

```php
use Orchestra\Core\CapabilitiesManager;

// Get all capabilities for a specific role
$organizer_caps = CapabilitiesManager::forRole('organizer');

// Or use role-specific methods
$conductor_caps = CapabilitiesManager::conductorCapabilities();
$member_caps = CapabilitiesManager::memberCapabilities();
$admin_caps = CapabilitiesManager::administratorCapabilities();
```

### Assign Capabilities to Roles

In your plugin's activation/initialization code:

```php
use Orchestra\Core\CapabilitiesManager;

// Simple way: assign all capabilities for a role
CapabilitiesManager::assignToRole('conductor', 'conductor');

// This is equivalent to:
$conductor_role = get_role('conductor');
if ($conductor_role) {
    foreach (CapabilitiesManager::conductorCapabilities() as $cap) {
        $conductor_role->add_cap($cap);
    }
}
```

### Sync Capabilities (Update Existing Role)

When capabilities change, sync the role to remove old caps and add new ones:

```php
use Orchestra\Core\CapabilitiesManager;

// Remove old capabilities and assign new ones
CapabilitiesManager::syncRole('conductor', 'conductor');
```

### Check User Capability with Ownership Verification

```php
use Orchestra\Core\CapabilitiesManager;

$user_id = get_current_user_id();
$piece_id = 123;

// This returns true only if:
// 1. User has the capability
// 2. For '_own' capabilities, user owns the resource
if (CapabilitiesManager::userCan($user_id, Capabilities::LIBRARY_PIECE_EDIT_OWN, $piece_id)) {
    // Allow edit
}
```

---

## Core Classes

### Capabilities

Defines all capability constants organized by domain.

**Responsibilities:**
- Provides constants for all capabilities
- Groups capabilities by domain (members, library, projects)
- Helper methods for ownership checking (`requiresOwnershipCheck()`)
- Conversion methods between `_own` and `_any` capabilities

**Key Methods:**
- `all()` - Get all capabilities grouped by domain
- `requiresOwnershipCheck($capability)` - Check if capability requires ownership verification
- `getAnyCapability($own_capability)` - Convert `_own` to `_any` capability
- `getOwnCapability($any_capability)` - Convert `_any` to `_own` capability

### CapabilitiesManager

Manages capability assignment to roles and user capability checking.

**Responsibilities:**
- Get capabilities for specific roles
- Assign capabilities to WordPress roles
- Sync role capabilities (remove old, add new)
- Check user capabilities with ownership verification

**Key Methods:**
- `forRole($role_name)` - Get capabilities for a specific role
- `administratorCapabilities()` - Get admin capabilities
- `organizerCapabilities()` - Get organizer capabilities
- `conductorCapabilities()` - Get conductor capabilities
- `memberCapabilities()` - Get member capabilities
- `assignToRole($wordpress_role, $orchestra_role)` - Assign capabilities to a role
- `removeFromRole($wordpress_role)` - Remove all orchestra capabilities
- `syncRole($wordpress_role, $orchestra_role)` - Sync role (remove old, add new)
- `userCan($user_id, $capability, $post_id)` - Check user capability with ownership rules

---

## Architecture Decisions

### Separation of Concerns

**Capabilities.php** contains only constants and utility functions for capability inspection. It is dependency-free and can be used in any context.

**CapabilitiesManager.php** handles WordPress-specific operations (reading/writing roles, checking user capabilities) and depends on WordPress functions.

This separation makes testing easier and allows usage in different contexts.

### Ownership Checking

For `_own` capabilities, CapabilitiesManager provides a `userCan()` method that:
1. Checks if user has the WordPress capability
2. Verifies resource ownership (if capability ends in `_own`)
3. Returns `true` only if both conditions are met

Example:
```php
// This checks if user 42 owns post 123
CapabilitiesManager::userCan(42, Capabilities::LIBRARY_PIECE_EDIT_OWN, 123);
```

---

When extending Orchestra or creating new plugins:

1. **Define constants** in `Capabilities.php`
2. **Add to `all()` method** in the appropriate domain array
3. **Add to role methods** as needed (e.g., `organizerCapabilities()`)
4. **Document** in this file with description and role assignments
5. **Register in plugins** via `add_cap()` in activation hooks

---

## Deprecation & Changes

When modifying capabilities:
- Do **not** delete existing capability constants without coordinating with all plugins
- Provide migration path when capabilities change
- Update this documentation immediately
- Coordinate changes across all orchestra plugins

---

## Integration with Orchestra Plugins

### orchestra-library
Registers and uses capabilities:
- `LIBRARY_PIECE_*`
- `LIBRARY_PART_*`
- `LIBRARY_SHEET_MUSIC_*`

### orchestra-members
Registers and uses capabilities:
- `MEMBERS_PROFILE_*`

### orchestra-projects
Registers and uses capabilities:
- `PROJECTS_PROJECT_*`
- `PROJECTS_PARTICIPATION_*`
- `PROJECTS_ASSIGNMENT_*`
- `PROJECTS_EMAIL_*`
- `PROJECTS_CONDUCTOR_*`
