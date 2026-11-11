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
3. Commit and push, then push an annotated tag with short user-facing notes:
   `git tag -a v{VERSION} -m "notes" && git push origin v{VERSION}`. GitHub Actions runs
   `composer check`, builds the zip and `update.json` with `./build-plugin.sh`, signs their build
   provenance and creates the release
   ([orchestra-workflows](https://github.com/TheRacoonOfDeath/orchestra-workflows#releases)).
   `./build-plugin.sh` stays available for local test builds.

Installed sites read `update.json` from the latest release and pick up the update automatically,
within 12 hours or with **Dashboard → Updates → Check again**.

Every plugin of the suite releases this way (`.github/workflows/release.yml`). Zips are never
built for a release on a developer's machine, so a compromised laptop cannot ship code to the
sites. Anyone can check a zip:

```bash
gh attestation verify orchestra-core-X.Y.Z.zip --repo TheRacoonOfDeath/orchestra-core
```

## First installation

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload the zip and activate the plugin.

The Orchestra roles and capabilities are set up automatically on the next page load.
