# Architecture

## Purpose

The Orchestra Manager is an internal web application built on
WordPress.

The goal is to support orchestra administration while reusing standard
WordPress functionality whenever possible.

---

## Architectural Principles

### WordPress First

Standard WordPress functionality shall be reused whenever possible.

Examples:

- Users
- Authentication
- Roles
- Permissions
- Media handling

Custom development is limited to orchestra-specific functionality.

---

### Domain Separation

The system is divided into independent business domains:

- Members
- Library
- Projects
- Seating (the seating plans of projects)

Each domain is implemented in its own plugin.

---

### Single Ownership

Every business object has one owner.

Example:

Piece
→ owned by orchestra-library

Project
→ owned by orchestra-projects

Member Profile Extension
→ owned by orchestra-members

## orchestra-core

Technical infrastructure.

Responsibilities:

- roles and the capability registry (see [capabilities.md](capabilities.md))
- the shared UI for the Orchestra pages (see below)
- PDF documents (`Orchestra\Core\Pdf\Pdf`, with dompdf): the plugins pass simple HTML with
  tables, core adds the page layout and sends the download. dompdf is bundled only here, so
  that the plugins do not load different versions of it.
- automatic updates from GitHub releases

Planned:

- logging

Contains no business logic. Core owns the *roles*, but each domain plugin owns its *capabilities*
and decides which roles receive them.

All other plugins depend on orchestra-core (`Requires Plugins: orchestra-core`).
Domain plugins do not depend on each other's internals; they communicate through public services,
actions and filters.

---

## orchestra-members

Responsibilities:

- member profile extensions
- dietary restrictions
- financial support information
- active status and blocking inactive members from logging in
- global communication groups

Uses WordPress users as source of truth.

---

## orchestra-library

Responsibilities:

- pieces
- parts
- sheet music and piece files

Responsible for secure file access.

---

## orchestra-projects

Responsibilities:

- projects
- participation
- assignments
- assignment history
- project-based communication groups (accepted and pending participants)

---

## orchestra-email

Email is a technical service without business rules, in its own plugin since orchestra-core 0.7.

The domain plugins register **recipient groups**, following the same pattern as capabilities:
orchestra-members the global groups (everyone, all members, all conductors, all organizers),
orchestra-projects the project groups. orchestra-email resolves them,
sends one email per recipient in the background (formatted text, optionally with attachments) and
keeps a log with metadata only.
The plugin is optional: the domain plugins register their groups only when it runs. See the
[orchestra-email documentation](https://github.com/TheRacoonOfDeath/orchestra-email/blob/main/docs/email.md).

---

## orchestra-seating

Responsibilities:

- the seating plan of each project: a grid of seats, who sits where, the piece whose parts are
  shown as voices, and whether organizers are seated

It has no shortcode of its own: it adds a "Seating" tab to the project page with the tab filters
of orchestra-projects 0.14 (`orchestra_projects_tabs`, `orchestra_projects_tab_content`), and
reads participation, program and assignments through `ProjectsService`. See the
[orchestra-seating documentation](https://github.com/TheRacoonOfDeath/orchestra-seating/blob/main/docs/seating.md).

---

## Orchestra Pages

Members, conductors and organizers use the site, never the dashboard: every function is available
through shortcodes on normal pages ([development guidelines](development-guidelines.md#frontend-first)).

| Shortcode | Plugin | Content |
|-----------|--------|---------|
| `[orchestra_portal]` | core | start page: a card per area; the login form for visitors |
| `[orchestra_menu]` | core | navigation between the areas |
| `[orchestra_login]` | core | login form with the messages of WordPress (e.g. inactive account) |
| `[orchestra_email]` | orchestra-email | write emails to groups, sent emails |
| `[orchestra_projects]` | orchestra-projects | projects; management for conductors and organizers |
| `[orchestra_library]` | orchestra-library | library and downloads; piece editor for conductors |
| `[orchestra_members]` | orchestra-members | member list; profiles and status for organizers |
| `[orchestra_profile]` | orchestra-members | the user's own profile and password |

Core provides the shared pieces in `Orchestra\Core\Ui`:

- `Assets`: the stylesheet and script, loaded on pages with Orchestra shortcodes and on Orchestra
  screens in wp-admin. The design inherits the theme's font and colors; the accent color follows
  the theme's primary color and can be changed with `--orchestra-accent`.
- `Html`, `Form`, `Redirect`, `Notices`: building blocks for views and handlers.
- `Pages`: finds the page that contains a shortcode, so pages link to each other without settings.
- `Menu` / `MenuItem`: the areas registered by the plugins.
- `AdminAccess`: sends users whose roles are all Orchestra roles (and subscriber) from wp-admin to
  the start page, hides the admin toolbar for them and sends them there after login. The filter
  `orchestra_core_restrict_admin` can change this.
- `PageSetup`: the "Create Orchestra pages" button for administrators.

