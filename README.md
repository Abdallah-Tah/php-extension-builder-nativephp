# NativePHP Extension CLI (`nativephp-ext-cli`)

A Laravel package that provides a powerful command-line tool to build custom static PHP binaries with selected extensions for NativePHP applications using `static-php-cli`. Optimized for Windows with robust handling of compilation challenges.

## Features

- 🚀 **NativePHP Mode** - Builds PHP matching NativePHP php-bin + your database drivers (faster builds!)
- 🎯 **Auto Version Resolution** - `8.3` → `8.3.15` automatically (always latest patch)
- 🪟 **Windows-Optimized** - Handles symlinks, path conversions, and Windows-specific issues
- 🔧 **Multiple PHP Versions** - Support for PHP 8.1, 8.2, 8.3, 8.4, and custom versions
- 💾 **Database Extensions** - MySQL, PostgreSQL, SQL Server, SQLite
- 📦 **Extension Packs** - Web, Performance, Processing, Compression, Network, SOAP
- 🐍 **Python-Based Extraction** - Windows-safe tar.gz/tar.xz extraction with symlink handling
- 🔄 **GitHub Fallback** - Automatic fallback to GitHub cloning when downloads fail
- ✅ **Smart Verification** - Detects successful extractions even with Python errors
- 📝 **Detailed Logging** - Complete build logs for troubleshooting

## Requirements

### For Development
- **PHP 8.1+** - Required for Laravel and package functionality
- **Laravel Framework 10.0+** - Compatible with Laravel 10, 11, and 12
- **Composer** - PHP dependency management

### For Building Custom PHP Binaries (Windows)
- **Strawberry Perl** - Required for OpenSSL compilation
  - ⚠️ **CRITICAL**: Git's Perl has spaces in path and will cause build failures
  - Download from: https://strawberryperl.com/
  - Installs to `C:\Strawberry\perl\bin\perl.exe` (no spaces in path)
  - Auto-detected and validated before build starts
- **Python 3.8+** - Used for reliable tar.gz/tar.xz extraction on Windows
  - Handles archives with symlinks gracefully
  - Install from: https://www.python.org/downloads/
- **CMake 3.15+** - Required for building native Windows libraries (zlib, openssl, etc.)
  - ⚠️ **CRITICAL**: Must add CMake to system PATH during installation
  - Download from: https://cmake.org/download/
  - Verify: `cmake --version`
- **Visual C++ Build Tools** - Microsoft C++ compiler (MSVC) for Windows builds
  - Automatically detected by php-sdk-binary-tools
- **Git for Windows** - Provides bash, tar, and git commands

### PHP Memory Configuration
- **Minimum 512MB** recommended for building PHP binaries
- Default 128MB will cause memory exhaustion errors
- Set via: `php -d memory_limit=512M` or edit `php.ini`

## Installation

```bash
# Install via Composer
composer require amohamed/nativephp-ext-cli --dev

# Verify installation
php artisan list
```

You should see the `php-ext:install` command listed.

## Usage

### Quick Start (Recommended - NativePHP Mode)

```bash
# Build PHP matching NativePHP with database drivers (interactive)
php -d memory_limit=512M artisan php-ext:install
```

This will:
1. Use the **same 26 extensions as NativePHP php-bin** for compatibility
2. Auto-resolve version: `8.3` → `8.3.15` (latest patch)
3. Ask which databases you need (MySQL, PostgreSQL, SQL Server)
4. Build faster (same proven base as NativePHP)

**Result**: PHP binary compatible with NativePHP + your database drivers 🎉

### Build Modes

#### NativePHP Mode (Default - Recommended)

**Best for:** Most users who want NativePHP compatibility + database drivers

```bash
# Interactive mode (recommended)
php -d memory_limit=512M artisan php-ext:install

# With specific version
php -d memory_limit=512M artisan php-ext:install --php-version=8.3

# Non-interactive with databases
php -d memory_limit=512M artisan php-ext:install --php-version=8.3 --extensions=mysqli,pdo_mysql,pgsql,pdo_pgsql
```

**Includes:**
- ✅ All 26 NativePHP base extensions (bcmath, bz2, ctype, curl, dom, fileinfo, filter, gd, iconv, intl, json, libxml, mbstring, openssl, pdo, pdo_sqlite, phar, session, simplexml, sockets, sqlite3, tokenizer, xml, opcache, zip, zlib)
- ✅ User-selected database drivers
- ✅ Faster builds (uses proven extension set)
- ✅ Perfect compatibility with NativePHP

#### Custom Mode (Advanced)

**Best for:** Advanced users who need specific extension combinations

```bash
# Full customization
php -d memory_limit=512M artisan php-ext:install --mode=custom
```

**Includes:**
- Minimal default extensions
- Full control over all extensions
- Slower builds (more choices to make)

### Auto Version Resolution

The package automatically resolves major.minor versions to the latest patch:

