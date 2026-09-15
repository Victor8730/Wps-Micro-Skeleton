<?php

declare(strict_types=1);

namespace App\Console;

use PDO;
use WpsMicro\Core\Config;
use WpsMicro\Core\Console\Command;
use WpsMicro\Core\Container;

class CheckCommand implements Command
{
    /** Keep the database check opt-in and avoid printing configuration secrets. */
    public function __construct(private Container $container)
    {
    }

    public function name(): string
    {
        return 'app:check';
    }

    public function description(): string
    {
        return 'Check configuration, storage, assets, and optionally the database';
    }

    /** @param list<string> $arguments */
    public function handle(array $arguments): int
    {
        if (array_diff($arguments, ['--database']) !== []) {
            echo "Usage: php wps app:check [--database]\n";
            return 1;
        }
        /** @var Config $config */
        $config = $this->container->get(Config::class);
        $production = $config->get('app.env') === 'production';
        $checks = [
            'PDO and mbstring' => extension_loaded('pdo') && extension_loaded('mbstring'),
            'Application URL' => filter_var($config->get('app.url'), FILTER_VALIDATE_URL) !== false,
            'Twig storage writable' => is_writable((string) $config->get('twig.cache_path')),
            'Log directory writable' => is_writable(dirname((string) $config->get('logging.path'))),
            'Frontend assets' => (!$production && $config->get('vite.dev_server_url', '') !== '')
                || is_file((string) $config->get('vite.manifest_path')),
            'Production safety' => !$production || (
                !$config->get('app.debug') && $config->get('session.secure')
                && str_starts_with((string) $config->get('app.url'), 'https://')
            ),
        ];
        if (in_array('--database', $arguments, true)) {
            try {
                /** @var PDO $db */
                $db = $this->container->get(PDO::class);
                $checks['Database connection'] = $db->query('SELECT 1') !== false;
            } catch (\Throwable) {
                $checks['Database connection'] = false;
            }
        }
        foreach ($checks as $label => $passed) {
            echo ($passed ? '[OK] ' : '[FAIL] ') . $label . "\n";
        }

        return in_array(false, $checks, true) ? 1 : 0;
    }
}
