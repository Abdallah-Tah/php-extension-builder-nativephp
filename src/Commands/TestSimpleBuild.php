<?php

namespace Amohamed\NativePhpCustomPhp\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class TestSimpleBuild extends Command
{
    protected $signature = 'php-ext:test-simple';
    protected $description = 'Test simple build with minimal extensions and better debugging';

    public function handle(): int
    {
        $this->info('Testing simple PHP build with minimal extensions...');

        // Essential extensions only
        $extensions = ['pdo', 'sqlite3', 'mbstring'];
        $phpVersion = '8.3';

        $spcPath = base_path('static-php-cli');

        try {
            // Skip setup if already exists
            if (!file_exists($spcPath)) {
                $this->error('static-php-cli not found. Please run the main command first.');
                return self::FAILURE;
            }

            // Clean previous build
            $this->info('Cleaning previous build...');
            $buildrootPath = $spcPath . '/buildroot';
            if (is_dir($buildrootPath)) {
                Process::run('rm -rf "' . $buildrootPath . '"');
            }

            // Download minimal components
            $this->info("Downloading PHP {$phpVersion} and essential components...");

            // Download PHP source
            $phpResult = Process::path($spcPath)->run("php bin/spc download php-src --with-php={$phpVersion}");
            if (!$phpResult->successful()) {
                $this->error("Failed to download PHP {$phpVersion}");
                $this->error($phpResult->errorOutput());
                return self::FAILURE;
            }

            // Download micro
            $microResult = Process::path($spcPath)->run('php bin/spc download micro');
            if (!$microResult->successful()) {
                $this->error("Failed to download micro SAPI");
                return self::FAILURE;
            }

            // Download minimal libraries
            $libraries = ['sqlite', 'zlib'];
            foreach ($libraries as $lib) {
                $this->info("Downloading {$lib}...");
                Process::path($spcPath)->run("php bin/spc download {$lib}");
            }

            // Verify sources
            $this->info('Verifying sources...');
            $listResult = Process::path($spcPath)->run('php bin/spc list sources');
            if ($listResult->successful()) {
                $this->line('Available sources:');
                $this->line($listResult->output());
            }

            // Build
            $extensionList = implode(',', $extensions);
            $this->info("Building with extensions: {$extensionList}");

            $buildCmd = "php bin/spc build \"{$extensionList}\" --build-cli --debug";
            $this->line("Command: {$buildCmd}");

            $buildProcess = Process::path($spcPath)
                ->timeout(1800) // 30 minute timeout
                ->env([
                    'SPC_CONCURRENCY' => '1',
                ])
                ->run($buildCmd);

            if (!$buildProcess->successful()) {
                $this->error("Build failed:");
                $this->error($buildProcess->output());
                $this->error($buildProcess->errorOutput());

                // Check if binary exists anyway
                $phpBinary = $spcPath . '/buildroot/bin/php.exe';
                if (file_exists($phpBinary)) {
                    $this->info("Binary created despite error. Testing...");
                    $testResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');
                    if ($testResult->successful()) {
                        $this->info("✅ PHP binary works!");
                        $this->info($testResult->output());
                        return self::SUCCESS;
                    }
                }

                return self::FAILURE;
            }

            $this->info('✅ Build succeeded!');

            // Test the binary
            $phpBinary = $spcPath . '/buildroot/bin/php.exe';
            if (file_exists($phpBinary)) {
                $this->info("Testing PHP binary: {$phpBinary}");

                $versionResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');
                if ($versionResult->successful()) {
                    $this->info('Version:');
                    $this->line($versionResult->output());
                }

                $extensionsResult = Process::path($spcPath)->run('buildroot\bin\php.exe -m');
                if ($extensionsResult->successful()) {
                    $this->info('Extensions:');
                    $this->line($extensionsResult->output());
                }

                // Test SQLite
                $sqliteTest = Process::path($spcPath)->run('buildroot\bin\php.exe -r "echo class_exists(\'PDO\') ? \'PDO: OK\' : \'PDO: FAIL\'; echo \"\\n\"; echo extension_loaded(\'sqlite3\') ? \'SQLite3: OK\' : \'SQLite3: FAIL\'; echo \"\\n\";"');
                if ($sqliteTest->successful()) {
                    $this->info('Functionality test:');
                    $this->line($sqliteTest->output());
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