```bash
# These automatically resolve to latest patch versions:
php artisan php-ext:install --php-version=8.1  # → 8.1.31 (latest)
php artisan php-ext:install --php-version=8.2  # → 8.2.29 (latest)
php artisan php-ext:install --php-version=8.3  # → 8.3.15 (latest)
php artisan php-ext:install --php-version=8.4  # → 8.4.13 (latest)

# Or specify exact version:
php artisan php-ext:install --php-version=8.3.13  # Uses exactly 8.3.13
```

**Benefits:**
- Always gets latest security patches
- Automatic fallback if PHP.net API unavailable
- No need to track patch versions manually

### Supported PHP Versions

- **8.1.x** - Full support including SQL Server extensions
- **8.2.x** - Full support including SQL Server extensions
- **8.3.x** - Full support including SQL Server extensions
- **8.4.x** - Supported (SQL Server extensions not available)
- **Custom** - Any specific version (e.g., 8.3.13, 8.4.1)

### Available Extensions

#### Database Extensions
- **MySQL**: `mysqli`, `pdo_mysql`
- **PostgreSQL**: `pgsql`, `pdo_pgsql`
- **SQL Server**: `sqlsrv`, `pdo_sqlsrv` (PHP 8.3 and below)
- **SQLite**: `sqlite3`, `pdo_sqlite` (always included)

#### NativePHP Base Extensions (Always Included in NativePHP Mode)
```
bcmath, bz2, ctype, curl, dom, fileinfo, filter, gd, iconv, intl,
json, libxml, mbstring, openssl, pdo, pdo_sqlite, phar, session,
simplexml, sockets, sqlite3, tokenizer, xml, opcache, zip, zlib
```

#### Extension Packs (Custom Mode Only)
- **Web**: `dom`, `xml`, `simplexml`, `gd`
- **Performance**: `opcache`, `phar`
- **Processing**: `iconv`, `ctype`, `bcmath`
- **Compression**: `bz2`
- **Network**: `sockets`
- **SOAP**: `soap`

### Build Output

After successful build:
- **Binary Location**: `static-php-cli/buildroot/bin/php.exe`
- **Build Logs**: `static-php-cli/log/spc.output.log` and `spc.shell.log`

### Verify Built Binary

```bash
# Check PHP version
./static-php-cli/buildroot/bin/php.exe -v

# List loaded extensions
./static-php-cli/buildroot/bin/php.exe -m

# Check specific extension
./static-php-cli/buildroot/bin/php.exe --ri mysqli

# Verify database extensions
./static-php-cli/buildroot/bin/php.exe -m | grep -E "(mysqli|pdo_mysql|pgsql|pdo_pgsql|sqlsrv|pdo_sqlsrv)"
```

## How It Works

### Build Process

1. **Setup Phase**: Clones `static-php-cli` and `php-sdk-binary-tools`
2. **Download Phase**: Downloads PHP source, libraries, and extension sources
3. **Pre-extraction Phase**: Uses Python to extract sources (Windows-safe)
4. **Library Extraction**: Handles symlinks and Windows path issues
5. **Build Phase**: Compiles PHP with MSVC and selected extensions
6. **Verification Phase**: Tests the compiled binary

### Windows-Specific Adaptations

- **Symlink Handling**: Python script skips symlinks (not supported on Windows without admin rights)
- **Path Conversion**: Automatic conversion between Windows and Unix-style paths
- **GitHub Fallback**: Auto-clones from GitHub when standard downloads fail (e.g., openssl)
- **Hash File Creation**: Prevents static-php-cli from re-extracting pre-extracted sources
- **Windows-Optimized Repos**: Uses `winlibs` repositories for better Windows compatibility (libxml2, zlib, etc.)
- **Versioned Archive Detection**: Automatically finds archives like `sqlsrv-5.11.1.tgz`

## NativePHP Integration

