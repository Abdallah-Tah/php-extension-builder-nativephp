<?php

namespace Amohamed\NativePhpCustomPhp\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class TestMinimalBuild extends Command
{
    protected $signature = 'php-ext:test-minimal';
    protected $description = 'Test minimal build with just essential extensions';

    public function handle(): int
    {
        $this->info('Testing minimal build with essential extensions only...');

        // Essential extensions for basic functionality
        $minimalExtensions = ['pdo', 'sqlite3', 'mbstring', 'fileinfo', 'tokenizer'];

        $spcPath = base_path('static-php-cli');

        try {
            // Clean previous artifacts
            $this->info('Cleaning previous build artifacts...');
            $buildrootPath = $spcPath . '/buildroot';
            if (is_dir($buildrootPath)) {
                Process::run('rm -rf "' . $buildrootPath . '"');
            }

            // Download minimal components
            $this->info('Downloading minimal components...');

            // Download PHP source
            Process::path($spcPath)->run('php bin/spc download php-src');
            Process::path($spcPath)->run('php bin/spc download micro');

            // Download minimal libraries
            $minimalLibraries = ['sqlite', 'zlib'];
            foreach ($minimalLibraries as $lib) {
                $this->info("Downloading {$lib}...");
                Process::path($spcPath)->run("php bin/spc download {$lib}");
            }

            // Build with minimal extensions
            $extensions = implode(',', $minimalExtensions);
            $this->info("Building with minimal extensions: {$extensions}");

            $buildCmd = "php bin/spc build \"{$extensions}\" --build-cli";
            $this->line("Running: {$buildCmd}");

            $buildProcess = Process::path($spcPath)
                ->timeout(3600)
                ->env([
                    'PATH' => getenv('PATH') . ';C:\Program Files\Git\usr\bin',
                    'SPC_CONCURRENCY' => '1',
                ])
                ->run($buildCmd);

            if (!$buildProcess->successful()) {
                $this->error("Build failed:");
                $this->error($buildProcess->output());
                $this->error($buildProcess->errorOutput());

                // Check if binary was created anyway
                $phpBinaryPath = $spcPath . '/buildroot/bin/php.exe';
                if (file_exists($phpBinaryPath)) {
                    $this->info("Binary created despite error. Testing...");
                    $testResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');
                    if ($testResult->successful()) {
                        $this->info("✅ Minimal build appears to work!");
                        $this->info($testResult->output());
                        return self::SUCCESS;
                    }
                }

                return self::FAILURE;
            }

            $this->info('✅ Minimal build completed successfully!');

            // Test the built PHP
            $phpBinaryPath = $spcPath . '/buildroot/bin/php.exe';
            if (file_exists($phpBinaryPath)) {
                $versionResult = Process::path($spcPath)->run('buildroot\bin\php.exe -v');
                if ($versionResult->successful()) {
                    $this->info('PHP Version:');
                    $this->line($versionResult->output());
                }

                $extensionsResult = Process::path($spcPath)->run('buildroot\bin\php.exe -m');
                if ($extensionsResult->successful()) {
                    $this->info('Loaded Extensions:');
                    $this->line($extensionsResult->output());
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
