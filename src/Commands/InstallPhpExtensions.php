<?php

namespace Amohamed\NativePhpCustomPhp\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

class InstallPhpExtensions extends Command
{
    protected $signature = 'php-ext:install {--php-version= : PHP version to build (8.1, 8.2, 8.3)} {--extensions= : Comma-separated list of extensions (or use interactive mode)}';

    protected $description = 'Build a custom PHP binary with database and extension support for NativePHP';

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
            'libraries' => ['libpng', 'libjpeg', 'freetype']
        ],
        'iconv' => [
            'name' => 'Iconv',
            'description' => 'Character encoding conversion',
            'php_versions' => ['8.1', '8.2', '8.3', '8.4'],
            'libraries' => []
        ],
        'mbstring' => [
            'name' => 'Multibyte String',
            'description' => 'Multibyte string functions',
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
    protected array $selectedExtensions = [];
    protected array $requiredLibraries = [];

    // Default extensions that are always included in every build
    // Reduced to essential extensions for reliability
    protected array $defaultExtensions = [
        'pdo',           // Database abstraction layer
        'pdo_sqlite',    // SQLite PDO driver
        'sqlite3',       // SQLite3 extension
        'mbstring',      // Multibyte string functions
        'fileinfo',      // File information functions
        'tokenizer',     // PHP tokenizer
        'openssl',       // SSL/TLS cryptographic functions
        'curl',          // HTTP client library
        'zip',           // ZIP archive support
        'zlib',          // Compression library
        'session',       // Session handling
        'filter'         // Data filtering
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->validateEnvironment();

        // Get user preferences
        $this->getUserPreferences();

        // Set the path to static-php-cli at the Laravel project root
        $spcPath = base_path('static-php-cli');

        try {
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
                $this->info('✅ Build completed successfully!');
                $this->displayBuildSummary($spcPath);
                return self::SUCCESS;
            }

            $this->error('❌ Build failed!');
            return self::FAILURE;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function setupStaticPhpCli(string $spcPath): void
    {
        if (!file_exists($spcPath)) {
            $this->info('Cloning static-php-cli repository...');
            $cloneResult = Process::run("git clone https://github.com/crazywhalecc/static-php-cli.git \"{$spcPath}\"");

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
            $cloneResult = Process::run("git clone https://github.com/microsoft/php-sdk-binary-tools.git \"{$phpSdkPath}\"");

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
            ->run('php bin/spc doctor --auto-fix');

        if (!$doctorResult->successful()) {
            $this->warn('Doctor check failed. Attempting to continue anyway...');
        }
    }

    protected function downloadComponents(string $spcPath): void
    {
        // Download PHP source for the specified version - force the exact version
        $this->info("Downloading PHP {$this->selectedPhpVersion} source...");

        // First try with specific version
        $downloadResult = Process::path($spcPath)->run("php bin/spc download php-src --with-php={$this->selectedPhpVersion}");

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

        // Verify the correct PHP version was downloaded
        $this->info('Verifying PHP version...');
        $versionCheck = Process::path($spcPath)->run('php bin/spc list sources | findstr php-src');
        if ($versionCheck->successful()) {
            $this->line("PHP source info: " . trim($versionCheck->output()));
        }

        // Download micro SAPI (required for building)
        $this->info('Downloading micro SAPI...');
        $microResult = Process::path($spcPath)->run('php bin/spc download micro');

        if (!$microResult->successful()) {
            throw new RuntimeException('Failed to download micro SAPI');
        }

        // CRITICAL: Handle git-based dependencies before downloading other components
        $this->info('Ensuring git-based dependencies are available...');
        $this->ensureGitDependencies($spcPath);

        // CRITICAL: Ensure tar-based extractions are complete (especially nghttp2)
        $this->info('Verifying tar-based extractions...');
        $this->ensureTarBasedExtractions($spcPath);

        // Download required extension sources
        foreach ($this->selectedExtensions as $ext) {
            // Skip basic extensions that don't need separate downloads
            if (in_array($ext, ['pdo', 'mbstring', 'fileinfo'])) {
                continue;
            }

            $this->info("Downloading {$ext} extension source...");
            $maxRetries = 3;
            $downloaded = false;

            for ($attempt = 1; $attempt <= $maxRetries && !$downloaded; $attempt++) {
                $this->line("Attempt {$attempt} of {$maxRetries}...");

                $downloadResult = Process::path($spcPath)
                    ->timeout(300)
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

        // Download each required library
        foreach ($this->requiredLibraries as $lib) {
            $this->info("Downloading {$lib}...");
            $maxRetries = 3;
            $downloaded = false;

            for ($attempt = 1; $attempt <= $maxRetries && !$downloaded; $attempt++) {
                $this->line("Attempt {$attempt} of {$maxRetries}...");

                $downloadResult = Process::path($spcPath)
                    ->timeout(300)
                    ->run("php bin/spc download {$lib}");

                if ($downloadResult->successful()) {
                    $downloaded = true;
                    $this->info("✅ Successfully downloaded {$lib}");
                    break;
                }

                if ($attempt === $maxRetries) {
                    if (!$this->confirm("Failed to download {$lib}. Continue anyway?")) {
                        throw new RuntimeException("Cannot continue without {$lib}");
                    }
                } else {
                    $this->warn("Download failed, retrying...");
                    sleep(2);
                }
            }
        }
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
    protected function buildPhpWithExtensions(string $spcPath): bool
    {
        // Build with selected extensions
        $extensions = implode(',', $this->selectedExtensions);

        $this->info("Building with selected extensions: {$extensions}");

        // Build command without --with-php (that's used in download phase)
        $buildCmd = "php bin/spc build \"{$extensions}\" --build-cli";
        $this->line("Running build command: {$buildCmd}");

        $buildProcess = Process::path($spcPath)
            ->timeout(7200) // 2 hour timeout for full build
            ->env([
                'PATH' => getenv('PATH') . ';C:\Program Files\Git\usr\bin',
                'SPC_CONCURRENCY' => '1',
                'CMAKE_BUILD_PARALLEL_LEVEL' => '1',
                'VS_PATH' => 'C:\Program Files (x86)\Microsoft Visual Studio\2022\BuildTools\VC\Tools\MSVC\14.41.34120\bin\Hostx64\x64'
            ])
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
    }

    protected function getUserPreferences(): void
    {
        // Get PHP version from option or prompt user
        $phpVersion = $this->option('php-version');
        if (!$phpVersion) {
            $this->info('Available PHP versions: 8.1, 8.2, 8.3');
            $this->warn('Note: PHP 8.4 does not support SQL Server extensions (sqlsrv, pdo_sqlsrv)');

            $phpVersion = $this->choice(
                'Select PHP version to build:',
                ['8.1', '8.2', '8.3'],
                '8.3'
            );
        }

        if (!in_array($phpVersion, ['8.1', '8.2', '8.3'])) {
            throw new RuntimeException('Unsupported PHP version. Supported versions: 8.1, 8.2, 8.3');
        }

        $this->selectedPhpVersion = $phpVersion;
        $this->info("Selected PHP version: {$phpVersion}");

        // Get extensions from option or prompt user
        $extensionsInput = $this->option('extensions');
        if ($extensionsInput) {
            $requestedExtensions = array_map('trim', explode(',', $extensionsInput));
        } else {
            $this->info('');
            $this->info('Building a custom PHP binary with your selected database and extension support...');
            $requestedExtensions = $this->promptForExtensions();
        }

        // Filter extensions based on PHP version compatibility
        $this->selectedExtensions = $this->filterExtensionsByPhpVersion($requestedExtensions);

        // Always include default extensions (these are core extensions needed for most PHP applications)
        $this->selectedExtensions = array_unique(array_merge($this->defaultExtensions, $this->selectedExtensions));

        $this->info('Including default extensions: ' . implode(', ', $this->defaultExtensions));

        // Calculate required libraries
        $this->calculateRequiredLibraries();

        $this->info('Selected extensions: ' . implode(', ', $this->selectedExtensions));
        $this->info('Required libraries: ' . implode(', ', $this->requiredLibraries));
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
        ];

        $additionalExt = $this->choice(
            'Select additional extension pack:',
            array_keys($additionalOptions),
            'none'
        );

        if ($additionalExt !== 'none') {
            $packExtensions = $this->getExtensionPack($additionalExt);
            $selectedExtensions = array_merge($selectedExtensions, $packExtensions);
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
        // Base libraries required for default extensions
        $this->requiredLibraries = [
            'zlib',           // zlib extension + zip dependency
            'sqlite',         // sqlite3 + pdo_sqlite
            'openssl',        // openssl extension + curl dependency
            'libxml2',        // dom, simplexml, xml extensions
            'libzip',         // zip extension
            'libpng',         // gd extension  
            'libjpeg',        // gd extension
            'freetype',       // gd extension
            'bzip2',          // bz2 extension
            'curl',           // curl extension
            'libcurl',        // curl extension
            'nghttp2',        // curl extension
            'libssh2',        // curl extension
            'xz',             // zip extension + xlswriter dependency
            'libwebp'         // gd extension (optional but recommended)
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
        $this->info("PHP Version: {$this->selectedPhpVersion}");

        // Show default extensions
        $this->info('Default Extensions (always included): ' . implode(', ', $this->defaultExtensions));

        // Group user-selected extensions by type
        $userSelectedExtensions = array_diff($this->selectedExtensions, $this->defaultExtensions);

        if (!empty($userSelectedExtensions)) {
            $databaseExtensions = array_intersect($userSelectedExtensions, [
                'mysqli',
                'pdo_mysql',
                'pgsql',
                'pdo_pgsql',
                'sqlsrv',
                'pdo_sqlsrv'
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

            // Test the built PHP
            $versionResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');
            if ($versionResult->successful()) {
                $this->info('PHP Version Output:');
                $this->line($versionResult->output());
            }

            // Test extensions
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
        $criticalTarDeps = ['nghttp2', 'libssh2', 'openssl', 'zlib', 'sqlite', 'bzip2', 'curl', 'libpng', 'libjpeg', 'libzip', 'xz', 'libwebp', 'libxml2'];

        foreach ($criticalTarDeps as $depName) {
            if (!isset($tarDependencies[$depName])) {
                continue;
            }

            $targetPath = $sourcePath . '/' . $depName;

            // Check if extraction is incomplete (only has .spc-hash and build directory)
            if (file_exists($targetPath)) {
                $contents = scandir($targetPath);
                $realContents = array_diff($contents, ['.', '..']);

                // If directory only contains .spc-hash and build/, it's incomplete
                if (
                    count($realContents) <= 2 &&
                    (in_array('.spc-hash', $realContents) || in_array('build', $realContents))
                ) {

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

    protected function reExtractTarDependency(string $downloadsPath, string $sourcePath, string $depName): void
    {
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
        if ($depName === 'libjpeg') {
            $possibleArchives = array_merge($possibleArchives, [
                'libjpeg-turbo*.tar.gz',
                'libjpeg-turbo*.tar.xz'
            ]);
        } elseif ($depName === 'libwebp') {
            $possibleArchives = array_merge($possibleArchives, [
                'v*.tar.gz',  // libwebp uses v1.3.2.tar.gz pattern
                'libwebp-*.tar.gz',
                'libwebp-*.tar.xz'
            ]);
        } elseif ($depName === 'libxml2') {
            $possibleArchives = array_merge($possibleArchives, [
                'v*.tar.gz',  // libxml2 uses v2.12.5.tar.gz pattern
                'libxml2-*.tar.gz',
                'libxml2-*.tar.xz'
            ]);
        }

        $archiveFile = null;
        foreach ($possibleArchives as $pattern) {
            $matches = glob($downloadsPath . '/' . $pattern);
            if (!empty($matches)) {
                $archiveFile = $matches[0]; // Use the first match
                break;
            }
        }

        if (!$archiveFile || !file_exists($archiveFile)) {
            $this->warn("  - No archive found for {$depName} in downloads directory");
            return;
        }

        $this->info("  - Extracting {$depName} from " . basename($archiveFile));

        // Use appropriate extraction command based on file extension
        $extractCmd = '';
        if (str_ends_with($archiveFile, '.tar.xz')) {
            $extractCmd = "tar -xf \"{$archiveFile}\" --strip-components=1 -C . --one-top-level={$depName} --no-same-owner";
        } elseif (str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {
            $extractCmd = "tar -xzf \"{$archiveFile}\" --strip-components=1 -C . --one-top-level={$depName} --no-same-owner";
        }

        if (empty($extractCmd)) {
            $this->warn("  - Unknown archive format for {$depName}");
            return;
        }

        // Execute extraction
        $extractResult = Process::path($sourcePath)->run($extractCmd);

        if ($extractResult->successful()) {
            $this->info("  ✅ {$depName} extracted successfully");

            // Verify extraction by checking for essential files
            $this->verifyTarExtraction($targetPath, $depName);
        } else {
            $this->warn("  ⚠️ Failed to extract {$depName}: " . $extractResult->errorOutput());

            // Try alternative extraction method without problematic options
            $this->info("  - Trying alternative extraction method...");
            $alternativeCmd = '';

            if (str_ends_with($archiveFile, '.tar.xz')) {
                $alternativeCmd = "tar -xf \"{$archiveFile}\" -C \"{$targetPath}\" --strip-components=1";
            } elseif (str_ends_with($archiveFile, '.tar.gz') || str_ends_with($archiveFile, '.tgz')) {
                $alternativeCmd = "tar -xzf \"{$archiveFile}\" -C \"{$targetPath}\" --strip-components=1";
            }

            if (!empty($alternativeCmd)) {
                // Create target directory first
                if (!file_exists($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }

                $altResult = Process::run($alternativeCmd);
                if ($altResult->successful()) {
                    $this->info("  ✅ {$depName} extracted with alternative method");
                    $this->verifyTarExtraction($targetPath, $depName);
                } else {
                    $this->error("  ❌ Both extraction methods failed for {$depName}");
                }
            }
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
        $expectedFiles = [];
        switch ($depName) {
            case 'nghttp2':
                $expectedFiles = ['CMakeLists.txt', 'lib', 'configure'];
                break;
            case 'openssl':
                $expectedFiles = ['Configure', 'config', 'crypto'];
                break;
            case 'zlib':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'zlib.h'];
                break;
            case 'libssh2':
                $expectedFiles = ['CMakeLists.txt', 'src', 'include'];
                break;
            case 'bzip2':
                $expectedFiles = ['Makefile', 'bzlib.h', 'bzip2.c'];
                break;
            case 'curl':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'lib', 'src'];
                break;
            case 'libpng':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'png.h'];
                break;
            case 'libjpeg':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'jpeglib.h'];
                break;
            case 'libzip':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'lib'];
                break;
            case 'xz':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'src'];
                break;
            case 'libwebp':
                $expectedFiles = ['CMakeLists.txt', 'configure', 'src'];
                break;
            default:
                // Generic check - should have more than just .spc-hash and build
                if (count($realContents) > 2) {
                    $this->info("  ✅ {$depName} appears to be properly extracted");
                    return;
                }
        }

        if (!empty($expectedFiles)) {
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
}