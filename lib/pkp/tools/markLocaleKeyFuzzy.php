<?php

/**
 * @file tools/markLocaleKeyFuzzy.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MarkLocaleKeyFuzzy
 *
 * @ingroup tools
 *
 * @brief Mark a locale key as fuzzy across all locales except en.
 */

require(dirname(__FILE__, 4) . '/tools/bootstrap.php');

class MarkLocaleKeyFuzzy extends \PKP\cliTool\CommandLineTool
{
    public string $msgidMatch = '';
    public string $skipLocale = 'en';

    /**
     * Set up the tool with the provided arguments.
     */
    public function __construct(array $argv = [])
    {
        parent::__construct($argv);

        array_shift($argv);

        if (sizeof($this->argv) < 1) {
            $this->usage();
            exit(1);
        }

        $this->msgidMatch = array_shift($argv);
    }

    /**
     * Print usage instructions for this tool.
     */
    public function usage(): void
    {
        echo "\nMark a locale key as fuzzy.\n\n"
            . "Inserts a `#, fuzzy` line immediately above the matching msgid in every locale file, except en.\n\n"
            . "  Usage: php {$this->scriptName} [match]\n\n"
            . "  [match]    The msgid (key) to mark as fuzzy in each locale file.\n\n"
            . "  Example: php lib/pkp/tools/markLocaleKeyFuzzy.php emails.submissionAck.body\n\n";
    }

    /**
     * Execute the tool against all locale files.
     */
    public function execute(): void
    {
        $localeDirs = scandir('locale');
        if (!$localeDirs) {
            $this->output('Locale directories could not be found. Run this from the root directory of the application.');
            exit;
        }

        $localeDirs = array_filter($localeDirs, function ($localeDir) {
            return $localeDir !== '.' && $localeDir !== '..';
        });

        $searchDirs = [
            'locale/',
            'lib/pkp/locale/'
        ];

        foreach ($searchDirs as $searchDir) {
            foreach ($localeDirs as $localeDir) {
                if ($localeDir === $this->skipLocale) {
                    continue;
                }
                $dir = $searchDir . $localeDir;

                if (!file_exists($dir)) {
                    $this->output('No directory exists at ' . $dir . ' to modify. Skipping this locale.');
                    continue;
                }

                $localeFiles = array_filter(scandir($dir), function ($localeDir) {
                    return $localeDir !== '.' && $localeDir !== '..';
                });

                foreach ($localeFiles as $localeFile) {
                    $file = $dir . '/' . $localeFile;
                    $countChanges = 0;

                    if (is_dir($file)) {
                        $this->output('Skipping directory ' . $file);
                        continue;
                    }

                    $lines = explode("\n", file_get_contents($file));
                    for ($i = 0; $i < count($lines); $i++) {
                        if ($lines[$i] !== "msgid \"{$this->msgidMatch}\"") {
                            continue;
                        }

                        $previousLine = $lines[$i - 1] ?? '';
                        if (trim($previousLine) === '#, fuzzy') {
                            continue;
                        }

                        array_splice($lines, $i, 0, '#, fuzzy');
                        $countChanges++;
                        $i++;
                    }

                    if ($countChanges) {
                        file_put_contents($file, join("\n", $lines));
                        $this->output('Added ' . $countChanges . ' lines in ' . $file . '.');
                    }
                }
            }
        }
    }

    /**
     * Output a message to the console.
     */
    protected function output(string $string): void
    {
        echo "\n" . $string;
    }
}

$tool = new MarkLocaleKeyFuzzy($argv ?? []);
$tool->execute();
