# Persistence

## Principles

- Every plugin stores and reads only its own data. Other plugins access it through the owning
  plugin's public services, never through its tables, post types or meta keys.
- Use WordPress storage where it fits (users, user meta, custom post types) and custom tables for
  relational records that need joins or are queried in bulk.
- All names are prefixed: post types `orchestra_*`, meta keys `orchestra_{plugin}_*`,
  tables `{$wpdb->prefix}orchestra_{plugin}_*`, options `orchestra_{plugin}_*`.
- Data that history depends on is archived, not deleted.

---

## Overview

| Data | Owner | Storage |
|------|-------|---------|
| Accounts, name, email, password | WordPress | `wp_users` |
| Member profile (dietary restrictions, financial support, active status) | orchestra-members | user meta |
| Pieces | orchestra-library | custom post type `orchestra_piece` |
| Parts | orchestra-library | table `orchestra_library_parts` |
| Sheet music and piece file metadata | orchestra-library | table `orchestra_library_files` |
| Sheet music and piece files | orchestra-library | private directory (see below) |
| Projects | orchestra-projects | custom post type `orchestra_project` |
| Project conductors and organizers | orchestra-projects | table `orchestra_projects_staff` |
| Program entries (with position) | orchestra-projects | table `orchestra_projects_program` |
| Participation | orchestra-projects | table `orchestra_projects_participation` |
| Assignments | orchestra-projects | table `orchestra_projects_assignments` |
| Assignment history | orchestra-projects | derived from assignments of past projects |
| Communication groups | orchestra-members, orchestra-projects | not stored, computed on demand |
| Email log (metadata only) | orchestra-core | table `orchestra_core_email_log` |

### Why custom post types for pieces and projects

They are content-like (title, description, status), benefit from WordPress features (admin UI,
revisions, search, capability mapping) and are few in number.

### Why custom tables for the rest

Parts, participation, program entries and assignments are relational records. Assignment history
queries ("who played Horn 2 in this piece last time?") need joins and indexes that post meta
cannot provide efficiently.

### Assignment history

History is not stored separately. It is the set of assignments of past projects, ordered by the
project's concert date. This avoids duplicated data, but requires that projects, pieces and parts
referenced by assignments are archived instead of deleted.

---

## Sheet Music Files

Sheet music must never be reachable through a direct URL, so it is **not** stored in the
WordPress media library (attachment URLs are public).

- Files are stored in a private directory.
  - Preferred: a directory outside the web root, configured with
    `define('ORCHESTRA_LIBRARY_STORAGE_DIR', '/path/outside/webroot');` in `wp-config.php`.
  - Fallback: a directory inside `wp-content/uploads` with a random, unguessable name, plus
    `.htaccess` (`Require all denied`) and an empty `index.php`. nginx ignores `.htaccess`, so on
    nginx hosts the directory must be denied in the server configuration or the constant used.
- A file belongs either to a part (at most one file per part; a new upload replaces it) or to the
  piece itself (any number of piece files).
- Files are stored under generated names (e.g. a UUID). The original file name is kept in the
  metadata table and used in the `Content-Disposition` header.
- Downloads go through a PHP endpoint that checks `orchestra_library_sheet_music_download` and a
  nonce, then streams the file with `Cache-Control: private, no-store`.
- The library offers a service that bundles several files into one zip download. orchestra-projects
  uses it for "download my sheet music" per project, passing the part IDs of the member's
  assignments.
- Uploads are validated by size and by detected MIME type (not by extension).

---

## Cross-Plugin References

Plugins reference other plugins' objects by ID only (e.g. an assignment stores a part ID).

Deleting a referenced object must not leave dangling references. The owning plugin asks before
deleting, through a filter that consumers hook into, so that the owner does not depend on its
consumers:

```php
// orchestra-library, before deleting a piece
$blockers = apply_filters('orchestra_library_piece_delete_blockers', [], $pieceId);
if ($blockers !== []) {
    // refuse and show $blockers, e.g. "Used in project Spring Concert 2027"
}

// orchestra-projects
add_filter('orchestra_library_piece_delete_blockers', function (array $blockers, int $pieceId): array {
    // add a message for every project whose program contains the piece
    return $blockers;
}, 10, 2);
```

---

## Personal Data

- When a member is set to inactive, orchestra-members deletes the sensitive profile meta
  (financial support, dietary restrictions).
- Every plugin that stores personal data registers a personal data exporter and eraser
  (`wp_privacy_personal_data_exporters`, `wp_privacy_personal_data_erasers`).
  Records needed for history (participation, assignments) are anonymized instead of deleted.
- The email log stores no message bodies or recipient addresses and is purged after its
  retention period.

---

## Schema Management

- Each plugin creates its tables with `dbDelta()` and stores a schema version in an option
  (`orchestra_{plugin}_schema_version`). On every request it compares that option with the code's
  version and migrates if needed. Activation hooks do not run on updates, so they are not enough.
- `uninstall.php` drops the plugin's tables, post types' posts, meta and options.
  Deactivation never deletes data.
