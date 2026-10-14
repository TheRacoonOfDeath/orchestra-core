# Building Orchestra Core

## Usage

```bash
./build-plugin.sh
```

Creates `build/orchestra-core-{VERSION}.zip`. The version is read from the `Version:` header in
`orchestra-core.php`.

## What the zip contains

- `orchestra-core.php`, `uninstall.php`, `README.md`
- `src/`
- `vendor/` with **production dependencies only**, installed freshly from `composer.lock`
  (`composer install --no-dev --classmap-authoritative`)

Tests, documentation, development tools and configuration files are not included.
Your local `vendor/` directory is never copied.

## Requirements

- Bash, `zip`
- Composer
- A committed, up-to-date `composer.lock`

## Releasing

1. Run `composer check`.
2. Bump `Version:` in `orchestra-core.php`.
3. Run `./build-plugin.sh`.
4. Create a GitHub release for the version and attach the zip as a release asset.
   Installed sites pick up the update automatically.

## First installation

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload the zip and activate the plugin.

The Orchestra roles and capabilities are set up automatically on the next page load.
