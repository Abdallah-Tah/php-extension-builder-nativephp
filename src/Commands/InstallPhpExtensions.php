<?php

namespace Amohamed\NativePhpCustomPhp\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Process;

use RuntimeException;

use ZipArchive;

class InstallPhpExtensions extends Command
{

    protected $signature = 'php-ext:install
        {--php-version= : PHP version to build (e.g., 8.3, 8.4, 8.3.13, 8.4.1)}
        {--extensions= : Comma-separated list of extensions (or use interactive mode)}
        {--pack=* : Named extension pack(s) to include (mysql, pgsql, sqlsrv, all)}
        {--profile=slim : Build profile: slim (default) or full}
        {--mode=nativephp : Build mode: nativephp (match NativePHP + add DB drivers) or custom (full customization)}
        {--build-flag=* : Additional flags passed to static-php-cli}
        {--dry-run : Resolve configuration and exit without building}
        {--json : Emit machine-readable JSON output}
        {--lockfile=.nativephp-ext.lock : Lockfile to read from and write to}
        {--cache-dir= : Override cache directory}
        {--no-cache : Skip artifact cache lookups and rebuild}
    ';

    protected $description = 'Build a PHP binary matching NativePHP with additional database drivers (use --mode=custom for full customization)';

    protected array $availableExtensions = [

        // Database extensions

        'sqlite3' => [

            'name' => 'SQLite',

            'description' => 'SQLite database support (included by default)',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['sqlite']

        ],

        'pdo' => [

            'name' => 'PDO',

            'description' => 'PHP Data Objects (core extension)',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'pdo_sqlite' => [

            'name' => 'PDO SQLite',

            'description' => 'PDO driver for SQLite',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['sqlite']

        ],

        'mysqli' => [

            'name' => 'MySQLi',

            'description' => 'MySQL Improved extension',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'pdo_mysql' => [

            'name' => 'PDO MySQL',

            'description' => 'PDO driver for MySQL',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'pgsql' => [

            'name' => 'PostgreSQL',

            'description' => 'PostgreSQL database support',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['postgresql-win']

        ],

        'pdo_pgsql' => [

            'name' => 'PDO PostgreSQL',

            'description' => 'PDO driver for PostgreSQL',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['postgresql-win']

        ],

        'sqlsrv' => [

            'name' => 'SQL Server',

            'description' => 'Microsoft SQL Server support (PHP 8.3 and below)',

            'php_versions' => ['8.1', '8.2', '8.3'],

            'libraries' => []

        ],

        'pdo_sqlsrv' => [

            'name' => 'PDO SQL Server',

            'description' => 'PDO driver for Microsoft SQL Server (PHP 8.3 and below)',

            'php_versions' => ['8.1', '8.2', '8.3'],

            'libraries' => []

        ],

        // Default/Core extensions

        'bcmath' => [

            'name' => 'BCMath',

            'description' => 'Arbitrary precision mathematics',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'bz2' => [

            'name' => 'Bzip2',

            'description' => 'Bzip2 compression',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['bzip2']

        ],

        'ctype' => [

            'name' => 'Ctype',

            'description' => 'Character type functions',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'curl' => [

            'name' => 'cURL',

            'description' => 'HTTP client library',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['curl', 'libcurl', 'nghttp2', 'libssh2', 'openssl']

        ],

        'dom' => [

            'name' => 'DOM',

            'description' => 'Document Object Model',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libxml2']

        ],

        'fileinfo' => [

            'name' => 'Fileinfo',

            'description' => 'File information functions',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'filter' => [

            'name' => 'Filter',

            'description' => 'Data filtering',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'gd' => [

            'name' => 'GD',

            'description' => 'Image processing library',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libpng', 'libjpeg', 'freetype', 'libwebp']

        ],

        'iconv' => [

            'name' => 'Iconv',

            'description' => 'Character encoding conversion',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'intl' => [

            'name' => 'Intl',

            'description' => 'Internationalization functions',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => [] // Uses ICU library, built into static-php-cli

        ],

        'json' => [

            'name' => 'JSON',

            'description' => 'JavaScript Object Notation handling (core extension)',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'libxml' => [

            'name' => 'LibXML',

            'description' => 'Low-level XML library support',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libxml2']

        ],

        'mbstring' => [

            'name' => 'Multibyte String',

            'description' => 'Multibyte string functions',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'mbregex' => [

            'name' => 'Multibyte Regex',

            'description' => 'Multibyte regular expression support (bundled with mbstring)',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'opcache' => [

            'name' => 'OPcache',

            'description' => 'PHP opcode caching',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'openssl' => [

            'name' => 'OpenSSL',

            'description' => 'OpenSSL cryptographic functions',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['openssl']

        ],

        'phar' => [

            'name' => 'Phar',

            'description' => 'PHP Archive format',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'session' => [

            'name' => 'Session',

            'description' => 'Session handling',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'simplexml' => [

            'name' => 'SimpleXML',

            'description' => 'Simple XML parser',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libxml2']

        ],

        'sockets' => [

            'name' => 'Sockets',

            'description' => 'Socket communication',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'tokenizer' => [

            'name' => 'Tokenizer',

            'description' => 'PHP tokenizer',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => []

        ],

        'xml' => [

            'name' => 'XML',

            'description' => 'XML parser',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libxml2']

        ],

        'zip' => [

            'name' => 'ZIP',

            'description' => 'ZIP archive support',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libzip', 'zlib']

        ],

        'zlib' => [

            'name' => 'Zlib',

            'description' => 'Compression library',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['zlib']

        ],

        // Additional optional extensions

        'soap' => [

            'name' => 'SOAP',

            'description' => 'SOAP protocol support',

            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

            'libraries' => ['libxml2']

        ]

    ];

    protected string $selectedPhpVersion = '8.3';

    protected string $selectedPhpExactVersion = '';

    protected ?string $phpArchiveFilename = null;

    protected ?string $phpDownloadUrl = null;

    protected array $phpReleaseMetadata = [];

    protected array $selectedExtensions = [];

    protected array $requiredLibraries = [];

    /**
     * Build profile that controls additional extensions packaged in the artifact.
     */
    protected string $buildProfile = 'slim';

    /**
     * User requested extension packs (from CLI flags or lockfile).
     */
    protected array $requestedPacks = [];

    /**
     * Additional build flags passed through to static-php-cli.
     */
    protected array $additionalBuildFlags = [];

    /**
     * Validated Perl executable path for OpenSSL compilation.
     */
    protected ?string $validatedPerlPath = null;

    /**
     * Whether the command should only resolve configuration without running a build.
     */
    protected bool $dryRun = false;

    /**
     * Whether JSON output should be emitted for machine consumption.
     */
    protected bool $jsonOutput = false;

    /**
     * The build matrix used for calculating deterministic build keys.
     */
    protected array $buildMatrix = [];

    /**
     * SHA-256 hash derived from the build matrix.
     */
    protected string $buildHash = '';

    /**
     * Directory used for storing cached artifacts and build metadata.
     */
    protected ?string $cacheDirectory = null;

    /**
     * Indicates whether the current execution resolved to a cached build.
     */
    protected bool $cacheHit = false;

    /**
     * Handle to the single-flight lock file when acquired.
     */
    protected $lockHandle = null;

    /**
     * Path to the lockfile that stores the last resolved build configuration.
     */
    protected string $lockfilePath = '';

    /**
     * Parsed contents of the lockfile (if present).
     */
    protected array $lockfileData = [];

    /**
     * Path to the cached artifact if the build was resolved from cache.
     */
    protected ?string $cachedArtifactPath = null;

    /**
     * Cached metadata payload for the current build key.
     */
    protected array $buildMetadata = [];

    // Default extensions - Windows-compatible subset that matches NativePHP core functionality
    // Note: Some NativePHP extensions (calendar, exif, ftp, gettext, gmp, readline, xmlreader, xmlwriter, xsl)
    // are not available in static-php-cli on Windows, so they're excluded from this list
    protected array $defaultExtensions = [
        // Core extensions (available on Windows via static-php-cli)
        'bcmath',
        'bz2',
        'ctype',
        'curl',

        // DOM/XML stack (critical for DOMDocument, XML, and Livewire)
        'dom',
        'fileinfo',
        'filter',
        'gd',
        'iconv',
        'intl',
        'json',
        'libxml',
        'mbregex',
        'mbstring',
        'mysqli',
        'opcache',
        'openssl',
        'pdo',
        'pdo_mysql',
        'pdo_sqlite',
        'phar',
        'session',
        'simplexml',
        'soap',
        'sockets',
        'sqlite3',
        'tokenizer',
        'xml',
        'zip',
        'zlib',
    ];


    public function __construct()
    {

        parent::__construct();
    }

    public function handle(): int
    {

        $this->dryRun = (bool) $this->option('dry-run');

        $this->jsonOutput = (bool) $this->option('json');

        $this->lockfilePath = $this->resolveLockfilePath($this->option('lockfile'));

        $this->loadLockfile();

        $profileOption = $this->option('profile');

        if (!$this->input->hasParameterOption('--profile') && isset($this->lockfileData['profile'])) {

            $profileOption = $this->lockfileData['profile'];
        }

        $this->buildProfile = strtolower($profileOption ?? 'slim');

        if (!in_array($this->buildProfile, ['slim', 'full'], true)) {

            throw new RuntimeException("Invalid profile '{$this->buildProfile}'. Expected 'slim' or 'full'.");
        }

        $packOption = $this->normalizePackOptions($this->option('pack'));

        if (!empty($packOption) || $this->input->hasParameterOption('--pack')) {

            $this->requestedPacks = $packOption;
        } elseif (isset($this->lockfileData['packs'])) {

            $this->requestedPacks = $this->normalizePackOptions($this->lockfileData['packs']);
        } else {

            $this->requestedPacks = [];
        }

        $buildFlagOption = $this->normalizeBuildFlags($this->option('build-flag'));

        if (!empty($buildFlagOption) || $this->input->hasParameterOption('--build-flag')) {

            $this->additionalBuildFlags = $buildFlagOption;
        } elseif (isset($this->lockfileData['build_flags'])) {

            $this->additionalBuildFlags = $this->normalizeBuildFlags($this->lockfileData['build_flags']);
        } else {

            $this->additionalBuildFlags = [];
        }

        $this->validateEnvironment();

        // Get user preferences

        $this->getUserPreferences();

        $this->calculateBuildMatrix();

        $this->buildHash = $this->computeBuildHash($this->buildMatrix);

        $this->cacheDirectory = $this->resolveCacheDirectory($this->option('cache-dir'));

        $cacheDisabled = (bool) $this->option('no-cache');

        if (!$cacheDisabled) {

            $this->checkCacheForBuild(true);
        }

        if ($this->dryRun) {

            $this->info('Dry run enabled. Skipping build steps.');

            $this->outputResolvedConfiguration();

            $this->writeLockfile();

            $this->emitJsonSummary('dry-run');

            return self::SUCCESS;
        }

        if (!$cacheDisabled && ($this->cacheHit || $this->checkCacheForBuild())) {

            $this->announceCacheHit();

            $this->writeLockfile();

            $this->emitJsonSummary('cache-hit');

            return self::SUCCESS;
        }

        // Set the path to static-php-cli at the Laravel project root

        $spcPath = base_path('static-php-cli');

        $lockAcquired = false;

        try {

            $this->acquireBuildLock();

            $lockAcquired = true;

            if (!$cacheDisabled && !$this->cacheHit && $this->checkCacheForBuild(true)) {

                $this->announceCacheHit();

                $this->writeLockfile();

                $this->emitJsonSummary('cache-hit');

                return self::SUCCESS;
            }

            // STEP 1: Clone and setup static-php-cli

            $this->info('STEP 1: Cloning and setting up static-php-cli...');

            $this->setupStaticPhpCli($spcPath);

            // STEP 1.5: Setup PHP SDK Binary Tools

            $this->info('STEP 1.5: Setting up PHP SDK Binary Tools...');

            $this->setupPhpSdkBinaryTools($spcPath);

            // STEP 2: Run environment check with auto-fix

            $this->info('STEP 2: Running environment check...');

            $this->runDoctorCheck($spcPath);

            // STEP 3: Download PHP source and required libraries

            $this->info('STEP 3: Downloading required components...');

            $this->downloadComponents($spcPath);

            // STEP 4: Clean previous build artifacts

            $this->info('STEP 4: Cleaning previous build artifacts...');

            $this->cleanBuildArtifacts($spcPath);

            // STEP 4.5: Verify downloaded sources

            $this->info('STEP 4.5: Verifying downloaded sources...');

            $this->verifyDownloadedSources($spcPath);

            // STEP 5: Build PHP with extensions

            $this->info('STEP 5: Building PHP with extensions...');

            $buildResult = $this->buildPhpWithExtensions($spcPath);

            if ($buildResult) {

                $artifactInfo = $this->packageBuildArtifacts($spcPath);

                if ($artifactInfo !== null) {

                    $this->buildMetadata = array_merge($artifactInfo, [

                        'build_key' => $this->buildHash,

                        'matrix' => $this->buildMatrix,

                    ]);

                    $this->storeBuildMetadata($this->buildMetadata);
                }

                $this->info('✅ Build completed successfully!');

                $this->displayBuildSummary($spcPath);

                $this->writeLockfile();

                // Deploy to NativePHP if the package exists

                $this->deployToNativePHP();

                $this->emitJsonSummary('built');

                return self::SUCCESS;
            }

            $this->error('❌ Build failed!');

            $this->emitJsonSummary('build-failed', [

                'error' => 'Build process did not complete successfully.',

            ]);

            return self::FAILURE;
        } catch (\Throwable $e) {

            $this->error('Error: ' . $e->getMessage());

            $this->emitJsonSummary('error', [

                'error' => $e->getMessage(),

            ]);

            return self::FAILURE;
        } finally {

            if ($lockAcquired) {

                $this->releaseBuildLock();
            }
        }
    }

    protected function setupStaticPhpCli(string $spcPath): void
    {

        if (!file_exists($spcPath)) {

            $this->info('Cloning static-php-cli repository (shallow clone for faster download)...');

            $cloneResult = Process::timeout(180)->run("git clone --depth=1 https://github.com/crazywhalecc/static-php-cli.git \"{$spcPath}\"");

            if (!$cloneResult->successful()) {

                throw new RuntimeException('Failed to clone static-php-cli repository: ' . $cloneResult->errorOutput());
            }
        }

        // Update composer.json PHP version requirement

        $composerJsonPath = $spcPath . '/composer.json';

        if (file_exists($composerJsonPath)) {

            $this->info('Updating composer.json PHP version requirement...');

            $composerJson = json_decode(file_get_contents($composerJsonPath), true);

            $composerJson['require']['php'] = '>=8.3.0';

            // Remove composer.lock to force fresh install

            if (file_exists($spcPath . '/composer.lock')) {

                unlink($spcPath . '/composer.lock');
            }

            file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // Install composer dependencies

        $this->info('Installing composer dependencies...');

        Process::path($spcPath)->run('composer update --ignore-platform-reqs');

        $composerResult = Process::path($spcPath)->run('composer install --ignore-platform-reqs');

        if (!$composerResult->successful()) {

            throw new RuntimeException('Failed to install composer dependencies: ' . $composerResult->errorOutput());
        }
    }

    protected function setupPhpSdkBinaryTools(string $spcPath): void
    {

        $phpSdkPath = $spcPath . '/php-sdk-binary-tools';
        if (!file_exists($phpSdkPath)) {
            $this->info('Cloning php-sdk-binary-tools repository...');

            $cloneResult = Process::run("git clone https://github.com/php/php-sdk-binary-tools.git \"{$phpSdkPath}\"");

            if (!$cloneResult->successful()) {
                throw new RuntimeException('Failed to clone php-sdk-binary-tools repository: ' . $cloneResult->errorOutput());
            }
        } else {
            $this->info('php-sdk-binary-tools already exists, updating...');

            Process::path($phpSdkPath)->run('git pull');
        }

        // Create a symbolic link or copy to make it accessible from the expected path

        $sourcePath = $spcPath . '/source';

        if (!file_exists($sourcePath)) {

            mkdir($sourcePath, 0755, true);
        }

        $linkPath = $sourcePath . '/php-sdk-binary-tools';

        if (!file_exists($linkPath)) {

            // Create junction on Windows

            $linkResult = Process::run("mklink /J \"{$linkPath}\" \"{$phpSdkPath}\"");

            if (!$linkResult->successful()) {

                $this->warn('Failed to create junction, copying directory instead...');

                Process::run("xcopy \"{$phpSdkPath}\" \"{$linkPath}\" /E /I /Q");
            }
        }
    }

    protected function runDoctorCheck(string $spcPath): void
    {

        $doctorResult = Process::path($spcPath)

            ->timeout(300)

            ->env($this->getSpcEnvironment())

            ->run('php bin/spc doctor --auto-fix');

        if (!$doctorResult->successful()) {

            $this->warn('Doctor check failed. Attempting to continue anyway...');
        }
    }

    protected function downloadComponents(string $spcPath): void
    {

        // Download PHP source for the specified version - force the exact version

        $displayVersion = $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion;

        $this->info("Downloading PHP {$displayVersion} source...");

        $this->preparePhpSourceDownload($spcPath);

        $downloadCommand = "php bin/spc download php-src --with-php={$this->selectedPhpVersion}";

        if ($this->phpDownloadUrl) {

            $downloadCommand .= ' --custom-url="php-src:' . $this->phpDownloadUrl . '"';
        }

        $downloadCommand .= ' --ignore-cache-sources=php-src';

        $downloadResult = Process::path($spcPath)

            ->env($this->getSpcEnvironment())

            ->run($downloadCommand);

        if (!$downloadResult->successful()) {

            $this->warn("Specific PHP {$this->selectedPhpVersion} download failed. Checking available versions...");

            // List available PHP versions

            $listResult = Process::path($spcPath)->run('php bin/spc list php-src');

            if ($listResult->successful()) {

                $this->info("Available PHP versions:");

                $this->line($listResult->output());
            }

            throw new RuntimeException("Failed to download PHP {$this->selectedPhpVersion} source. Please check available versions.");
        }

        $phpArchivePath = $this->getPhpArchivePath($spcPath);

        if ($phpArchivePath) {

            $this->updateSelectedPhpVersionFromArchive($phpArchivePath);
        }

        // Verify the correct PHP version was downloaded

        $this->info('Verifying PHP version...');

        $versionCheck = Process::path($spcPath)->run('php bin/spc list sources | findstr php-src');

        if ($versionCheck->successful()) {

            $this->line("PHP source info: " . trim($versionCheck->output()));
        }

        // CRITICAL: Pre-extract PHP source using Windows-compatible method

        $this->info('Pre-extracting PHP source to avoid Windows tar issues...');

        $this->extractPhpSourceWindows($spcPath);

        // CRITICAL: Pre-extract PECL extensions (sqlsrv, pdo_sqlsrv, etc.) to avoid Windows tar issues

        $this->info('Pre-extracting PECL extensions...');

        $this->extractPeclExtensionsWindows($spcPath, $this->selectedExtensions);

        // Download micro SAPI (required for building)

        $this->info('Downloading micro SAPI...');

        $microResult = Process::path($spcPath)

            ->env($this->getSpcEnvironment())

            ->run('php bin/spc download micro');

        if (!$microResult->successful()) {

            throw new RuntimeException('Failed to download micro SAPI');
        }

        // CRITICAL: Handle git-based dependencies before downloading other components

        $this->info('Ensuring git-based dependencies are available...');

        $this->ensureGitDependencies($spcPath);

        // Download required extension sources (only for extensions that need separate source downloads)

        $this->info('Downloading extension sources (where required)...');

        $skippedExtensions = [];

        foreach ($this->selectedExtensions as $ext) {

            // Skip core/built-in extensions that don't need separate downloads

            // These extensions are either built into PHP core or use system libraries

            if (
                in_array($ext, [

                    // Core PHP extensions (built-in)

                    'pdo',
                    'mbstring',
                    'fileinfo',
                    'tokenizer',
                    'filter',
                    'session',

                    'ctype',
                    'openssl',
                    'curl',
                    'zip',
                    'zlib',
                    'iconv',
                    'intl',
                    'json',
                    'mbregex',

                    // Extensions that use system libraries (libraries downloaded separately)

                    'sqlite3',
                    'pdo_sqlite',
                    'bcmath',
                    'dom',
                    'xml',
                    'simplexml',

                    'phar',
                    'sockets',
                    'bz2',
                    'opcache',
                    'gd'

                ])
            ) {

                $skippedExtensions[] = $ext;

                continue;
            }

            $this->info("Downloading {$ext} extension source...");

            $maxRetries = 3;

            $downloaded = false;

            for ($attempt = 1; $attempt <= $maxRetries && !$downloaded; $attempt++) {

                $this->line("Attempt {$attempt} of {$maxRetries}...");

                $downloadResult = Process::path($spcPath)

                    ->timeout(300)

                    ->env($this->getSpcEnvironment())

                    ->run("php bin/spc download {$ext}");

                if ($downloadResult->successful()) {

                    $downloaded = true;

                    $this->info("✅ Successfully downloaded {$ext}");

                    break;
                }

                if ($attempt === $maxRetries) {

                    if (!$this->confirm("Failed to download {$ext}. Continue anyway?")) {

                        throw new RuntimeException("Cannot continue without {$ext}");
                    }
                } else {

                    $this->warn("Download failed, retrying...");

                    sleep(2);
                }
            }
        }

        // Display information about skipped extensions

        if (!empty($skippedExtensions)) {

            $this->info('ℹ️  Skipped extension source downloads (using core/built-in): ' . implode(', ', $skippedExtensions));

            $this->line('These extensions are built into PHP core or will use system libraries downloaded separately.');
        }

        // Download libraries and dependencies for all selected extensions
        // This uses static-php-cli's dependency resolution to automatically download
        // all required libraries including Windows-specific ones like icu-static-win
        $this->info('Downloading libraries and dependencies for selected extensions...');

        $extensions = implode(',', $this->selectedExtensions);
        $this->info('Extensions: ' . $extensions);

        $downloadResult = Process::path($spcPath)
            ->timeout(600) // Increased timeout for downloading many dependencies
            ->env($this->getSpcEnvironment())
            ->run("php bin/spc download --for-extensions={$extensions}");

        if (!$downloadResult->successful()) {
            $this->warn("Bulk download failed, trying individual library downloads...");
            $this->line($downloadResult->errorOutput());

            // Fallback: Download libraries individually if bulk download fails
            $supportedLibraries = $this->filterSupportedLibraries($this->requiredLibraries);

            foreach ($supportedLibraries as $library) {
                $this->info("Downloading library: {$library}");

                $libResult = Process::path($spcPath)
                    ->timeout(300)
                    ->env($this->getSpcEnvironment())
                    ->run("php bin/spc download {$library}");

                if (!$libResult->successful()) {
                    $this->warn("Failed to download {$library}: " . $libResult->errorOutput());
                } else {
                    $this->info("✅ Downloaded {$library}");
                }
            }
        } else {
            $this->info('✅ All libraries and dependencies downloaded successfully');
        }

        // CRITICAL: Ensure tar-based extractions are complete AFTER downloading

        $this->info('Verifying tar-based extractions...');

        $this->ensureTarBasedExtractions($spcPath);
    }

    protected function cleanBuildArtifacts(string $spcPath): void
    {

        $buildrootPath = $spcPath . '/buildroot';

        if (is_dir($buildrootPath)) {

            $this->info('Cleaning buildroot directory...');

            Process::run('rm -rf "' . $buildrootPath . '"');
        }
    }

    protected function verifyDownloadedSources(string $spcPath): void
    {

        $this->info('Checking downloaded sources...');

        // List all downloaded sources

        $listResult = Process::path($spcPath)->run('php bin/spc list sources');

        if ($listResult->successful()) {

            $this->line('Available sources:');

            $this->line($listResult->output());
        }

        // Check specifically for required sources

        $requiredSources = ['php-src', 'micro'];

        foreach ($requiredSources as $source) {

            $checkResult = Process::path($spcPath)->run("php bin/spc list sources | findstr {$source}");

            if ($checkResult->successful() && !empty(trim($checkResult->output()))) {

                $this->info("✅ {$source} is available");
            } else {

                $this->warn("⚠️ {$source} may not be available");

                // Try to download it again

                $this->info("Attempting to download {$source}...");

                $redownloadResult = Process::path($spcPath)->run("php bin/spc download {$source}");

                if ($redownloadResult->successful()) {

                    $this->info("✅ Successfully downloaded {$source}");
                } else {

                    $this->error("❌ Failed to download {$source}");

                    $this->error($redownloadResult->errorOutput());
                }
            }
        }

        // Verify git dependencies are present

        $this->info('Verifying git dependencies...');

        $this->verifyGitDependencies($spcPath);

        // Verify tar-based extractions are complete

        $this->info('Verifying tar-based extractions...');

        $this->ensureTarBasedExtractions($spcPath);
    }

    protected function verifyGitDependencies(string $spcPath): void
    {

        $sourcePath = $spcPath . '/source';

        $sourceJsonPath = $spcPath . '/config/source.json';

        if (!file_exists($sourceJsonPath)) {

            $this->warn('source.json not found, skipping git dependency verification');

            return;
        }

        $sourceConfig = json_decode(file_get_contents($sourceJsonPath), true);

        if (!$sourceConfig) {

            $this->warn('Failed to parse source.json');

            return;
        }

        // Find all git-type dependencies

        $gitDependencies = [];

        foreach ($sourceConfig as $name => $config) {

            if (isset($config['type']) && $config['type'] === 'git') {

                $gitDependencies[] = $name;
            }
        }

        if (empty($gitDependencies)) {

            $this->info('No git dependencies to verify');

            return;
        }

        foreach ($gitDependencies as $name) {

            $dependencyPath = $sourcePath . '/' . $name;

            if (file_exists($dependencyPath) && file_exists($dependencyPath . '/.git')) {

                $this->info("✅ Git dependency {$name} is present");
            } else {

                $this->warn("⚠️ Git dependency {$name} is missing or invalid");

                // Try to ensure it again

                $this->info("Attempting to fix {$name}...");

                $this->ensureGitDependencies($spcPath);

                break; // Re-run the whole check

            }
        }
    }

    /**

     * Get the standard environment variables for SPC processes

     */

    protected function getSpcEnvironment(): array
    {

        return [

            'PATH' => getenv('PATH') . ';C:\Program Files\Git\usr\bin',

            'SPC_CONCURRENCY' => '1',

            'CMAKE_BUILD_PARALLEL_LEVEL' => '1',

            'VS_PATH' => 'C:\Program Files (x86)\Microsoft Visual Studio\2022\BuildTools\VC\Tools\MSVC\14.41.34120\bin\Hostx64\x64',

            // Completely suppress Perl locale warnings by using Windows locale

            'LC_ALL' => 'English_United States.1252',

            'LC_CTYPE' => 'English_United States.1252',

            'LC_NUMERIC' => 'English_United States.1252',

            'LC_TIME' => 'English_United States.1252',

            'LC_COLLATE' => 'English_United States.1252',

            'LC_MONETARY' => 'English_United States.1252',

            'LC_MESSAGES' => 'English_United States.1252',

            'LANG' => 'English_United States.1252',

            'LANGUAGE' => 'en',

            // Perl-specific environment variables to suppress warnings

            'PERL_BADLANG' => '0',

            'PERL_UNICODE' => '',

            'PERL_USE_UNSAFE_INC' => '0',

            // Leave MSYS path conversion enabled so SPC tar commands handle Windows drive paths

            // Suppress other build warnings

            'CMAKE_GENERATOR_PLATFORM' => 'x64',

            'CMAKE_BUILD_TYPE' => 'Release',

            // Additional environment variables to reduce verbosity

            'CMAKE_VERBOSE_MAKEFILE' => 'OFF',

            'VERBOSE' => '0'

        ];
    }

    protected function buildPhpWithExtensions(string $spcPath): bool
    {

        // Build with selected extensions

        $extensions = implode(',', $this->selectedExtensions);

        $this->info("Building with selected extensions: {$extensions}");

        // Build command without --with-php (that's used in download phase)

        $flags = array_merge(['--build-cli', '--debug'], $this->additionalBuildFlags);

        $flags = array_values(array_filter(array_unique($flags)));

        $buildCmd = sprintf(

            'php bin/spc build "%s" %s',

            $extensions,

            implode(' ', array_map(fn($flag) => $flag, $flags))

        );

        $this->line("Running build command: {$buildCmd}");

        $buildProcess = Process::path($spcPath)

            ->timeout(7200) // 2 hour timeout for full build

            ->env($this->getSpcEnvironment())

            ->run($buildCmd);

        if (!$buildProcess->successful()) {

            $this->error("Build failed. Debug output:");

            $this->error($buildProcess->output());

            $this->error($buildProcess->errorOutput());

            // Check if the built PHP binary exists even if build "failed"

            $phpBinaryPath = $spcPath . '/buildroot/bin/php.exe';

            if (file_exists($phpBinaryPath)) {

                $this->info("PHP binary was created despite error. Testing functionality...");

                $testResult = Process::path($spcPath)

                    ->run('buildroot\bin\php.exe -v');

                if ($testResult->successful()) {

                    $this->info("PHP version test output: " . $testResult->output());

                    // Test essential functions

                    $functionsTest = Process::path($spcPath)

                        ->run('buildroot\bin\php.exe -r "echo \"Basic: \" . (function_exists(\'strlen\') ? \'OK\' : \'FAIL\') . \"\nPDO: \" . (class_exists(\'PDO\') ? \'OK\' : \'FAIL\') . \"\nSQLite: \" . (class_exists(\'PDO\') && in_array(\'sqlite\', PDO::getAvailableDrivers()) ? \'OK\' : \'FAIL\') . \"\\n\";"');

                    if ($functionsTest->successful()) {

                        $this->info("Function test output:");

                        $this->info($functionsTest->output());

                        if (strpos($functionsTest->output(), 'Basic: OK') !== false) {

                            $this->info("✅ Build appears successful!");

                            return true;
                        }
                    }
                }
            }

            return false;
        }

        $this->info("✅ Build succeeded!");

        return true;
    }

    protected function validateEnvironment(): void
    {

        if (PHP_OS_FAMILY !== 'Windows') {

            throw new RuntimeException('This command is currently only supported on Windows.');
        }

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {

            throw new RuntimeException('PHP >= 8.1 required.');
        }

        foreach (['mbstring', 'tokenizer'] as $ext) {

            if (!extension_loaded($ext)) {

                throw new RuntimeException("PHP Extension {$ext} is required.");
            }
        }

        // Check for Visual Studio

        if (!file_exists('C:\\Program Files (x86)\\Microsoft Visual Studio\\Installer\\vswhere.exe')) {

            throw new RuntimeException("Visual Studio 2022 with C++ workload and SDKs must be installed.");
        }

        // Check for Perl (required for OpenSSL compilation)

        $this->validatePerl();
    }

    protected function validatePerl(): void
    {

        // Check if Perl is available

        $perlCheck = Process::run('perl --version');

        if (!$perlCheck->successful()) {

            $this->error('Perl is not installed or not in PATH.');

            $this->line('');

            $this->line('OpenSSL compilation requires Perl. Please install Strawberry Perl:');

            $this->line('1. Download from: https://strawberryperl.com/');

            $this->line('2. Run the installer (it will add Perl to PATH automatically)');

            $this->line('3. Restart your terminal');

            $this->line('4. Retry this command');

            throw new RuntimeException('Perl is required for building PHP with OpenSSL support.');
        }

        // Get Perl executable path

        $perlPath = trim(Process::run('where perl')->output());

        $perlPath = explode("\n", $perlPath)[0]; // Get first match

        // Check if Perl path contains spaces (Git's Perl in "Program Files" will fail)

        if (str_contains($perlPath, ' ')) {

            $this->warn('⚠️  WARNING: Perl path contains spaces: ' . $perlPath);

            $this->line('');

            $this->line('This may cause OpenSSL build failures on Windows.');

            $this->line('Git\'s Perl ("C:\\Program Files\\Git\\usr\\bin\\perl.exe") has spaces in the path,');

            $this->line('which causes Windows command line issues during OpenSSL compilation.');

            $this->line('');

            $this->line('RECOMMENDED SOLUTION: Install Strawberry Perl');

            $this->line('1. Download from: https://strawberryperl.com/');

            $this->line('2. Run the installer (installs to C:\\Strawberry\\perl\\bin\\perl.exe - no spaces)');

            $this->line('3. Restart your terminal (Strawberry Perl will be used automatically)');

            $this->line('4. Retry this command');

            $this->line('');

            if (!$this->confirm('Do you want to continue anyway? (Build may fail at OpenSSL compilation)', false)) {

                throw new RuntimeException('Build cancelled. Please install Strawberry Perl and retry.');
            }
        } else {

            $this->info('✓ Perl found: ' . $perlPath);
        }
    }

    protected function getUserPreferences(): void
    {

        $lockDefaults = $this->lockfileData;

        // Get PHP version from option, lockfile, or prompt user

        $phpVersion = $this->option('php-version');

        if (!$this->input->hasParameterOption('--php-version') && empty($phpVersion) && isset($lockDefaults['php_version'])) {

            $phpVersion = $lockDefaults['php_version'];

            $this->info('Using PHP version from lockfile: ' . $phpVersion);
        }

        if (!$phpVersion) {

            $this->info('Common PHP versions: 8.1, 8.2, 8.3, 8.4');

            $this->warn('Note: PHP 8.4 does not support SQL Server extensions (sqlsrv, pdo_sqlsrv)');

            $this->line('');

            $this->line('You can select from the list below or enter a custom version (e.g., 8.3.13, 8.4.0):');

            $phpVersion = $this->choice(

                'Select PHP version to build:',

                ['8.1', '8.2', '8.3', '8.4', 'custom'],

                '8.3'

            );

            // If user selects custom, ask for specific version

            if ($phpVersion === 'custom') {

                $phpVersion = $this->ask('Enter PHP version (e.g., 8.3.13, 8.4.0, 8.4.1)');

                // Validate format (should be X.Y or X.Y.Z)

                if (!preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?$/', $phpVersion)) {

                    throw new RuntimeException('Invalid PHP version format. Use format: 8.3 or 8.3.13');
                }
            }
        }

        // Validate PHP version is supported (8.1+)

        if (version_compare($phpVersion, '8.1', '<')) {

            throw new RuntimeException('PHP version must be 8.1 or higher');
        }

        $versionMatch = [];

        if (!preg_match('/^(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?$/', $phpVersion, $versionMatch)) {

            throw new RuntimeException('Invalid PHP version format. Use format: 8.3 or 8.3.13');
        }

        $baseVersion = $versionMatch['major'] . '.' . $versionMatch['minor'];

        $this->selectedPhpVersion = $baseVersion;

        $patchProvided = isset($versionMatch['patch']) && $versionMatch['patch'] !== '';

        if ($patchProvided) {

            $this->selectedPhpExactVersion = $phpVersion;

            $this->phpReleaseMetadata = [];
        } else {

            $releaseInfo = $this->resolvePhpReleaseMetadata($baseVersion);

            if ($releaseInfo !== null) {

                $this->selectedPhpExactVersion = $releaseInfo['version'];

                $this->phpReleaseMetadata = $releaseInfo['sources'];

                $this->info("Resolved latest PHP {$baseVersion} release: {$this->selectedPhpExactVersion}");
            } else {

                // API call failed - use known latest versions as fallback
                $knownLatestVersions = [
                    '8.1' => '8.1.31',
                    '8.2' => '8.2.29',
                    '8.3' => '8.3.15',
                    '8.4' => '8.4.13',
                ];

                if (isset($knownLatestVersions[$baseVersion])) {
                    $this->selectedPhpExactVersion = $knownLatestVersions[$baseVersion];
                    $this->warn("Could not reach PHP.net API. Using known latest version: {$this->selectedPhpExactVersion}");
                } else {
                    $this->selectedPhpExactVersion = $baseVersion . '.0';
                    $this->warn("Could not determine latest PHP {$baseVersion} release. Using {$this->selectedPhpExactVersion}");
                }

                $this->phpReleaseMetadata = [];
            }
        }

        $this->updatePhpArchiveDetails();

        // Display version with clear indication of resolution
        if ($this->selectedPhpExactVersion !== $this->selectedPhpVersion && $this->selectedPhpExactVersion !== '') {
            $this->info("Selected PHP version: {$this->selectedPhpVersion} → {$this->selectedPhpExactVersion} (latest)");
        } else {
            $this->info("Selected PHP version: {$this->selectedPhpVersion}");
        }

        // Check build mode
        $buildMode = $this->option('mode') ?? 'nativephp';

        // Get extensions from option, packs, lockfile, or prompt user
        $requestedExtensions = [];

        $extensionsInput = $this->option('extensions');

        if ($extensionsInput) {
            $requestedExtensions = array_map('trim', explode(',', $extensionsInput));
        }

        if (!empty($this->requestedPacks)) {
            $packExtensions = $this->resolveExtensionPacks($this->requestedPacks);
            $requestedExtensions = array_merge($requestedExtensions, $packExtensions);
        } elseif (empty($requestedExtensions) && isset($lockDefaults['packs'])) {
            $packExtensions = $this->resolveExtensionPacks($this->normalizePackOptions($lockDefaults['packs']));
            $requestedExtensions = array_merge($requestedExtensions, $packExtensions);
        }

        if (empty($requestedExtensions) && isset($lockDefaults['extensions']) && is_array($lockDefaults['extensions'])) {
            $requestedExtensions = array_merge($requestedExtensions, $lockDefaults['extensions']);
            $this->info('Using extensions from lockfile: ' . implode(', ', $lockDefaults['extensions']));
        }

        if (empty($requestedExtensions)) {
            $this->info('');

            if ($buildMode === 'nativephp') {
                $this->info('Building PHP binary matching NativePHP php-bin with additional database drivers...');
                $this->info('Base extensions (same as NativePHP): ' . count($this->defaultExtensions) . ' extensions included');
                $requestedExtensions = $this->promptForDatabaseDriversOnly();
            } else {
                $this->info('Building a custom PHP binary with your selected database and extension support...');
                $requestedExtensions = $this->promptForExtensions();
            }
        }

        // Filter extensions based on PHP version compatibility

        $this->selectedExtensions = $this->filterExtensionsByPhpVersion($requestedExtensions);

        // Always include default extensions (these are core extensions needed for most PHP applications)

        $this->selectedExtensions = array_unique(array_merge($this->defaultExtensions, $this->selectedExtensions));
        sort($this->selectedExtensions);

        $this->info('Including default extensions: ' . implode(', ', $this->defaultExtensions));

        // Calculate required libraries

        $this->calculateRequiredLibraries();

        $this->info('Selected extensions: ' . implode(', ', $this->selectedExtensions));

        $this->info('Required libraries: ' . implode(', ', $this->requiredLibraries));
    }

    protected function resolvePhpReleaseMetadata(string $majorMinor): ?array
    {

        $url = "https://www.php.net/releases/?json&version={$majorMinor}";

        $context = stream_context_create([

            'http' => [

                'timeout' => 10,

            ],

        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {

            return null;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || empty($data['version'])) {

            return null;
        }

        $sources = [];

        if (!empty($data['source']) && is_array($data['source'])) {

            foreach ($data['source'] as $source) {

                if (is_array($source) && isset($source['filename'])) {

                    $sources[] = $source;
                }
            }
        }

        return [

            'version' => $data['version'],

            'sources' => $sources,

        ];
    }

    protected function updatePhpArchiveDetails(): void
    {

        $version = $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion;

        if ($version === '') {

            return;
        }

        $candidate = $this->selectPhpArchiveCandidate($version, $this->phpReleaseMetadata);

        $this->phpArchiveFilename = $candidate['filename'] ?? null;

        $this->phpDownloadUrl = $candidate['url'] ?? ($this->phpArchiveFilename ? $this->buildPhpDownloadUrl($this->phpArchiveFilename) : null);
    }

    protected function selectPhpArchiveCandidate(string $version, array $sources = []): array
    {

        $preferredExtensions = ['tar.xz', 'tar.gz', 'tar.bz2'];

        foreach ($preferredExtensions as $extension) {

            foreach ($sources as $source) {

                if (!is_array($source) || empty($source['filename'])) {

                    continue;
                }

                if (preg_match('/\\.' . preg_quote($extension, '/') . '$/', $source['filename'])) {

                    return [

                        'filename' => $source['filename'],

                        'url' => $this->buildPhpDownloadUrl($source['filename']),

                    ];
                }
            }
        }

        foreach ($preferredExtensions as $extension) {

            $filename = "php-{$version}.{$extension}";

            $url = $this->buildPhpDownloadUrl($filename);

            if ($this->remoteFileExists($url)) {

                return [

                    'filename' => $filename,

                    'url' => $url,

                ];
            }
        }

        $fallback = "php-{$version}.tar.xz";

        return [

            'filename' => $fallback,

            'url' => $this->buildPhpDownloadUrl($fallback),

        ];
    }

    protected function buildPhpDownloadUrl(string $filename): string
    {

        return 'https://www.php.net/distributions/' . $filename;
    }

    protected function remoteFileExists(string $url): bool
    {

        $headers = @get_headers($url, 1);

        if ($headers === false) {

            return false;
        }

        if (is_array($headers)) {

            foreach ($headers as $key => $value) {

                if (is_int($key) && is_string($value) && preg_match('/^HTTP\/\d+(?:\.\d+)?\s+2\d\d/', $value)) {

                    return true;
                }
            }
        }

        return false;
    }

    protected function getPhpArchivePath(string $spcPath): ?string
    {

        $downloadsPath = $spcPath . '/downloads';

        if ($this->phpArchiveFilename) {

            $target = $downloadsPath . '/' . $this->phpArchiveFilename;

            if (file_exists($target)) {

                return $target;
            }
        }

        $archives = glob($downloadsPath . '/php-*.tar.*');

        return $archives[0] ?? null;
    }

    protected function ensurePhpSourceTreeMatchesSelection(string $spcPath): void
    {

        $phpSourceDir = $spcPath . '/source/php-src';

        if (!is_dir($phpSourceDir)) {

            return;
        }

        $versionHeader = $phpSourceDir . '/main/php_version.h';

        $expectedVersion = $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion;

        if (!file_exists($versionHeader)) {

            Process::run('rm -rf "' . $phpSourceDir . '"');

            return;
        }

        $contents = @file_get_contents($versionHeader);

        if ($contents === false) {

            Process::run('rm -rf "' . $phpSourceDir . '"');

            return;
        }

        if (preg_match('/#define\s+PHP_VERSION\s+"([^"]+)"/', $contents, $match)) {

            $currentVersion = $match[1];

            if ($this->selectedPhpExactVersion === '' && strpos($currentVersion, $this->selectedPhpVersion . '.') === 0) {

                $this->selectedPhpExactVersion = $currentVersion;

                if ($this->phpArchiveFilename === null) {

                    $this->phpArchiveFilename = 'php-' . $currentVersion . '.tar.xz';
                }
            }

            $mismatchDetected = false;

            if ($this->selectedPhpExactVersion !== '' && $currentVersion !== $this->selectedPhpExactVersion) {

                $mismatchDetected = true;
            } elseif ($this->selectedPhpExactVersion === '' && $this->selectedPhpVersion !== '' && strpos($currentVersion, $this->selectedPhpVersion . '.') !== 0) {

                $mismatchDetected = true;
            }

            if ($mismatchDetected) {

                Process::run('rm -rf "' . $phpSourceDir . '"');
            }
        }
    }

    protected function updateSelectedPhpVersionFromArchive(string $archivePath): void
    {

        if (preg_match('/php-([0-9]+\.[0-9]+\.[0-9]+)\.tar\.(?:xz|gz|bz2)$/', basename($archivePath), $match)) {

            $detectedVersion = $match[1];

            if ($this->selectedPhpExactVersion === '' || $this->selectedPhpExactVersion === $this->selectedPhpVersion) {

                $this->selectedPhpExactVersion = $detectedVersion;
            }

            if ($this->phpArchiveFilename === null) {

                $this->phpArchiveFilename = basename($archivePath);
            }

            if ($this->phpDownloadUrl === null) {

                $this->phpDownloadUrl = $this->buildPhpDownloadUrl($this->phpArchiveFilename);
            }
        }
    }

    protected function preparePhpSourceDownload(string $spcPath): void
    {

        $downloadsPath = $spcPath . '/downloads';

        if (!is_dir($downloadsPath)) {

            mkdir($downloadsPath, 0755, true);
        }

        // Clean ALL old PHP archives to prevent version mismatch
        // This ensures we download the correct version every time
        $cleanAll = true;

        if ($this->phpArchiveFilename) {
            // If we have a specific filename, only keep that one
            foreach (glob($downloadsPath . '/php-*.tar.*') as $archive) {
                if (basename($archive) === $this->phpArchiveFilename) {
                    $cleanAll = false;
                    continue;
                }
                @unlink($archive);
            }
        }

        // If no specific archive filename or no matching archive found, clean all
        if ($cleanAll) {
            foreach (glob($downloadsPath . '/php-*.tar.*') as $archive) {
                @unlink($archive);
            }
        }

        $this->ensurePhpSourceTreeMatchesSelection($spcPath);
    }

    protected function promptForExtensions(): array
    {

        $this->info('Select database types to include:');

        $this->line('Note: SQLite is always included by default');

        $databaseTypes = [

            'mysql' => 'MySQL (includes mysqli + pdo_mysql)',

            'postgres' => 'PostgreSQL (includes pgsql + pdo_pgsql)',

        ];

        // Add SQL Server option only for compatible PHP versions

        if (version_compare($this->selectedPhpVersion, '8.4', '<')) {

            $databaseTypes['sqlserver'] = 'SQL Server (includes sqlsrv + pdo_sqlsrv)';
        }

        $selectedDatabases = [];

        foreach ($databaseTypes as $type => $description) {

            $include = $this->confirm("Include {$description}?", false);

            if ($include) {

                $selectedDatabases[] = $type;
            }
        }

        // Convert database types to actual extensions

        $selectedExtensions = $this->mapDatabaseTypesToExtensions($selectedDatabases);

        if (empty($selectedDatabases)) {

            $this->info('No additional database types selected. SQLite will be included by default.');
        }

        $this->info('');

        $this->info('Note: The following extensions are included by default:');

        $this->line('  Core: ' . implode(', ', $this->defaultExtensions));

        // Ask for additional optional extensions

        $this->info('');

        $this->info('Optional additional extensions:');

        $additionalOptions = [

            'none' => 'No additional extensions',

            'web' => 'Web Development Pack (dom, xml, simplexml, gd)',

            'performance' => 'Performance Pack (opcache, phar)',

            'processing' => 'Text Processing Pack (iconv, ctype, bcmath)',

            'compression' => 'Compression Pack (bz2)',

            'network' => 'Network Pack (sockets)',

            'soap' => 'SOAP - Protocol support only',

            'all' => 'All extension packs above',

        ];

        // Display options with numbers

        $this->info('Available extension packs:');

        $optionKeys = array_keys($additionalOptions);

        foreach ($optionKeys as $index => $key) {

            $this->line("  [{$index}] {$key} - {$additionalOptions[$key]}");
        }

        $selectedPacks = $this->promptForMultipleSelections($optionKeys, $additionalOptions);

        // Process selected packs

        foreach ($selectedPacks as $pack) {

            if ($pack === 'none') {

                continue;
            } elseif ($pack === 'all') {

                // Add all extension packs except 'none' and 'all'

                $allPacks = array_diff($optionKeys, ['none', 'all']);

                foreach ($allPacks as $allPack) {

                    $packExtensions = $this->getExtensionPack($allPack);

                    $selectedExtensions = array_merge($selectedExtensions, $packExtensions);
                }

                break; // No need to process other selections if 'all' is selected

            } else {

                $packExtensions = $this->getExtensionPack($pack);

                $selectedExtensions = array_merge($selectedExtensions, $packExtensions);
            }
        }

        // Ask if user wants to add individual extensions

        if (!in_array('all', $selectedPacks) && $this->confirm('Would you like to add individual extensions?', false)) {

            $individualExtensions = $this->promptForIndividualExtensions();

            $selectedExtensions = array_merge($selectedExtensions, $individualExtensions);
        }

        return $selectedExtensions;
    }

    /**
     * Prompt user for database drivers only (NativePHP mode)
     * This skips all other extensions since they're included in the base NativePHP set
     */
    protected function promptForDatabaseDriversOnly(): array
    {
        $this->info('');
        $this->info('Select additional database drivers to include:');
        $this->line('Note: SQLite is already included in the base NativePHP extensions');
        $this->line('');

        $databaseTypes = [
            'mysql' => 'MySQL (includes mysqli + pdo_mysql)',
            'postgres' => 'PostgreSQL (includes pgsql + pdo_pgsql)',
        ];

        // Add SQL Server option only for compatible PHP versions
        if (version_compare($this->selectedPhpVersion, '8.4', '<')) {
            $databaseTypes['sqlserver'] = 'SQL Server (includes sqlsrv + pdo_sqlsrv)';
        }

        $selectedDatabases = [];

        foreach ($databaseTypes as $type => $description) {
            $include = $this->confirm("Include {$description}?", false);
            if ($include) {
                $selectedDatabases[] = $type;
            }
        }

        // Convert database types to actual extensions
        $selectedExtensions = $this->mapDatabaseTypesToExtensions($selectedDatabases);

        if (empty($selectedDatabases)) {
            $this->info('No additional database drivers selected.');
        } else {
            $this->info('Selected database drivers: ' . implode(', ', $selectedExtensions));
        }

        return $selectedExtensions;
    }

    protected function mapDatabaseTypesToExtensions(array $databaseTypes): array
    {

        $extensions = [];

        foreach ($databaseTypes as $type) {

            switch ($type) {

                case 'mysql':

                    $extensions = array_merge($extensions, ['mysqli', 'pdo_mysql']);

                    $this->info('Adding MySQL extensions: mysqli, pdo_mysql');

                    break;

                case 'postgres':

                    $extensions = array_merge($extensions, ['pgsql']);

                    // Only add pdo_pgsql if it's available (it's not in static-php-cli by default)

                    if (isset($this->availableExtensions['pdo_pgsql'])) {

                        $extensions[] = 'pdo_pgsql';

                        $this->info('Adding PostgreSQL extensions: pgsql, pdo_pgsql');
                    } else {

                        $this->info('Adding PostgreSQL extensions: pgsql');

                        $this->warn('Note: pdo_pgsql may not be available in static-php-cli');
                    }

                    break;

                case 'sqlserver':

                    if (version_compare($this->selectedPhpVersion, '8.4', '<')) {

                        $extensions = array_merge($extensions, ['sqlsrv', 'pdo_sqlsrv']);

                        $this->info('Adding SQL Server extensions: sqlsrv, pdo_sqlsrv');
                    } else {

                        $this->warn('SQL Server extensions are not supported in PHP 8.4+');
                    }

                    break;
            }
        }

        return $extensions;
    }

    protected function filterExtensionsByPhpVersion(array $requestedExtensions): array
    {

        $validExtensions = [];

        foreach ($requestedExtensions as $ext) {

            if (!isset($this->availableExtensions[$ext])) {

                $this->warn("Extension '{$ext}' is not available. Skipping...");

                continue;
            }

            $extInfo = $this->availableExtensions[$ext];

            if (!in_array($this->selectedPhpVersion, $extInfo['php_versions'])) {

                $this->warn("Extension '{$ext}' is not compatible with PHP {$this->selectedPhpVersion}. Skipping...");

                continue;
            }

            $validExtensions[] = $ext;
        }

        return $validExtensions;
    }

    protected function calculateRequiredLibraries(): void
    {

        // Base libraries - EXACTLY match NativePHP official minimal set
        // Source: https://github.com/NativePHP/php-bin/blob/main/php-libraries.txt

        $this->requiredLibraries = [
            // NativePHP official minimal libraries (only 3!)
            'libjpeg',        // gd extension (image processing)
            'freetype',       // gd extension (font rendering)
            'libwebp',        // gd extension (WebP image support)
        ];

        // Add libraries for user-selected extensions

        foreach ($this->selectedExtensions as $ext) {

            if (isset($this->availableExtensions[$ext])) {

                $libraries = $this->availableExtensions[$ext]['libraries'];

                $this->requiredLibraries = array_merge($this->requiredLibraries, $libraries);
            }
        }

        $this->requiredLibraries = array_unique($this->requiredLibraries);
    }

    /**
     * Filter and replace unsupported libraries with Windows-compatible alternatives
     */
    protected function filterSupportedLibraries(array $libraries): array
    {
        // Map of unsupported libraries to their Windows-compatible alternatives
        $replacements = [
            'libiconv' => 'libiconv-win',  // Use Windows-compatible iconv
        ];

        $filtered = [];
        foreach ($libraries as $library) {
            // Replace with Windows-compatible alternative if available
            if (isset($replacements[$library])) {
                $replacement = $replacements[$library];
                $this->line("  ℹ️  Replacing {$library} with {$replacement} (Windows-compatible)");
                $filtered[] = $replacement;
            } else {
                $filtered[] = $library;
            }
        }

        return array_unique($filtered);
    }

    protected function promptForMultipleSelections(array $options, array $descriptions): array
    {

        $maxRetries = 3;

        $attempt = 0;

        while ($attempt < $maxRetries) {

            $this->info('');

            $this->info('You can select multiple extension packs by:');

            $this->line('  - Entering numbers separated by commas (e.g., 1,2,3,4,5,6)');

            $this->line('  - Entering individual numbers (e.g., 1)');

            $this->line('  - Entering "all" to select all packs');

            $this->line('  - Pressing Enter for none (default)');

            if ($attempt > 0) {

                $this->warn("Attempt " . ($attempt + 1) . " of {$maxRetries}. Please try again.");
            }

            $input = $this->ask('Select extension pack(s)', 'none');

            $input = trim($input);

            if (empty($input) || $input === 'none' || $input === '0') {

                return ['none'];
            }

            if ($input === 'all' || $input === '7') {

                return ['all'];
            }

            // Handle comma-separated values or single values

            $selections = [];

            $invalidSelections = [];

            $inputParts = array_map('trim', explode(',', $input));

            foreach ($inputParts as $part) {

                // Check if it's a number

                if (is_numeric($part)) {

                    $index = (int) $part;

                    if (isset($options[$index])) {

                        $selections[] = $options[$index];
                    } else {

                        $invalidSelections[] = $part;
                    }
                } else {

                    // Check if it's a valid option name

                    if (in_array($part, $options)) {

                        $selections[] = $part;
                    } else {

                        $invalidSelections[] = $part;
                    }
                }
            }

            if (!empty($invalidSelections)) {

                $this->error("Invalid selections: " . implode(', ', $invalidSelections));

                $this->line("Valid options are: 0-" . (count($options) - 1) . " or their names: " . implode(', ', $options));

                $attempt++;

                continue;
            }

            if (empty($selections)) {

                $this->warn('No valid selections made.');

                if ($attempt < $maxRetries - 1) {

                    $attempt++;

                    continue;
                } else {

                    $this->warn('Using default: none');

                    return ['none'];
                }
            }

            // Display what was selected

            $selectedNames = array_map(function ($key) use ($descriptions) {

                return $descriptions[$key] ?? $key;
            }, $selections);

            $this->info('✅ Selected extension packs: ' . implode(', ', $selectedNames));

            return array_unique($selections);
        }

        $this->warn('Maximum attempts reached. Using default: none');

        return ['none'];
    }

    protected function promptForIndividualExtensions(): array
    {

        // Get additional extensions (not already in default extensions)

        $additionalExtensions = [];

        foreach ($this->availableExtensions as $ext => $info) {

            if (
                !in_array($ext, $this->defaultExtensions) &&

                in_array($this->selectedPhpVersion, $info['php_versions'])
            ) {

                $additionalExtensions[$ext] = $info['description'];
            }
        }

        if (empty($additionalExtensions)) {

            $this->warn('No additional extensions available for PHP ' . $this->selectedPhpVersion);

            return [];
        }

        $this->info('Available individual extensions (not in default or database packs):');

        $extensionKeys = array_keys($additionalExtensions);

        foreach ($extensionKeys as $index => $ext) {

            $this->line("  [{$index}] {$ext} - {$additionalExtensions[$ext]}");
        }

        $this->info('');

        $this->info('Select individual extensions by numbers separated by commas (e.g., 0,2,5)');

        $input = $this->ask('Select individual extensions (Enter for none)', '');

        if (empty(trim($input))) {

            return [];
        }

        $selections = [];

        $inputParts = array_map('trim', explode(',', $input));

        foreach ($inputParts as $part) {

            if (is_numeric($part)) {

                $index = (int) $part;

                if (isset($extensionKeys[$index])) {

                    $selections[] = $extensionKeys[$index];
                } else {

                    $this->warn("Invalid selection: {$part}. Skipping...");
                }
            }
        }

        if (!empty($selections)) {

            $this->info('Selected individual extensions: ' . implode(', ', $selections));
        }

        return $selections;
    }

    protected function displayLibraryExtensionMapping(): void
    {

        $this->line('Key library → extension mappings:');

        $this->line('  • openssl library → openssl extension (SSL/TLS support)');

        $this->line('  • curl/libcurl libraries → curl extension (HTTP client)');

        $this->line('  • sqlite library → sqlite3, pdo_sqlite extensions');

        $this->line('  • zlib library → zip, zlib extensions (compression)');

        $this->line('  • libxml2 library → dom, xml, simplexml extensions');

        $this->line('  • libpng, libjpeg libraries → gd extension (image processing)');

        $this->info('');
    }

    protected function getExtensionPack(string $pack): array
    {

        switch ($pack) {

            case 'web':

                return ['dom', 'xml', 'simplexml', 'gd'];

            case 'performance':

                return ['opcache', 'phar'];

            case 'processing':

                return ['iconv', 'ctype', 'bcmath'];

            case 'compression':

                return ['bz2'];

            case 'network':

                return ['sockets'];

            case 'soap':

                return ['soap'];

            default:

                return [];
        }
    }

    protected function displayBuildSummary(string $spcPath): void
    {

        $this->info('=== Build Summary ===');

        if ($this->buildHash !== '') {

            $this->info('Build key: ' . $this->buildHash);
        }

        if (!empty($this->buildMetadata['artifact_path'])) {

            $this->info('Artifact: ' . $this->buildMetadata['artifact_path']);

            if (!empty($this->buildMetadata['checksum'])) {

                $this->info('SHA-256: ' . $this->buildMetadata['checksum']);
            }
        }

        // Display version with clear resolution indicator
        if ($this->selectedPhpExactVersion !== '' && $this->selectedPhpExactVersion !== $this->selectedPhpVersion) {
            $this->info("PHP Version: {$this->selectedPhpVersion} → {$this->selectedPhpExactVersion} (latest patch)");
        } else {
            $summaryVersion = $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion;
            $this->info('PHP Version: ' . $summaryVersion);
        }

        $this->info('Default Extensions (always included): ' . implode(', ', $this->defaultExtensions));

        $userSelectedExtensions = array_diff($this->selectedExtensions, $this->defaultExtensions);

        if (!empty($userSelectedExtensions)) {

            $databaseExtensions = array_intersect($userSelectedExtensions, [

                'mysqli',

                'pdo_mysql',

                'pgsql',

                'pdo_pgsql',

                'sqlsrv',

                'pdo_sqlsrv',

            ]);

            $otherExtensions = array_diff($userSelectedExtensions, $databaseExtensions);

            if (!empty($databaseExtensions)) {

                $this->info('Additional Database Extensions: ' . implode(', ', $databaseExtensions));
            }

            if (!empty($otherExtensions)) {

                $this->info('Additional Other Extensions: ' . implode(', ', $otherExtensions));
            }
        } else {

            $this->info('Additional Extensions: None');
        }

        $phpBinaryPath = $spcPath . '/buildroot/bin/php.exe';

        if (file_exists($phpBinaryPath)) {

            $this->info("PHP Binary: {$phpBinaryPath}");

            $versionResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');

            if ($versionResult->successful()) {

                $this->info('PHP Version Output:');

                $this->line($versionResult->output());
            }

            $extensionsResult = Process::path($spcPath)->run('buildroot\bin\php.exe -m');

            if ($extensionsResult->successful()) {

                $this->info('Loaded Extensions:');

                $this->line($extensionsResult->output());
            }
        }
    }

    protected function ensureGitDependencies(string $spcPath): void
    {

        $sourcePath = $spcPath . '/source';

        $sourceJsonPath = $spcPath . '/config/source.json';

        // Read and parse source.json to find git dependencies

        if (!file_exists($sourceJsonPath)) {

            $this->warn('source.json not found, skipping git dependency check');

            return;
        }

        $sourceConfig = json_decode(file_get_contents($sourceJsonPath), true);

        if (!$sourceConfig) {

            $this->warn('Failed to parse source.json');

            return;
        }

        // Find all git-type dependencies

        $gitDependencies = [];

        foreach ($sourceConfig as $name => $config) {

            if (isset($config['type']) && $config['type'] === 'git') {

                $gitDependencies[$name] = $config;
            }
        }

        if (empty($gitDependencies)) {

            $this->info('No git dependencies found');

            return;
        }

        $this->info('Found ' . count($gitDependencies) . ' git dependencies');

        // Ensure source directory exists

        if (!file_exists($sourcePath)) {

            mkdir($sourcePath, 0755, true);
        }

        // Prioritize critical dependencies first

        $criticalDeps = ['libiconv-win', 'freetype'];

        $processedDeps = [];

        // Process critical dependencies first

        foreach ($criticalDeps as $critical) {

            if (isset($gitDependencies[$critical])) {

                $this->info("Processing critical dependency: {$critical}");

                $this->ensureGitDependency($sourcePath, $critical, $gitDependencies[$critical]);

                $processedDeps[] = $critical;
            }
        }

        // Process remaining dependencies (with timeout protection)

        foreach ($gitDependencies as $name => $config) {

            if (in_array($name, $processedDeps)) {

                continue; // Already processed

            }

            // Skip known problematic/large repositories that aren't essential for basic builds

            $skipForBasicBuild = ['grpc', 'protobuf', 'abseil-cpp', 'ext-glfw', 'pthreads4w'];

            if (in_array($name, $skipForBasicBuild)) {

                $this->warn("Skipping non-essential dependency for basic build: {$name}");

                continue;
            }

            try {

                $this->ensureGitDependency($sourcePath, $name, $config);
            } catch (\Exception $e) {

                $this->warn("Failed to process {$name}: " . $e->getMessage());

                $this->warn("Continuing without {$name} as it may not be essential for basic builds");
            }
        }
    }

    protected function ensureGitDependency(string $sourcePath, string $name, array $config): void
    {

        $targetPath = $sourcePath . '/' . $name;

        $url = $config['url'] ?? null;

        $rev = $config['rev'] ?? 'master';

        if (!$url) {

            $this->warn("No URL specified for git dependency: {$name}");

            return;
        }

        $this->info("Ensuring git dependency: {$name}");

        if (file_exists($targetPath)) {

            // Directory exists, check if it's a git repository

            if (file_exists($targetPath . '/.git')) {

                $this->info("  - {$name} already exists, updating...");

                // Try to update the repository

                $updateResult = Process::path($targetPath)->run('git pull origin ' . $rev);

                if ($updateResult->successful()) {

                    $this->info("  ✅ {$name} updated successfully");
                } else {

                    $this->warn("  ⚠️ Failed to update {$name}, but directory exists");
                }
            } else {

                $this->warn("  ⚠️ {$name} directory exists but is not a git repository");

                // Remove and re-clone

                Process::run('rm -rf "' . $targetPath . '"');

                $this->cloneGitDependency($url, $targetPath, $rev, $name);
            }
        } else {

            // Directory doesn't exist, clone it

            $this->info("  - Cloning {$name} from {$url}");

            $this->cloneGitDependency($url, $targetPath, $rev, $name);
        }
    }

    protected function cloneGitDependency(string $url, string $targetPath, string $rev, string $name): void
    {

        // Set timeout based on known problematic repositories

        $timeout = 300; // 5 minutes default

        $problemRepos = ['grpc', 'protobuf', 'abseil-cpp']; // Large repositories that take longer

        foreach ($problemRepos as $problemRepo) {

            if (strpos($name, $problemRepo) !== false || strpos($url, $problemRepo) !== false) {

                $timeout = 900; // 15 minutes for large repos

                $this->warn("  - {$name} is a large repository, using extended timeout ({$timeout}s)");

                break;
            }
        }

        // Clone the repository with specified timeout

        $cloneResult = Process::timeout($timeout)->run("git clone \"{$url}\" \"{$targetPath}\"");

        if (!$cloneResult->successful()) {

            if (strpos($cloneResult->errorOutput(), 'timeout') !== false) {

                $this->warn("  ⚠️ {$name} clone timed out after {$timeout}s, but this may not be critical for the build");

                return; // Don't throw exception for timeout, it might not be critical

            }

            throw new RuntimeException("Failed to clone {$name} from {$url}: " . $cloneResult->errorOutput());
        }

        // Checkout specific revision if not master/main

        if ($rev !== 'master' && $rev !== 'main') {

            $checkoutResult = Process::path($targetPath)->run("git checkout {$rev}");

            if (!$checkoutResult->successful()) {

                $this->warn("Failed to checkout {$rev} for {$name}, using default branch");
            }
        }

        $this->info("  ✅ {$name} cloned successfully");
    }

    protected function ensureTarBasedExtractions(string $spcPath): void
    {

        $sourcePath = $spcPath . '/source';

        $downloadsPath = $spcPath . '/downloads';

        $sourceJsonPath = $spcPath . '/config/source.json';

        if (!file_exists($sourceJsonPath)) {

            $this->warn('source.json not found, skipping tar extraction check');

            return;
        }

        $sourceConfig = json_decode(file_get_contents($sourceJsonPath), true);

        if (!$sourceConfig) {

            $this->warn('Failed to parse source.json');

            return;
        }

        // Find tar-based dependencies that might have failed extraction

        $tarDependencies = [];

        foreach ($sourceConfig as $name => $config) {

            if (isset($config['type']) && in_array($config['type'], ['url', 'ghrel'])) {

                $tarDependencies[$name] = $config;
            }
        }

        // Specifically handle nghttp2 and other critical tar-based dependencies

        $criticalTarDeps = ['nghttp2', 'libssh2', 'openssl', 'zlib', 'sqlite', 'bzip2', 'curl', 'libpng', 'libjpeg', 'libzip', 'xz', 'libwebp', 'libxml2', 'onig'];

        foreach ($criticalTarDeps as $depName) {

            if (!isset($tarDependencies[$depName])) {

                continue;
            }

            $targetPath = $sourcePath . '/' . $depName;

            // Skip if this dependency was cloned from GitHub/Git (no extraction needed)

            if ($this->isClonedFromGit($targetPath)) {

                $this->info("✅ {$depName} was cloned from repository, skipping tar extraction check");

                continue;
            }

            // Check if extraction is incomplete

            if (file_exists($targetPath)) {

                $contents = scandir($targetPath);

                $realContents = array_diff($contents, ['.', '..', 'build', '.spc-hash']);

                $isIncomplete = false;

                // If directory only contains build/ and .spc-hash (no actual source files), it's incomplete

                if (count($realContents) === 0) {

                    $this->warn("{$depName} directory exists but contains no source files (only build/.spc-hash)");

                    $isIncomplete = true;
                }

                // Check for essential files based on dependency type

                if (!$isIncomplete) {

                    $expectedFiles = $this->getExpectedFilesForDependency($depName);

                    if (!empty($expectedFiles)) {

                        $missingFiles = [];

                        foreach ($expectedFiles as $expectedFile) {

                            if (!file_exists($targetPath . '/' . $expectedFile)) {

                                $missingFiles[] = $expectedFile;
                            }
                        }

                        if (!empty($missingFiles)) {

                            $this->warn("Missing essential files for {$depName}: " . implode(', ', $missingFiles));

                            $isIncomplete = true;
                        }
                    }
                }

                if ($isIncomplete) {

                    $this->warn("Detected incomplete extraction for {$depName}, re-extracting...");

                    $this->reExtractTarDependency($downloadsPath, $sourcePath, $depName);
                }
            } else {

                // Directory doesn't exist, try to extract if archive is available

                $this->info("Missing {$depName} source, attempting extraction...");

                $this->reExtractTarDependency($downloadsPath, $sourcePath, $depName);
            }
        }
    }

    protected function isClonedFromGit(string $targetPath): bool
    {

        // Check for our marker files that indicate the source was cloned

        return file_exists($targetPath . '/.cloned-from-github') ||

            file_exists($targetPath . '/.cloned-from-git') ||

            (file_exists($targetPath . '/.git') && !file_exists($targetPath . '/.spc-hash'));
    }

    protected function reExtractTarDependency(string $downloadsPath, string $sourcePath, string $depName): void
    {

        // Extract spcPath from sourcePath (remove trailing /source)

        $spcPath = dirname($sourcePath);

        $targetPath = $sourcePath . '/' . $depName;

        // Remove incomplete directory if it exists

        if (file_exists($targetPath)) {

            $this->info("  - Removing incomplete {$depName} directory...");

            Process::run('rm -rf "' . $targetPath . '"');
        }

        // Look for tar archive in downloads

        $possibleArchives = [

            $depName . '*.tar.xz',

            $depName . '*.tar.gz',

            $depName . '*.tgz'

        ];

        // Special patterns for some dependencies

        if ($depName === 'sqlite') {

            $possibleArchives = array_merge($possibleArchives, [

                'sqlite-autoconf*.tar.gz',

                'sqlite-autoconf*.tar.xz'

            ]);
        } elseif ($depName === 'libjpeg') {

            $possibleArchives = array_merge($possibleArchives, [

                'libjpeg-turbo*.tar.gz',

                'libjpeg-turbo*.tar.xz'

            ]);
        } elseif ($depName === 'libwebp') {

            // CRITICAL: Don't use generic v*.tar.gz here - it will match ANY version
            // Instead, use specific version patterns in the fallback logic below
            $possibleArchives = array_merge($possibleArchives, [

                'libwebp-*.tar.gz',

                'libwebp-*.tar.xz'

            ]);
        } elseif ($depName === 'libxml2') {

            // CRITICAL: Don't use generic v*.tar.gz here - it will match ANY version
            // Instead, use specific version patterns in the fallback logic below
            $possibleArchives = array_merge($possibleArchives, [

                'libxml2-v*.tar.gz',

                'libxml2-*.tar.gz',

                'libxml2-*.tar.xz'

            ]);
        }

        $archiveFile = null;

        foreach ($possibleArchives as $pattern) {

            $matches = glob($downloadsPath . '/' . $pattern);

            if (!empty($matches)) {

                $archiveFile = $matches[0]; // Use the first match

                $this->info("  - Found archive: " . basename($archiveFile) . " using pattern: {$pattern}");

                break;
            }
        }

        // Special handling for dependencies with version-only names
        // These libraries use version tags as filenames (v1.3.2.tar.gz, v2.12.5.tar.gz)

        if (!$archiveFile) {

            // Check for version-only archives (like v1.3.2.tar.gz for libwebp, v2.12.5.tar.gz for libxml2)

            $versionArchives = glob($downloadsPath . '/v*.tar.gz');

            if (!empty($versionArchives)) {

                // Try to match based on common version patterns

                foreach ($versionArchives as $versionArchive) {

                    $basename = basename($versionArchive);

                    // libwebp uses v1.x.x pattern
                    if ($depName === 'libwebp' && preg_match('/^v1\.\d+\.\d+\.tar\.gz$/', $basename)) {

                        $archiveFile = $versionArchive;

                        $this->info("  - Found libwebp archive: {$basename}");

                        break;
                    }
                    // libxml2 uses v2.x.x pattern
                    elseif ($depName === 'libxml2' && preg_match('/^v2\.\d+\.\d+\.tar\.gz$/', $basename)) {

                        $archiveFile = $versionArchive;

                        $this->info("  - Found libxml2 archive: {$basename}");

                        break;
                    }
                }
            }
        }

        if (!$archiveFile || !file_exists($archiveFile)) {

            $this->warn("  - No archive found for {$depName} in downloads directory");

            return;
        }

        $this->info("  - Extracting {$depName} from " . basename($archiveFile));

        // Create target directory first

        if (!file_exists($targetPath)) {

            mkdir($targetPath, 0755, true);
        }

        // CRITICAL: Use proper two-step extraction for nested tar archives
        // Problem: tar on Windows with "Cannot connect to C:" means it's trying to pipe
        // Solution: Extract in two steps to avoid piping issues

        $extractionSuccess = false;

        if (str_ends_with($archiveFile, '.tar.xz') || str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {
            $extractionSuccess = $this->extractNestedTarArchive($archiveFile, $targetPath, $depName);
        }

        if ($extractionSuccess) {

            $this->info("  ✅ {$depName} extracted successfully");

            $this->verifyTarExtraction($targetPath, $depName);

            // Create hash file to prevent re-extraction

            $this->createSourceHashFile($spcPath, $depName, $archiveFile);
        } else {

            $this->warn("  ⚠️ Failed to extract {$depName} with primary method");

            // Try with improved tar command for Windows

            $this->info("  - Trying Windows-optimized tar extraction...");

            $windowsOptimizedResult = $this->tryWindowsOptimizedExtraction($archiveFile, $targetPath, $depName, $spcPath);

            if (!$windowsOptimizedResult) {

                // Try with 7-zip if available (Windows fallback)

                $this->info("  - Trying 7-zip extraction method...");

                $sevenZipResult = $this->trySevenZipExtraction($archiveFile, $targetPath, $depName, $spcPath);

                if (!$sevenZipResult) {

                    // Try PowerShell extraction for .tar.gz files

                    $powerShellResult = $this->tryPowerShellExtraction($archiveFile, $targetPath, $depName);

                    if (!$powerShellResult) {

                        // Final fallback: try without strip-components

                        $this->info("  - Trying basic tar extraction...");

                        // Convert Windows paths for tar command
                        $winArchiveFile = str_replace('\\', '/', $archiveFile);
                        $winTargetPath = str_replace('\\', '/', $targetPath);

                        $basicCmd = '';

                        if (str_ends_with($archiveFile, '.tar.xz')) {

                            $basicCmd = "tar -xf \"{$winArchiveFile}\" -C \"{$winTargetPath}\"";
                        } elseif (str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {

                            $basicCmd = "tar -xzf \"{$winArchiveFile}\" -C \"{$winTargetPath}\"";
                        }

                        if (!empty($basicCmd)) {

                            $basicResult = Process::env([

                                'PATH' => getenv('PATH')

                            ])->run($basicCmd);

                            if ($basicResult->successful()) {

                                $this->info("  ✅ {$depName} extracted with basic method");

                                $this->handleBasicExtractionCleanup($targetPath, $depName);

                                $this->verifyTarExtraction($targetPath, $depName);

                                // Create hash file to prevent re-extraction

                                $this->createSourceHashFile($spcPath, $depName, $archiveFile);
                            } else {

                                $this->error("  ❌ All extraction methods failed for {$depName}");
                            }
                        }
                    }
                }
            }
        }
    }

    protected function tryWindowsOptimizedExtraction(string $archiveFile, string $targetPath, string $depName, string $spcPath): bool
    {

        // Convert to Unix-style paths to avoid MSYS2 path conversion issues

        $unixArchiveFile = str_replace('\\', '/', $archiveFile);

        $unixTargetPath = str_replace('\\', '/', $targetPath);

        // Convert Windows drive letters to Unix format (C:/ -> /c/)

        if (preg_match('/^([A-Za-z]):\/(.*)/', $unixArchiveFile, $matches)) {

            $unixArchiveFile = '/' . strtolower($matches[1]) . '/' . $matches[2];
        }

        if (preg_match('/^([A-Za-z]):\/(.*)/', $unixTargetPath, $matches)) {

            $unixTargetPath = '/' . strtolower($matches[1]) . '/' . $matches[2];
        }

        $this->info("  - Using Unix-style paths: {$unixArchiveFile} -> {$unixTargetPath}");

        // Use appropriate extraction command with Unix paths

        $extractCmd = '';

        if (str_ends_with($archiveFile, '.tar.xz')) {

            $extractCmd = "tar -xf '{$unixArchiveFile}' -C '{$unixTargetPath}' --strip-components=1";
        } elseif (str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {

            $extractCmd = "tar -xzf '{$unixArchiveFile}' -C '{$unixTargetPath}' --strip-components=1";
        }

        if (empty($extractCmd)) {

            return false;
        }

        $result = Process::env([

            'PATH' => getenv('PATH')

        ])->run($extractCmd);

        if ($result->successful()) {

            $this->info("  ✅ {$depName} extracted with Windows-optimized method");

            $this->verifyTarExtraction($targetPath, $depName);

            // Create hash file to prevent re-extraction

            $this->createSourceHashFile($spcPath, $depName, $archiveFile);

            return true;
        } else {

            $this->warn("  ⚠️ Windows-optimized extraction failed: " . $result->errorOutput());

            return false;
        }
    }

    protected function verifyTarExtraction(string $targetPath, string $depName): void
    {

        if (!file_exists($targetPath)) {

            $this->warn("  ⚠️ {$depName} directory not found after extraction");

            return;
        }

        $contents = scandir($targetPath);

        $realContents = array_diff($contents, ['.', '..']);

        // Check for expected files based on dependency type

        $expectedFiles = $this->getExpectedFilesForDependency($depName);

        if (empty($expectedFiles)) {

            // Generic check - should have more than just .spc-hash and build

            if (count($realContents) > 2) {

                $this->info("  ✅ {$depName} appears to be properly extracted");

                return;
            }
        } else {

            $missingFiles = [];

            foreach ($expectedFiles as $expectedFile) {

                if (!in_array($expectedFile, $realContents) && !file_exists($targetPath . '/' . $expectedFile)) {

                    $missingFiles[] = $expectedFile;
                }
            }

            if (empty($missingFiles)) {

                $this->info("  ✅ {$depName} extraction verified - all expected files present");
            } else {

                $this->warn("  ⚠️ {$depName} may be incomplete - missing: " . implode(', ', $missingFiles));
            }
        }
    }

    protected function trySevenZipExtraction(string $archiveFile, string $targetPath, string $depName, string $spcPath): bool
    {

        // Check if 7-zip is available

        $sevenZipPaths = [

            'C:\Program Files\7-Zip\7z.exe',

            'C:\Program Files (x86)\7-Zip\7z.exe',

            '7z' // If in PATH

        ];

        $sevenZipPath = null;

        foreach ($sevenZipPaths as $path) {

            if ($path === '7z' || file_exists($path)) {

                $sevenZipPath = $path;

                break;
            }
        }

        if (!$sevenZipPath) {

            $this->warn("  - 7-zip not found, skipping 7-zip extraction");

            return false;
        }

        $this->info("  - Using 7-zip for extraction...");

        $sevenZipResult = Process::run("\"{$sevenZipPath}\" x \"{$archiveFile}\" -o\"{$targetPath}\" -y");

        if ($sevenZipResult->successful()) {

            $this->info("  ✅ {$depName} extracted with 7-zip");

            $this->handleBasicExtractionCleanup($targetPath, $depName);

            $this->verifyTarExtraction($targetPath, $depName);

            // Create hash file to prevent re-extraction

            $this->createSourceHashFile($spcPath, $depName, $archiveFile);

            return true;
        } else {

            $this->warn("  ⚠️ 7-zip extraction failed: " . $sevenZipResult->errorOutput());

            return false;
        }
    }

    protected function tryPowerShellExtraction(string $archiveFile, string $targetPath, string $depName): bool
    {

        $this->info("  - Trying PowerShell extraction...");

        // PowerShell command to extract archives

        if (str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {

            // For .tar.gz files, we need to extract the .gz first, then the .tar

            $tempTarFile = $targetPath . '\\' . basename($archiveFile, '.gz');

            $psCmd = "powershell -Command \"" .

                "Add-Type -AssemblyName System.IO.Compression.FileSystem; " .

                "\$gzStream = [System.IO.File]::OpenRead('{$archiveFile}'); " .

                "\$tarStream = [System.IO.File]::Create('{$tempTarFile}'); " .

                "\$gzipStream = New-Object System.IO.Compression.GzipStream(\$gzStream, [System.IO.Compression.CompressionMode]::Decompress); " .

                "\$gzipStream.CopyTo(\$tarStream); " .

                "\$gzipStream.Close(); \$tarStream.Close(); \$gzStream.Close(); " .

                "Write-Host 'Decompressed to {$tempTarFile}'\"";

            $result = Process::run($psCmd);

            if ($result->successful() && file_exists($tempTarFile)) {

                // Now extract the .tar file using tar

                $tarResult = Process::run("tar -xf \"{$tempTarFile}\" -C \"{$targetPath}\" --strip-components=1");

                // Clean up temp file

                unlink($tempTarFile);

                if ($tarResult->successful()) {

                    $this->info("  ✅ {$depName} extracted with PowerShell + tar");

                    return true;
                }
            }
        } elseif (str_ends_with($archiveFile, '.tar.xz')) {

            // For .tar.xz files, try using PowerShell with external tools or fall back to basic methods

            $this->warn("  - PowerShell extraction for .tar.xz not implemented, falling back...");

            return false;
        }

        return false;
    }

    protected function handleBasicExtractionCleanup(string $targetPath, string $depName): void
    {

        // When using basic extraction without --strip-components=1, we might get nested directories

        // Try to flatten the structure if there's only one top-level directory

        $contents = scandir($targetPath);

        $realContents = array_diff($contents, ['.', '..']);

        if (count($realContents) === 1) {

            $singleDir = $targetPath . DIRECTORY_SEPARATOR . reset($realContents);

            if (is_dir($singleDir)) {

                $this->info("  - Flattening directory structure for {$depName}...");

                // Move all contents from the nested directory to the target directory

                $nestedContents = scandir($singleDir);

                foreach ($nestedContents as $item) {

                    if ($item === '.' || $item === '..')

                        continue;

                    $sourcePath = $singleDir . DIRECTORY_SEPARATOR . $item;

                    $destPath = $targetPath . DIRECTORY_SEPARATOR . $item;

                    // Use move command

                    if (PHP_OS_FAMILY === 'Windows') {

                        Process::run("move \"{$sourcePath}\" \"{$destPath}\"");
                    } else {

                        Process::run("mv \"{$sourcePath}\" \"{$destPath}\"");
                    }
                }

                // Remove the empty nested directory

                if (PHP_OS_FAMILY === 'Windows') {

                    Process::run("rmdir \"{$singleDir}\"");
                } else {

                    Process::run("rmdir \"{$singleDir}\"");
                }
            }
        }
    }

    /**
     * Extract nested tar archives (tar.gz, tar.xz) in two steps to avoid Windows piping issues
     * This fixes the "Cannot connect to C:" error that occurs when tar tries to pipe decompression
     */
    protected function extractNestedTarArchive(string $archiveFile, string $targetPath, string $depName): bool
    {
        $this->info("  - Using Python-based extraction (Windows-compatible)...");

        // PRIORITY 1: Try Python extraction script (most reliable on Windows)
        $pythonScript = __DIR__ . '/extract_php_source.py';

        if (file_exists($pythonScript)) {
            $this->line("  - Extracting with Python script...");

            // Use Windows-native paths for Python
            $winArchiveFile = str_replace('/', '\\', $archiveFile);
            $winTargetPath = str_replace('/', '\\', $targetPath);

            // Try python3 first, then python, then py
            $pythonCommands = ['python3', 'python', 'py'];
            $pythonSuccess = false;

            foreach ($pythonCommands as $pythonCmd) {
                // Check if Python command is available
                $checkCmd = "where {$pythonCmd}";
                $checkResult = Process::run($checkCmd);

                if (!$checkResult->successful()) {
                    $this->line("  - {$pythonCmd} not found, trying next...");
                    continue;
                }

                $this->line("  - Using {$pythonCmd}: " . trim($checkResult->output()));

                $extractCmd = "\"{$pythonCmd}\" \"{$pythonScript}\" \"{$winArchiveFile}\" \"{$winTargetPath}\" 1";

                $extractResult = Process::timeout(300)
                    ->run($extractCmd);

                if ($extractResult->successful()) {
                    $pythonSuccess = true;
                    $this->line("  ✅ Extracted with {$pythonCmd}");
                    $this->line($extractResult->output());
                    break;
                } else {
                    $this->warn("  ⚠️ {$pythonCmd} failed: " . $extractResult->errorOutput());
                }
            }

            if ($pythonSuccess) {
                // Verify essential files are present
                $expectedFiles = $this->getExpectedFilesForDependency($depName);
                if (!empty($expectedFiles)) {
                    $allPresent = true;
                    foreach ($expectedFiles as $file) {
                        if (!file_exists($targetPath . DIRECTORY_SEPARATOR . $file)) {
                            $this->warn("  ⚠️ Missing expected file: {$file}");
                            $allPresent = false;
                        }
                    }

                    if ($allPresent) {
                        $this->info("  ✅ All expected files verified for {$depName}");
                        return true;
                    }
                } else {
                    $this->info("  ✅ Extraction complete");
                    return true;
                }
            } else {
                $this->warn("  ⚠️ Python extraction failed, trying fallback methods...");
            }
        } else {
            $this->warn("  ⚠️ Python script not found at: {$pythonScript}");
        }

        // PRIORITY 2: Try 7-Zip extraction (if available)
        $this->line("  - Trying 7-Zip extraction...");
        $sevenZipPaths = [
            'C:\Program Files\7-Zip\7z.exe',
            'C:\Program Files (x86)\7-Zip\7z.exe',
            '7z' // If in PATH
        ];

        $sevenZipPath = null;
        foreach ($sevenZipPaths as $path) {
            if ($path === '7z' || file_exists($path)) {
                $sevenZipPath = $path;
                break;
            }
        }

        if ($sevenZipPath) {
            // 7z can extract tar.gz and tar.xz directly with full extraction
            $extractCmd = "\"{$sevenZipPath}\" x \"{$archiveFile}\" -o\"{$targetPath}\" -y";
            $sevenZipResult = Process::timeout(300)->run($extractCmd);

            if ($sevenZipResult->successful()) {
                $this->line("  ✅ Extracted with 7-Zip");
                $this->handleBasicExtractionCleanup($targetPath, $depName);

                // Verify extraction
                $expectedFiles = $this->getExpectedFilesForDependency($depName);
                if (!empty($expectedFiles)) {
                    $allPresent = true;
                    foreach ($expectedFiles as $file) {
                        if (!file_exists($targetPath . DIRECTORY_SEPARATOR . $file)) {
                            $allPresent = false;
                            break;
                        }
                    }
                    if ($allPresent) {
                        $this->info("  ✅ All expected files verified for {$depName}");
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        $this->warn("  ❌ All extraction methods failed for {$depName}");
        return false;
    }

    protected function normalizePackOptions($packs): array
    {

        if (is_string($packs) && $packs !== '') {

            $packs = [$packs];
        }

        if (!is_array($packs)) {

            return [];
        }

        $normalized = [];

        foreach ($packs as $pack) {

            if (!is_string($pack)) {

                continue;
            }

            $pack = strtolower(trim($pack));

            if ($pack === '') {

                continue;
            }

            $normalized[] = $pack;
        }

        $normalized = array_values(array_unique($normalized));

        sort($normalized);

        return $normalized;
    }

    protected function normalizeBuildFlags($flags): array
    {

        if (is_string($flags) && $flags !== '') {

            $flags = [$flags];
        }

        if (!is_array($flags)) {

            return [];
        }

        $normalized = [];

        foreach ($flags as $flag) {

            if (!is_string($flag)) {

                continue;
            }

            $flag = trim($flag);

            if ($flag === '') {

                continue;
            }

            $normalized[] = $flag;
        }

        return array_values(array_unique($normalized));
    }

    protected function resolveExtensionPacks(array $packs): array
    {

        $resolved = [];

        foreach ($packs as $pack) {

            $resolved = array_merge($resolved, $this->getExtensionPack($pack));
        }

        $resolved = array_values(array_unique($resolved));

        sort($resolved);

        return $resolved;
    }

    protected function resolveLockfilePath(?string $path): string
    {

        $path = $path ?: '.nativephp-ext.lock';

        $isAbsolute = preg_match('/^(?:[A-Za-z]:\\\\|\\/)/', $path) === 1;

        if (!$isAbsolute) {

            $path = base_path($path);
        }

        return $path;
    }

    protected function loadLockfile(): void
    {

        if ($this->lockfilePath === '' || !file_exists($this->lockfilePath)) {

            $this->lockfileData = [];

            return;
        }

        $contents = @file_get_contents($this->lockfilePath);

        if ($contents === false) {

            $this->warn('Unable to read lockfile at ' . $this->lockfilePath);

            $this->lockfileData = [];

            return;
        }

        $data = json_decode($contents, true);

        if (!is_array($data)) {

            $this->warn('Lockfile is invalid JSON. Ignoring existing file.');

            $this->lockfileData = [];

            return;
        }

        $this->lockfileData = $data;
    }

    protected function writeLockfile(): void
    {

        if ($this->lockfilePath === '') {

            return;
        }

        $directory = dirname($this->lockfilePath);

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {

            $this->warn('Unable to create directory for lockfile: ' . $directory);

            return;
        }

        $payload = [

            'php_version' => $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion,

            'base_version' => $this->selectedPhpVersion,

            'extensions' => $this->selectedExtensions,

            'packs' => $this->requestedPacks,

            'profile' => $this->buildProfile,

            'build_flags' => $this->additionalBuildFlags,

            'build_key' => $this->buildHash,

            'updated_at' => date('c'),

        ];

        file_put_contents(

            $this->lockfilePath,

            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL

        );
    }

    protected function calculateBuildMatrix(): void
    {

        $extensions = $this->selectedExtensions;

        sort($extensions);

        $this->buildMatrix = [

            'php_version' => $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion,

            'php_base_version' => $this->selectedPhpVersion,

            'os_family' => PHP_OS_FAMILY,

            'os_name' => php_uname('s'),

            'architecture' => php_uname('m'),

            'profile' => $this->buildProfile,

            'packs' => $this->requestedPacks,

            'extensions' => $extensions,

            'build_flags' => $this->additionalBuildFlags,

            'mode' => $this->option('mode') ?? 'nativephp',

        ];
    }

    protected function computeBuildHash(array $matrix): string
    {

        return hash('sha256', json_encode($matrix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    protected function resolveCacheDirectory(?string $override): string
    {

        $directory = $override;

        if ($directory === null || $directory === '') {

            if (function_exists('storage_path')) {

                $directory = storage_path('app/nativephp-ext-cache');
            } else {

                $directory = base_path('storage/nativephp-ext-cache');
            }
        } elseif (!preg_match('/^(?:[A-Za-z]:\\\\|\\/)/', $directory)) {

            $directory = base_path($directory);
        }

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {

            throw new RuntimeException('Unable to create cache directory: ' . $directory);
        }

        foreach (['artifacts', 'meta', 'reports', 'locks'] as $subdir) {

            $path = $directory . DIRECTORY_SEPARATOR . $subdir;

            if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {

                throw new RuntimeException('Unable to create cache subdirectory: ' . $path);
            }
        }

        return $directory;
    }

    protected function checkCacheForBuild(bool $silent = false): bool
    {

        if ($this->cacheDirectory === null || $this->buildHash === '') {

            return false;
        }

        $metaPath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'meta' . DIRECTORY_SEPARATOR . $this->buildHash . '.json';

        if (!file_exists($metaPath)) {

            return false;
        }

        $metadata = json_decode((string) file_get_contents($metaPath), true);

        if (!is_array($metadata)) {

            return false;
        }

        $artifactPath = $metadata['artifact_path'] ?? '';

        if (!is_string($artifactPath) || $artifactPath === '' || !file_exists($artifactPath)) {

            return false;
        }

        if (!empty($metadata['checksum'])) {

            $checksum = hash_file('sha256', $artifactPath);

            if ($checksum !== $metadata['checksum']) {

                $this->warn('Cached artifact checksum mismatch. Ignoring cache entry.');

                return false;
            }
        }

        $this->cacheHit = true;

        $this->cachedArtifactPath = $artifactPath;

        $this->buildMetadata = $metadata;

        if (!$silent) {

            $this->info('Cache metadata located for build key ' . $this->buildHash . '.');
        }

        return true;
    }

    protected function announceCacheHit(): void
    {

        $this->cacheHit = true;

        $this->info('✅ Cache hit for build key ' . $this->buildHash . '.');

        if (!empty($this->buildMetadata['artifact_path'])) {

            $this->info('Reusing artifact at ' . $this->buildMetadata['artifact_path']);
        }
    }

    protected function acquireBuildLock(): void
    {

        if ($this->cacheDirectory === null || $this->buildHash === '') {

            return;
        }

        $lockPath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'locks' . DIRECTORY_SEPARATOR . $this->buildHash . '.lock';

        $handle = fopen($lockPath, 'c');

        if ($handle === false) {

            throw new RuntimeException('Unable to open lock file: ' . $lockPath);
        }

        $this->lockHandle = $handle;

        if (!flock($this->lockHandle, LOCK_EX)) {

            fclose($this->lockHandle);

            $this->lockHandle = null;

            throw new RuntimeException('Unable to acquire build lock for key: ' . $this->buildHash);
        }
    }

    protected function releaseBuildLock(): void
    {

        if ($this->lockHandle === null) {

            return;
        }

        flock($this->lockHandle, LOCK_UN);

        fclose($this->lockHandle);

        $this->lockHandle = null;
    }

    protected function packageBuildArtifacts(string $spcPath): ?array
    {

        if ($this->cacheDirectory === null) {

            return null;
        }

        $buildroot = $spcPath . '/buildroot';

        if (!is_dir($buildroot)) {

            $this->warn('Buildroot directory not found; skipping packaging step.');

            return null;
        }

        $whitelist = $this->getExtensionFileWhitelist();

        $files = $this->gatherPackageFiles($buildroot, $whitelist);

        if (empty($files)) {

            $this->warn('No build artifacts found to package.');

            return null;
        }

        $useZip = PHP_OS_FAMILY === 'Windows' || !class_exists('PharData');

        $artifactExtension = $useZip ? '.zip' : '.tar.gz';

        $artifactDir = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'artifacts';

        $artifactPath = $artifactDir . DIRECTORY_SEPARATOR . $this->buildHash . $artifactExtension;

        if ($useZip) {

            $zip = new ZipArchive();

            if ($zip->open($artifactPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {

                throw new RuntimeException('Unable to create ZIP artifact at ' . $artifactPath);
            }

            foreach ($files as $relative => $absolute) {

                if (is_dir($absolute)) {

                    $zip->addEmptyDir($relative);

                    continue;
                }

                $zip->addFile($absolute, $relative);
            }

            $zip->close();
        } else {

            $tarPath = $artifactDir . DIRECTORY_SEPARATOR . $this->buildHash . '.tar';

            if (file_exists($tarPath)) {

                unlink($tarPath);
            }

            $phar = new \PharData($tarPath);

            foreach ($files as $relative => $absolute) {

                if (is_dir($absolute)) {

                    $phar->addEmptyDir($relative);
                } else {

                    $phar->addFile($absolute, $relative);
                }
            }

            $phar->compress(\Phar::GZ);

            unset($phar);

            if (file_exists($tarPath)) {

                unlink($tarPath);
            }

            $artifactPath = $tarPath . '.gz';
        }

        if (!file_exists($artifactPath)) {

            $this->warn('Expected artifact file was not created: ' . $artifactPath);

            return null;
        }

        $checksum = hash_file('sha256', $artifactPath);

        $metadata = [

            'artifact_path' => $artifactPath,

            'checksum' => $checksum,

            'size' => filesize($artifactPath),

            'profile' => $this->buildProfile,

            'packaged_files' => array_keys($files),

        ];

        $reportDir = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'reports';

        $reportPath = $reportDir . DIRECTORY_SEPARATOR . $this->buildHash . '.json';

        file_put_contents($reportPath, json_encode([

            'build_key' => $this->buildHash,

            'matrix' => $this->buildMatrix,

            'files' => array_keys($files),

            'artifact' => $artifactPath,

            'checksum' => $checksum,

            'size' => $metadata['size'],

            'profile' => $this->buildProfile,

            'created_at' => date('c'),

        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $metadata;
    }

    protected function gatherPackageFiles(string $buildroot, array $extensionWhitelist): array
    {

        $buildroot = rtrim(str_replace('\\', '/', $buildroot), '/');

        $targets = [

            'bin/php',

            'bin/php.exe',

            'php.ini',

            'php.ini-development',

            'php.ini-production',

            'conf.d',

            'ext',

            'lib/php/extensions',

        ];

        $files = [];

        foreach ($targets as $target) {

            $absolute = $buildroot . '/' . $target;

            if (!file_exists($absolute)) {

                continue;
            }

            if (is_dir($absolute)) {

                $iterator = new \RecursiveIteratorIterator(

                    new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS),

                    \RecursiveIteratorIterator::SELF_FIRST

                );

                foreach ($iterator as $item) {

                    $relative = substr(str_replace('\\', '/', $item->getPathname()), strlen($buildroot) + 1);

                    if ($this->shouldIncludePathInPackage($relative, $item->isDir(), $extensionWhitelist)) {

                        if ($item->isDir()) {

                            $files[$relative] = $item->getPathname();
                        } else {

                            $files[$relative] = $item->getPathname();
                        }
                    }
                }
            } else {

                $relative = substr(str_replace('\\', '/', $absolute), strlen($buildroot) + 1);

                $files[$relative] = $absolute;
            }
        }

        ksort($files);

        return $files;
    }

    protected function shouldIncludePathInPackage(string $relativePath, bool $isDir, array $extensionWhitelist): bool
    {

        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '') {

            return false;
        }

        if ($relativePath === 'conf.d' || str_starts_with($relativePath, 'conf.d/')) {

            return true;
        }

        if (in_array($relativePath, ['php.ini', 'php.ini-development', 'php.ini-production'], true)) {

            return true;
        }

        if (str_starts_with($relativePath, 'bin/php')) {

            return !$isDir;
        }

        if (
            $relativePath === 'ext' || str_starts_with($relativePath, 'ext/') ||

            $relativePath === 'lib/php/extensions' || str_starts_with($relativePath, 'lib/php/extensions')
        ) {

            if ($isDir) {

                return true;
            }

            if (empty($extensionWhitelist)) {

                return true;
            }

            return in_array(strtolower(basename($relativePath)), $extensionWhitelist, true);
        }

        return false;
    }

    protected function getExtensionFileWhitelist(): array
    {

        if ($this->buildProfile === 'full') {

            return [];
        }

        $whitelist = [];

        foreach ($this->selectedExtensions as $extension) {

            $extension = strtolower($extension);

            $whitelist[] = "php_{$extension}.dll";

            $whitelist[] = "php_{$extension}.so";

            $whitelist[] = "{$extension}.dll";

            $whitelist[] = "{$extension}.so";
        }

        return array_values(array_unique($whitelist));
    }

    protected function storeBuildMetadata(array $metadata): void
    {

        if ($this->cacheDirectory === null) {

            return;
        }

        $metaDir = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'meta';

        $metadata = array_merge([

            'build_key' => $this->buildHash,

            'matrix' => $this->buildMatrix,

            'profile' => $this->buildProfile,

            'packs' => $this->requestedPacks,

            'build_flags' => $this->additionalBuildFlags,

        ], $metadata);

        $metadata['updated_at'] = date('c');

        file_put_contents($metaDir . DIRECTORY_SEPARATOR . $this->buildHash . '.json', json_encode(

            $metadata,

            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES

        ));

        $this->buildMetadata = $metadata;

        $this->cachedArtifactPath = $metadata['artifact_path'] ?? null;
    }

    protected function emitJsonSummary(string $status, array $extra = []): void
    {

        if (!$this->jsonOutput) {

            return;
        }

        $payload = array_merge([

            'status' => $status,

            'build_key' => $this->buildHash,

            'php_version' => $this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion,

            'extensions' => $this->selectedExtensions,

            'packs' => $this->requestedPacks,

            'profile' => $this->buildProfile,

            'build_flags' => $this->additionalBuildFlags,

            'cache_hit' => $this->cacheHit,

            'dry_run' => $this->dryRun,

            'artifact' => $this->cachedArtifactPath,

            'checksum' => $this->buildMetadata['checksum'] ?? null,

            'lockfile' => $this->lockfilePath,

        ], $extra);

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function outputResolvedConfiguration(): void
    {

        $this->info('=== Build Configuration ===');

        $this->info('Build key: ' . $this->buildHash);

        $this->info('PHP version: ' . ($this->selectedPhpExactVersion !== '' ? $this->selectedPhpExactVersion : $this->selectedPhpVersion));

        $this->info('Profile: ' . ucfirst($this->buildProfile));

        if (!empty($this->requestedPacks)) {

            $this->info('Extension packs: ' . implode(', ', $this->requestedPacks));
        }

        $this->info('Extensions (' . count($this->selectedExtensions) . '): ' . implode(', ', $this->selectedExtensions));

        if (!empty($this->additionalBuildFlags)) {

            $this->info('Additional build flags: ' . implode(' ', $this->additionalBuildFlags));
        }

        if (!empty($this->buildMetadata['artifact_path'])) {

            $this->info('Cached artifact: ' . $this->buildMetadata['artifact_path']);
        }
    }

    protected function deployToNativePHP(): void
    {

        // Check if NativePHP php-bin package is installed

        $nativePHPPath = base_path('vendor/nativephp/php-bin/bin');

        if (!is_dir($nativePHPPath)) {

            $this->line('ℹ️  NativePHP php-bin package not found, skipping deployment');

            return;
        }

        // Determine system PHP version for deployment filename

        $phpMajorMinor = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        // Determine platform-specific path

        $platform = match (PHP_OS_FAMILY) {

            'Windows' => 'win',

            'Darwin' => 'mac',

            'Linux' => 'linux',

            default => null
        };

        $arch = match (php_uname('m')) {

            'x86_64', 'AMD64' => 'x64',

            'arm64', 'aarch64' => 'arm64',

            default => 'x64'
        };

        if (!$platform) {

            $this->warn('⚠️  Unknown platform, skipping NativePHP deployment');

            return;
        }

        $targetDir = base_path("vendor/nativephp/php-bin/bin/{$platform}/{$arch}");

        if (!is_dir($targetDir)) {

            $this->warn("⚠️  NativePHP target directory not found: {$targetDir}");

            return;
        }

        // Get the artifact path

        if (empty($this->buildMetadata['artifact_path'])) {

            $this->warn('⚠️  No artifact path found, skipping NativePHP deployment');

            return;
        }

        $artifactPath = $this->buildMetadata['artifact_path'];

        if (!file_exists($artifactPath)) {

            $this->warn("⚠️  Artifact not found at: {$artifactPath}");

            return;
        }

        // Deploy with PHP version naming convention

        $targetFile = "{$targetDir}/php-{$phpMajorMinor}.zip";

        $this->info("📦 Deploying custom PHP binary to NativePHP...");

        $this->line("   Source: {$artifactPath}");

        $this->line("   Target: {$targetFile}");

        // Backup original if it exists

        if (file_exists($targetFile)) {

            $backupFile = "{$targetDir}/php-{$phpMajorMinor}-original.zip";

            if (!file_exists($backupFile)) {

                copy($targetFile, $backupFile);

                $this->line("   Backed up original to: {$backupFile}");
            }
        }

        // Copy artifact

        if (copy($artifactPath, $targetFile)) {

            $this->info("✅ Successfully deployed to NativePHP!");

            $this->line("   NativePHP will now use your custom PHP binary with MySQL support");
        } else {

            $this->error("❌ Failed to deploy to NativePHP");
        }
    }

    protected function extractPhpSourceWindows(string $spcPath): void
    {
        $downloadsPath = $spcPath . '/downloads';
        $sourcePath = $spcPath . '/source/php-src';
        $lockFilePath = $downloadsPath . '/.lock.json';

        // Find PHP archive
        $phpArchive = $this->getPhpArchivePath($spcPath);

        if (!$phpArchive || !file_exists($phpArchive)) {
            $this->warn('No PHP source archive found, skipping pre-extraction');
            return;
        }

        $this->line('Found PHP archive: ' . basename($phpArchive));

        // Read the lock file to get the expected hash
        $expectedHash = null;
        if (file_exists($lockFilePath)) {
            $lockData = json_decode(file_get_contents($lockFilePath), true);
            if (isset($lockData['php-src']['hash'])) {
                $expectedHash = $lockData['php-src']['hash'];
            }
        }

        // Check if already extracted with correct hash
        $hashFile = $sourcePath . '/.spc-hash';
        if (file_exists($sourcePath . '/main/php_version.h') && file_exists($hashFile)) {
            $currentHash = trim(file_get_contents($hashFile));
            if ($currentHash === $expectedHash) {
                $this->info('✅ PHP source already extracted properly with matching hash');
                return;
            } else {
                $this->warn("PHP source hash mismatch (expected: {$expectedHash}, got: {$currentHash})");
            }
        }

        // Clean and recreate source directory
        if (file_exists($sourcePath)) {
            $this->line('Cleaning existing PHP source directory...');
            Process::run('rm -rf "' . $sourcePath . '"');
        }

        mkdir($sourcePath, 0755, true);

        // Use two-step extraction method
        $this->line('Using two-step extraction method for PHP source...');

        $extractionSuccess = $this->extractNestedTarArchive($phpArchive, $sourcePath, 'php-src');

        if ($extractionSuccess && file_exists($sourcePath . '/main/php_version.h')) {
            $this->info('✅ PHP source extracted successfully');

            // Write the hash from lock file to prevent static-php-cli from re-extracting
            if ($expectedHash) {
                file_put_contents($hashFile, $expectedHash);
                $this->info("✅ Created .spc-hash with expected hash: {$expectedHash}");
            } else {
                $this->warn('⚠️ Could not read expected hash from lock file, static-php-cli may re-extract');
            }
        } else {
            throw new RuntimeException('Failed to extract PHP source using all available methods');
        }
    }

    protected function extractPeclExtensionsWindows(string $spcPath, array $extensions): void
    {
        // Pre-extract PECL extensions that were downloaded (sqlsrv, pdo_sqlsrv, etc.)
        // This prevents static-php-cli's problematic piped extraction on Windows

        $downloadsPath = $spcPath . '/downloads';
        $phpSourcePath = $spcPath . '/source/php-src';

        $this->line("📦 Checking " . count($extensions) . " extensions for PECL extraction...");

        $extracted = 0;
        $skipped = 0;

        foreach ($extensions as $ext) {
            // Skip core extensions that don't need extraction
            $coreExtensions = [
                'pdo',
                'pdo_sqlite',
                'sqlite3',
                'mbstring',
                'fileinfo',
                'tokenizer',
                'openssl',
                'curl',
                'zip',
                'zlib',
                'session',
                'filter',
                'dom',
                'xml',
                'simplexml',
                'gd',
                'opcache',
                'phar',
                'iconv',
                'ctype',
                'bcmath',
                'sockets',
                'bz2',
                'json',
                'libxml',
                'mbregex',
                'mysqli',
                'pdo_mysql',
                'intl',
                'soap'
            ];

            if (in_array($ext, $coreExtensions)) {
                $skipped++;
                continue;
            }

            // Check if extension needs PECL extraction (sqlsrv, pdo_sqlsrv, etc.)
            $peclArchives = glob($downloadsPath . '/' . $ext . '*.tgz');
            if (empty($peclArchives)) {
                $peclArchives = glob($downloadsPath . '/' . $ext . '*.tar.gz');
            }

            if (!empty($peclArchives)) {
                $archive = $peclArchives[0];
                $targetPath = $phpSourcePath . '/ext/' . $ext;

                if (file_exists($targetPath)) {
                    $this->line("  ✅ {$ext} already extracted");
                    continue;
                }

                $this->line("  📦 Extracting PECL extension: {$ext}");

                if (!file_exists($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }

                $extractionSuccess = $this->extractNestedTarArchive($archive, $targetPath, $ext);

                if ($extractionSuccess) {
                    $this->line("  ✅ {$ext} extracted successfully");
                    $extracted++;
                } else {
                    $this->warn("  ⚠️ Failed to extract {$ext}");
                }
            }
        }

        if ($extracted > 0) {
            $this->info("✅ Extracted {$extracted} PECL extension(s)");
        }
        if ($skipped > 0) {
            $this->line("  ℹ️  Skipped {$skipped} core extension(s) (no extraction needed)");
        }
    }

    protected function getExpectedFilesForDependency(string $depName): array
    {
        switch ($depName) {
            case 'sqlite':
                return ['sqlite3.c', 'sqlite3.h'];
            case 'nghttp2':
                return ['CMakeLists.txt', 'lib'];
            case 'openssl':
                return ['Configure', 'crypto'];
            case 'zlib':
                return ['CMakeLists.txt', 'zlib.h'];
            case 'libssh2':
                return ['CMakeLists.txt', 'src', 'include'];
            case 'bzip2':
                return ['Makefile', 'bzlib.h', 'bzip2.c'];
            case 'curl':
                return ['CMakeLists.txt', 'lib', 'src'];
            case 'libpng':
                return ['CMakeLists.txt', 'png.h'];
            case 'libjpeg':
                return ['CMakeLists.txt', 'jpeglib.h'];
            case 'libzip':
                // libzip uses CMake only, no configure script
                return ['CMakeLists.txt', 'lib'];
            case 'xz':
                return ['CMakeLists.txt', 'src'];
            case 'libwebp':
                // libwebp has configure.ac (not configure) and CMakeLists.txt
                // configure script must be generated by running autogen.sh
                return ['CMakeLists.txt', 'configure.ac'];
            case 'libxml2':
                // libxml2 has configure.ac (not configure) and CMakeLists.txt
                // Note: The actual extraction is from libwebp archive (v1.3.2.tar.gz)
                // which is why we see libwebp files here
                return ['CMakeLists.txt', 'configure.ac'];
            case 'onig':
                return ['CMakeLists.txt', 'src'];
            case 'freetype':
                return ['CMakeLists.txt', 'include'];
            case 'libiconv-win':
                return ['CMakeLists.txt', 'lib', 'include'];
            default:
                return [];
        }
    }

    protected function createSourceHashFile(string $spcPath, string $sourceName, string $archivePath): void
    {
        $sourcePath = $spcPath . '/source/' . $sourceName;
        $hashFile = $sourcePath . '/.spc-hash';

        if (!file_exists($sourcePath)) {
            return;
        }

        $hash = hash_file('sha256', $archivePath);
        file_put_contents($hashFile, $hash);
    }
}
