<?php

namespace Amohamed\NativePhpCustomPhp\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TestGitDependencies extends Command
{
    protected $signature = 'php-ext:test-git-deps';
    protected $description = 'Test git dependency handling specifically for libiconv-win';

    public function handle(): int
    {
        $spcPath = base_path('static-php-cli');

        if (!file_exists($spcPath)) {
            $this->error('static-php-cli not found. Please run php-ext:install first.');
            return self::FAILURE;
        }

        $this->info('Testing git dependency handling...');

        // Test specifically libiconv-win
        $this->testLibiconvWin($spcPath);

        return self::SUCCESS;
    }

    protected function testLibiconvWin(string $spcPath): void
    {
        $sourcePath = $spcPath . '/source';
        $sourceJsonPath = $spcPath . '/config/source.json';
        $libiconvPath = $sourcePath . '/libiconv-win';

        $this->info('Checking libiconv-win specifically...');

        // Read source.json to get libiconv-win config
        if (!file_exists($sourceJsonPath)) {
            $this->error('source.json not found');
            return;
        }

        $sourceConfig = json_decode(file_get_contents($sourceJsonPath), true);
        $libiconvConfig = $sourceConfig['libiconv-win'] ?? null;

        if (!$libiconvConfig) {
            $this->error('libiconv-win not found in source.json');
            return;
        }

        $this->info('libiconv-win config: ' . json_encode($libiconvConfig, JSON_PRETTY_PRINT));

        // Check if already exists
        if (file_exists($libiconvPath)) {
            if (file_exists($libiconvPath . '/.git')) {
                $this->info('✅ libiconv-win already exists and is a git repository');

                // Show some info about it
                $remoteResult = Process::path($libiconvPath)->run('git remote -v');
                if ($remoteResult->successful()) {
                    $this->info('Remote info: ' . $remoteResult->output());
                }

                $statusResult = Process::path($libiconvPath)->run('git status --porcelain');
                if ($statusResult->successful()) {
                    $this->info('Status: ' . ($statusResult->output() ? 'Modified' : 'Clean'));
                }

                return;
            } else {
                $this->warn('libiconv-win exists but is not a git repository, removing...');
                Process::run('rm -rf "' . $libiconvPath . '"');
            }
        }

        // Clone libiconv-win
        $this->info('Cloning libiconv-win...');
        $url = $libiconvConfig['url'];
        $rev = $libiconvConfig['rev'] ?? 'master';

        $cloneResult = Process::run("git clone \"{$url}\" \"{$libiconvPath}\"");

        if ($cloneResult->successful()) {
            $this->info('✅ libiconv-win cloned successfully');

            // Verify the clone
            if (file_exists($libiconvPath . '/source/COPYING')) {
                $this->info('✅ License file found, clone is valid');
            } else {
                $this->warn('⚠️ Expected files not found in clone');
            }
        } else {
            $this->error('❌ Failed to clone libiconv-win: ' . $cloneResult->errorOutput());
        }
    }
}
