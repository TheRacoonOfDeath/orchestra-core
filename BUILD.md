# Building Orchestra Core Plugin

This directory contains scripts to build and distribute the Orchestra Core plugin.

## Build Script

### Usage

```bash
./build-plugin.sh
```

This creates a distributable zip file containing:
- Main plugin file (`orchestra-core.php`)
- Source code (`src/`)
- Dependencies (`vendor/`)
- Configuration files (`composer.json`, `composer.lock`)
- Documentation (`docs/`, `README.md`)

### Output

The zip file is created in the `build/` directory with naming convention:
```
build/orchestra-core-{VERSION}.zip
```

Example:
```
build/orchestra-core-0.1.3.zip
```

### What's Included

✅ Production code
✅ Autoloader and dependencies
✅ Documentation
✅ Configuration

### What's Excluded

❌ Test files
❌ Development tools (PHPStan, PHPUnit)
❌ Git history
❌ IDE configuration
❌ Unnecessary dependency files

### WordPress Installation

1. Run the build script: `./build-plugin.sh`
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Choose the generated zip file from `build/orchestra-core-{VERSION}.zip`
4. Click "Install Now"
5. Activate the plugin

### Automatic Setup

Upon activation, the plugin automatically:
- Creates `conductor` and `organizer` roles
- Assigns all Orchestra capabilities to roles
- Configures WordPress permissions
- No further configuration needed!

## Requirements

- Bash shell
- `zip` command-line tool
- `grep` and standard Unix tools

## Platform Support

✅ Linux
✅ macOS
✅ WSL (Windows Subsystem for Linux)

## Troubleshooting

**Script not found:** Make sure you're in the plugin root directory.

**Permission denied:** Run `chmod +x build-plugin.sh`

**Zip command not found:** Install zip utility:
- macOS: `brew install zip`
- Ubuntu/Debian: `apt-get install zip`
- CentOS: `yum install zip`
