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
- the check that the private file directories of the plugins cannot be downloaded
  (`Orchestra\Core\Files\PrivateDirectoryCheck`, see [persistence.md](persistence.md#sheet-music-files))
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

- the seating plan of each project: a grid of seats, who sits where, and whether organizers are
  seated; the voices of every piece of the program can be shown on it
- the master seating: the standing seating of all active members and organizers, which projects
  can take over

It adds a "Seating" tab to the project page with the tab filters of orchestra-projects 0.14
(`orchestra_projects_tabs`, `orchestra_projects_tab_content`), and reads participation, program
and assignments through `ProjectsService`. Its shortcode `[orchestra_seating]` shows the master
seating to conductors and organizers. See the
[orchestra-seating documentation](https://github.com/TheRacoonOfDeath/orchestra-seating/blob/main/docs/seating.md).

---

## Orchestra Pages

Members, conductors and organizers use the site, never the dashboard: every function is available
through shortcodes on normal pages ([development guidelines](development-guidelines.md#frontend-first)).

| Shortcode | Plugin | Content |
|-----------|--------|---------|
| `[orchestra_portal]` | core | start page: a card per area; the login form for visitors |
| `[orchestra_menu]` | core | navigation between the areas (without "Log out", which is in the site's menu and on the login page) |
| `[orchestra_login]` | core | login form with the messages of WordPress (e.g. inactive account) |
| `[orchestra_email]` | orchestra-email | write emails to groups, sent emails |
| `[orchestra_projects]` | orchestra-projects | projects; management for conductors and organizers |
| `[orchestra_library]` | orchestra-library | library and downloads; piece editor for conductors |
| `[orchestra_members]` | orchestra-members | member list; profiles and status for organizers |
| `[orchestra_profile]` | orchestra-members | the user's own profile and password |
| `[orchestra_seating]` | orchestra-seating | the master seating, for conductors and organizers |

The Orchestra pages are different for every logged-in user. They do not mark themselves as
uncacheable: page caches must skip logged-in users, which the common caching plugins do by default.
Check this for host-level caches (e.g. "cache logged-in users" options), or exclude the Orchestra
pages there. Visitors only see the login form, which may be cached.

Core provides the shared pieces in `Orchestra\Core\Ui`:

- `Assets`: the stylesheet and script, loaded on pages with Orchestra shortcodes and on Orchestra
  screens in wp-admin. The design inherits the theme's font and colors; the accent color follows
  the theme's primary color and can be changed with `--orchestra-accent`. On phones the Orchestra
  blocks bring their own space above and below (many themes have none there), and tab bars and the
  navigation wrap onto more lines instead of hiding tabs. Components of `orchestra-ui.css` for the
  plugins, since 0.15: `.orchestra-card--linked` (the title's `.orchestra-card__link` opens the
  page from anywhere on the card, the card's own buttons stay clickable), `.orchestra-ellipsis`
  (text cut with "…", give it a `title`), `.orchestra-footnote` (a note set apart at the end of a
  section) and compact tables (`Html::table(..., compact: true)`: on phones a row is a short block
  of lines instead of a label per cell; `.orchestra-compact-title`, `.orchestra-compact-meta`,
  `.orchestra-compact-line`, and `.orchestra-phone-only` / `.orchestra-desktop-only` for content
  shown at one width only).
- `Html`, `Form`, `Redirect`, `Notices`: building blocks for views and handlers. The behaviour of
  `assets/orchestra-ui.js` is chosen with `data-orchestra-*` attributes, listed at the top of the
  script (e.g. `data-orchestra-switch` and `data-orchestra-show-for` show the parts of a row that
  belong to the selected option, since 0.14). Everything also works without JavaScript. After a form
  returns to the same page, the script restores the scroll position (kept in `sessionStorage` for
  a minute) and floats that page's notices at the bottom of the screen; success notices disappear
  after a few seconds. wp-admin screens open at the top, where WordPress shows the notices.
- `Pages`: finds the page that contains a shortcode, so pages link to each other without settings.
- `Menu` / `MenuItem`: the areas registered by the plugins.
- `AdminAccess`: sends users whose roles are all Orchestra roles (and subscriber) from wp-admin to
  the start page, hides the admin toolbar for them and sends them there after login. The filter
  `orchestra_core_restrict_admin` can change this.
- `PageSetup`: a wp-admin notice for administrators that lists every missing Orchestra page (start
  page, login, and the page of every area) with an unticked checkbox and creates the selected ones;
  area pages become children of the start page. "I will add the shortcodes myself" hides it for the
  pages missing at that moment (option `orchestra_core_page_setup_dismissed`, a list of shortcodes);
  a new area brings it back.
- `LoginShortcode` and `LoginPageRedirect`: the login page. Orchestra links that need a login,
  i.e. an Orchestra page (a page with an Orchestra shortcode) or an Orchestra action of
  `admin-post.php` (`orchestra_*`, e.g. a sheet music download), lead to the Orchestra login page
  with `redirect_to`, and the login returns there (only URLs of the site). Without a login page, the
  start page with its login form is used. All other login links of the site (e.g. of a shop or the
  theme), wp-admin and forced re-logins keep WordPress's login screen, so the Orchestra pages can
  be an area inside a website with logins of its own. The Orchestra pages have no "Log out" link of
  their own: it is the "Log in / Log out" entry of the site's menu and on the login page. For
  visitors, the start page shows "<site name> – internal area" and a line about what is inside
  above the login form.
- `PasswordReset` (since 0.15): "Forgot your password?" happens on the Orchestra login page too.
  `lostpassword_url` leads to `?orchestra_login=lostpassword`, where WordPress's email is requested
  (`retrieve_password()`; the page never says whether the account exists). The link in that email
  and in the welcome email of new accounts opens `wp-login.php?action=rp`, which moves the key into
  a cookie (like WordPress, so the key does not stay in the URL) and continues on
  `?orchestra_login=resetpass`; the new password needs at least 8 characters. Results are shown with
  a fixed `orchestra_password` state in the URL, since visitors have no notices. Without an
  Orchestra login or start page, WordPress's screens are used.
- `LoginPageRedirect::requireLogin()`: the plugins hook it to `admin_post_nopriv_<action>` for
  every action that needs a login; it always uses the Orchestra login page. WordPress's
  `auth_redirect` must not be used for this: it always forces a new login and therefore leads to
  WordPress's login screen. After a form (`POST`), the login returns to the page the form was sent
  from.
- `InternalSite`: the Orchestra pages are internal, also when they are an area inside a public
  website; the rest of the site is left alone. Pages (and posts) with an Orchestra shortcode have
  `noindex`, are left out of the sitemaps and cannot be embedded (oEmbed). The author archives of
  users with an Orchestra role who have published nothing are not found, so visitors cannot find
  out who has an account (WordPress itself already hides users without published posts in the REST
  API and the users sitemap). Sheet music and attachments are private anyway (see
  [persistence.md](persistence.md)). They are not blocked in `robots.txt`: search engines have to
  read the pages to see `noindex`, and the file would list them. A site that is only the orchestra
  ticks "Discourage search engines" (Settings → Reading), which makes every page `noindex`, blocks
  `robots.txt` and turns off the sitemaps. Up to orchestra-core 0.11 the whole site was internal;
  the filter `orchestra_core_internal_site` no longer exists.
