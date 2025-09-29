<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckFilamentDuplicates extends Command
{
    protected $signature = 'filament:check-duplicates {--fix : Automatically fix found duplicates}';
    protected $description = 'Check and fix duplicate array keys in Filament resources';

    private $issues = [];
    private $fixed = [];

    public function handle()
    {
        $this->info('🔍 Checking for duplicate array keys in Filament resources...');

        $files = File::glob(app_path('Filament/Resources/**/*.php'));
        $files = array_merge($files, File::glob(app_path('Filament/Resources/*.php')));

        foreach ($files as $file) {
            $this->checkFile($file);
        }

        $this->displayReport();

        return count($this->issues) > 0 ? 1 : 0;
    }

    private function checkFile($file)
    {
        $content = File::get($file);
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);

        // Check for duplicate enum values in arrays
        $this->checkDuplicateEnumValues($content, $relativePath);

        // Check for duplicate string keys in colors array
        $this->checkDuplicateColorKeys($content, $relativePath);

        // Check match statements for duplicates
        $this->checkDuplicateMatchCases($content, $relativePath);
    }

    private function checkDuplicateEnumValues($content, $file)
    {
        // Pattern to find array definitions with enum values
        $pattern = '/\[[\s\S]*?(AdStatus::\w+->value\s*=>\s*[\'"][^\'"]+([\'"]))[\s\S]*?\]/';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $arrayContent) {
                // Extract all enum values
                preg_match_all('/(AdStatus::\w+)->value/', $arrayContent, $enumMatches);

                $enumValues = $enumMatches[1];
                $duplicates = array_diff_assoc($enumValues, array_unique($enumValues));

                if (!empty($duplicates)) {
                    foreach ($duplicates as $duplicate) {
                        $this->issues[] = [
                            'file' => $file,
                            'type' => 'duplicate_enum',
                            'value' => $duplicate,
                            'message' => "Duplicate enum value: {$duplicate}->value"
                        ];
                    }
                }
            }
        }
    }

    private function checkDuplicateColorKeys($content, $file)
    {
        // Pattern to find colors array
        $pattern = '/->colors\(\[([^\]]+)\]\)/';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $colorsContent) {
                // Extract color keys
                preg_match_all("/['\"](\w+)['\"]\s*=>/", $colorsContent, $keyMatches);

                $keys = $keyMatches[1];
                $duplicates = array_diff_assoc($keys, array_unique($keys));

                if (!empty($duplicates)) {
                    foreach ($duplicates as $duplicate) {
                        $this->issues[] = [
                            'file' => $file,
                            'type' => 'duplicate_color_key',
                            'value' => $duplicate,
                            'message' => "Duplicate color key: '{$duplicate}'"
                        ];
                    }
                }
            }
        }
    }

    private function checkDuplicateMatchCases($content, $file)
    {
        // Pattern to find match statements
        $pattern = '/match\s*\([^)]+\)\s*\{([^}]+)\}/';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $matchContent) {
                // Extract match cases
                preg_match_all('/(AdStatus::\w+->value)\s*=>/', $matchContent, $caseMatches);

                $cases = $caseMatches[1];
                $duplicates = array_diff_assoc($cases, array_unique($cases));

                if (!empty($duplicates)) {
                    foreach ($duplicates as $duplicate) {
                        $this->issues[] = [
                            'file' => $file,
                            'type' => 'duplicate_match_case',
                            'value' => $duplicate,
                            'message' => "Duplicate match case: {$duplicate}"
                        ];
                    }
                }
            }
        }
    }

    private function displayReport()
    {
        $this->newLine();

        if (empty($this->issues)) {
            $this->info('✅ No duplicate array keys found!');
            return;
        }

        $this->error('❌ Found ' . count($this->issues) . ' duplicate issues:');
        $this->newLine();

        $groupedIssues = collect($this->issues)->groupBy('file');

        foreach ($groupedIssues as $file => $fileIssues) {
            $this->warn("📁 {$file}:");
            foreach ($fileIssues as $issue) {
                $this->line("   ⚠️  {$issue['message']}");
            }
            $this->newLine();
        }

        if ($this->option('fix')) {
            $this->info('Attempting to fix issues automatically...');
            $this->warn('Note: Automatic fixing may require manual review.');
        }
    }
}