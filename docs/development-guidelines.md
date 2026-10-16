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

## Frontend first

Nobody is ever forced to use the WordPress dashboard. Every function is available on normal pages
of the site through a shortcode, and those pages look good with any theme.

- Each plugin has one shortcode per area (e.g. `[orchestra_projects]`). It shows what the current
  user may do: members and organizers see different things on the same page.
- wp-admin screens are optional and meant for administrators. Members, conductors and organizers
  are kept out of wp-admin (`Orchestra\Core\Ui\AdminAccess`).
- wp-admin screens and shortcodes render the **same view classes** and post to the **same
  handlers**. Never build a function only for wp-admin.
- Forms post to `admin-post.php` with `Orchestra\Core\Ui\Form` (nonce, `redirect_to`); handlers
  return with `Redirect::back()` and report with `Notices`.
- Markup uses the components of `assets/orchestra-ui.css` (cards, tables, badges, forms, tabs,
  notices). Shortcodes wrap their output in `Html::page()`, wp-admin screens in `Html::adminPage()`.
  Tables use `Html::table()`, which stacks rows on phones.
- Every area registers a `MenuItem`, so it appears in `[orchestra_menu]`, on the start page and in
  "Create Orchestra pages".
- Check new pages at phone and desktop width.

## Translations

All text shown to users is written in English and translated; German is included.

- Wrap every user-visible string in the WordPress functions (`__()`, `esc_html__()`, `_n()`, …) with
  the plugin's text domain (`orchestra-core`, `orchestra-library`, …). Add a `translators:` comment
  for every placeholder.
- Each plugin loads its translations from `languages/` on `init` (priority 0).
- After changing strings, run `bin/translations.sh` (needs GNU gettext). It updates the `.pot`
  file, merges it into every `.po` file and compiles the `.mo` files. Translate new or empty entries
  in `languages/<domain>-de_DE.po` and run it again. Commit all three file types.
- The language of all Orchestra plugins is chosen in Settings → General ("Orchestra language"):
  the site language (German variants use German), English or German
  (`Orchestra\Core\Ui\Language`). It changes only the Orchestra texts; WordPress itself keeps
  the site language.
- German texts address users informally ("du").

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
