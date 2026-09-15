<?php

declare(strict_types=1);

namespace App\Console;

use App\Models\User;
use App\Services\DemoSeeder;
use WpsMicro\Core\Config;
use WpsMicro\Core\Console\Command;
use WpsMicro\Core\Container;

class SeedCommand implements Command
{
    /** Resolve database dependencies only when explicitly seeding. */
    public function __construct(private Container $container)
    {
    }

    public function name(): string
    {
        return 'db:seed';
    }

    public function description(): string
    {
        return 'Load private demo messages for an existing account';
    }

    /** @param list<string> $arguments */
    public function handle(array $arguments): int
    {
        $email = null;
        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--user=') && $email === null) {
                $email = mb_strtolower(trim(substr($argument, 7)));
            } elseif ($argument !== '--force') {
                echo "Usage: php wps db:seed --user=email@example.com [--force]\n";
                return 1;
            }
        }
        if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            echo "Provide the email of an existing account with --user=.\n";
            return 1;
        }
        /** @var Config $config */
        $config = $this->container->get(Config::class);
        if ($config->get('app.env') === 'production' && !in_array('--force', $arguments, true)) {
            echo "Demo seeding in production requires --force.\n";
            return 1;
        }
        /** @var User $users */
        $users = $this->container->get(User::class);
        $user = $users->findByEmail($email);
        if ($user === null) {
            echo "Account not found. Register first.\n";
            return 1;
        }
        /** @var DemoSeeder $seeder */
        $seeder = $this->container->get(DemoSeeder::class);
        echo $seeder->seedForUser((int) $user['id']) . " demo messages added.\n";

        return 0;
    }
}
