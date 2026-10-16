# orchestra-core
Core repo providing some fundamental functionality to the orchestra plugins and serving as a central project documentation.

## Documentation

- [Architecture](docs/architecture.md)
- [Domain model](docs/domain-model.md)
- [Use cases](docs/use-cases.md)
- [Roles and capabilities](docs/capabilities.md)
- [Persistence](docs/persistence.md)
- [Development guidelines](docs/development-guidelines.md)
- [Building a release](BUILD.md)

## Features

- The roles Orchestra Member, Conductor and Organizer, and the registry through which the other
  Orchestra plugins grant their capabilities
- The Orchestra pages: members, conductors and organizers use the site, never the dashboard
  - `[orchestra_portal]` start page, `[orchestra_menu]` navigation, `[orchestra_login]` login form
  - a shared, theme-aware design for all Orchestra pages
  - members, conductors and organizers are kept out of wp-admin; administrators keep it
  - **Create Orchestra pages**: one click creates all pages with their shortcodes
  - a menu entry "Log in / Log out" for the site's menu (**Appearance → Menus**, box "Orchestra"):
    visitors see "Log in", logged-in users "Log out"

Emails to members and project participants are a plugin of their own,
[orchestra-email](https://github.com/TheRacoonOfDeath/orchestra-email), since 0.7.

## Getting started

1. Install and activate orchestra-core and the other Orchestra plugins.
2. Click **Create Orchestra pages** in the notice in wp-admin. It creates a start page
   "Orchestra" with sub-pages for every area, each with the navigation on top.
3. Add the "Orchestra" page to your site's menu.

The shortcodes can also be placed on any page by hand; see [architecture.md](docs/architecture.md#orchestra-pages).

## Updates

The plugin updates itself from the releases of this public GitHub repository. The plugin reads
`update.json` from the latest release, so the GitHub API and its rate limit are not involved and
no token is needed.

## Development

```bash
composer install
composer check   # coding standard, static analysis and tests
```