This package is designed for use with [NativePHP](https://nativephp.com/) applications.

### Step 1: Install NativePHP

```bash
composer require nativephp/electron
```

### Step 2: Build Custom PHP Binary

```bash
# Build with MySQL, PostgreSQL, and SQL Server
php -d memory_limit=512M artisan php-ext:install --php-version=8.3 --extensions=mysqli,pdo_mysql,pgsql,pdo_pgsql,sqlsrv,pdo_sqlsrv
```

### Step 3: Configure NativePHP

Add to your `.env` file:

```env
# Use custom PHP binary with database drivers
NATIVEPHP_PHP_BINARY_PATH=static-php-cli/buildroot/bin/php.exe
```

### Step 4: Build Your App

```bash
# Development mode
composer native:dev

# Production build
php artisan native:build windows
```

Your NativePHP app now has full database driver support! 🎉

For detailed integration guide, see the example project below.

## Comparison: NativePHP Mode vs Custom Mode

| Feature | NativePHP Mode | Custom Mode |
|---------|----------------|-------------|
| **Base Extensions** | 26 (same as NativePHP) | Minimal set |
| **Database Selection** | Interactive (MySQL/PostgreSQL/SQL Server) | Full customization |
| **Build Time** | ⚡ Faster (proven set) | Slower (more choices) |
| **Compatibility** | ✅ Matches NativePHP php-bin | Variable |
| **Use Case** | Most users | Advanced users |
| **Command** | `--mode=nativephp` (default) | `--mode=custom` |

## Troubleshooting

### Memory Exhaustion Error
```
Allowed memory size of 134217728 bytes exhausted
```
**Solution**:
```bash
php -d memory_limit=512M artisan php-ext:install
```

### CMake Not Found
```
'cmake' is not recognized as an internal or external command
```
**Solution**:
1. Install CMake from https://cmake.org/download/
2. Select "Add CMake to system PATH" during installation
3. Restart terminal
4. Verify: `cmake --version`

### OpenSSL Build Failure - Perl Path Error
```
'C:\Program' is not recognized as an internal or external command
```
**Cause**: Git's Perl has spaces in path (`C:\Program Files\Git\usr\bin\perl.exe`)

**Solution**: Install Strawberry Perl (REQUIRED)
1. Download from https://strawberryperl.com/
2. Run installer (adds to PATH automatically)
3. Restart terminal
4. Verify: `perl --version` should show Strawberry Perl
5. Retry build

**Why This Works**: Strawberry Perl installs to `C:\Strawberry\perl\bin\perl.exe` (no spaces), which Windows can execute properly during OpenSSL compilation.

**Auto-Detection**: The package now detects Perl path issues and warns you before build starts.

### Version Resolution Failed
```
Could not reach PHP.net API
```
**Status**: Automatically handled with fallback
- Package includes known latest versions for each PHP branch
- Automatic fallback: `8.3` → `8.3.15`, `8.2` → `8.2.29`, etc.
- No action needed!

### SQL Server Extension Not Found
```
tar: Cannot open: No such file or directory
```
**Status**: Fixed in latest version
- Enhanced archive detection for versioned files (`sqlsrv-5.11.1.tgz`)
- Automatically finds and extracts SQL Server extensions

### MSVC Compiler Errors (C4146, C4703)
```
error C4146: unary minus operator applied to unsigned type
```
**Cause**: PHP 8.3.26+ has code patterns triggering strict MSVC warnings

**Workaround**:
1. Try building without SQL Server extensions
2. Use PHP 8.3.13 (known stable version)
3. Retry - sometimes transient compiler issues resolve

### Python Unicode Error
```
UnicodeEncodeError: 'charmap' codec can't encode character
```
**Status**: Fixed in latest version - Script uses ASCII-safe output

### Symlink Errors
```
tar: Cannot create symlink
```
**Status**: Fixed - Python script skips symlinks automatically

### Clean Build
If build fails repeatedly:
```bash
rm -rf static-php-cli
php -d memory_limit=512M artisan php-ext:install
```

For additional help, check the build logs in `static-php-cli/log/` or open an issue on the [GitHub repository](https://github.com/Abdallah-Tah/nativephp-ext-cli).

## Example Project

See a complete implementation of this package in action:
- **[NativePHP Database Driver Switcher](https://github.com/Abdallah-Tah/nativephp-switch-driver-sql)**
  - Live demo of switching between SQLite, MySQL, PostgreSQL, and SQL Server
  - Livewire interface for real-time database operations
  - Complete NativePHP integration guide

## Changelog

### v1.2.0 (Latest)
- ✨ **NativePHP Mode**: Build with NativePHP's exact extension set + database drivers
- ✨ **Auto Version Resolution**: `8.3` → `8.3.15` automatically with fallback
- 🐛 **Fix**: Enhanced SQL Server extension detection (versioned archives)
- 🐛 **Fix**: Clean old PHP archives to prevent version mismatch
- 📝 **Improved**: Clear version display (`8.3 → 8.3.15 (latest)`)
- 📚 **Added**: Comprehensive NativePHP integration documentation

### v1.1.0
- Windows-specific adaptations and Python extraction
- GitHub fallback for failed downloads
- Enhanced error handling

### v1.0.0
- Initial release

## Contributing

Contributions are welcome! Please submit a pull request or open an issue on the [GitHub repository](https://github.com/Abdallah-Tah/nativephp-ext-cli).

### Development Setup

```bash
# Clone the repository
git clone https://github.com/Abdallah-Tah/nativephp-ext-cli.git
cd nativephp-ext-cli

# Install dependencies
composer install

# Run tests
composer test
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- [static-php-cli](https://github.com/crazywhalecc/static-php-cli) - PHP static compiler
- [NativePHP](https://nativephp.com/) - Laravel desktop app framework
- [php-sdk-binary-tools](https://github.com/php/php-sdk-binary-tools) - Microsoft's PHP SDK for Windows
- [NativePHP php-bin](https://github.com/NativePHP/php-bin) - Official PHP binaries for NativePHP

## Support

- 📖 [Documentation](https://github.com/Abdallah-Tah/nativephp-ext-cli)
- 📚 [SPC build optimization playbook](docs/spc-build-optimization.md)
- 🐛 [Issue Tracker](https://github.com/Abdallah-Tah/nativephp-ext-cli/issues)
- 💬 [Discussions](https://github.com/Abdallah-Tah/nativephp-ext-cli/discussions)
