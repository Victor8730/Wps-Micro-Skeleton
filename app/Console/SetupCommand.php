<?php

declare(strict_types=1);

namespace App\Console;

use WpsMicro\Core\Console\Command;

class SetupCommand implements Command
{
    /** Prepare local files without connecting to or mutating a database. */
    public function __construct(private string $root)
    {
    }

    public function name(): string
    {
        return 'app:setup';
    }

    public function description(): string
    {
        return 'Prepare the local environment and writable storage directories';
    }

    /** @param list<string> $arguments */
    public function handle(array $arguments): int
    {
        if ($arguments !== []) {
            echo "Usage: php wps app:setup\n";
            return 1;
        }
        if (!file_exists($this->root . '/.env')) {
            $contents = file_get_contents($this->root . '/.env_example');
            $file = fopen($this->root . '/.env', 'x');
            if ($contents === false || $file === false) {
                throw new \RuntimeException('Unable to prepare the environment file.');
            }
            try {
                if (fwrite($file, $contents) !== strlen($contents)) {
                    throw new \RuntimeException('Unable to write the environment file.');
                }
            } finally {
                fclose($file);
            }
            chmod($this->root . '/.env', 0600);
        }
        foreach (['storage/cache/twig', 'storage/logs'] as $directory) {
            $path = $this->root . '/' . $directory;
            if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
                throw new \RuntimeException('Unable to create storage: ' . $directory);
            }
        }
        echo "Local files ready; existing .env values were preserved.\n";
        echo "Configure .env, build assets, then run php wps app:check --database and php wps migrate.\n";

        return 0;
    }
}
