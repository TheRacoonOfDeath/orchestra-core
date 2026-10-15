# Building Orchestra Core

## Usage

```bash
./build-plugin.sh
```

Creates `build/orchestra-core-{VERSION}.zip` and `build/update.json`, the update metadata for
installed sites. The version is read from the `Version:` header in `orchestra-core.php`.

## What the zip contains

- `orchestra-core.php`, `uninstall.php`, `README.md`
- `src/`
- `vendor/` with **production dependencies only**, installed freshly from `composer.lock`
  (`composer install --no-dev --classmap-authoritative`)

Tests, documentation, development tools and configuration files are not included.
Your local `vendor/` directory is never copied.

## Requirements

- Bash, `zip`, PHP
- Composer
- A committed, up-to-date `composer.lock`

## Releasing

1. Run `composer check`.
2. Bump `Version:` in `orchestra-core.php`.
3. Run `./build-plugin.sh`.
4. Create a GitHub release `v{VERSION}` and attach the zip **and `update.json`** as release
   assets. Installed sites read `update.json` from the latest release and pick up the update
   automatically, within 12 hours or with **Dashboard → Updates → Check again**.

## First installation

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload the zip and activate the plugin.

The Orchestra roles and capabilities are set up automatically on the next page load.
