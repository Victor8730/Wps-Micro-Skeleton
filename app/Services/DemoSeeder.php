<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class DemoSeeder
{
    /** Create a seeder that never creates accounts or passwords. */
    public function __construct(private PDO $db)
    {
    }

    /** Seed missing examples for an existing user and return the inserted count. */
    public function seedForUser(int $userId): int
    {
        $lookup = $this->db->prepare('SELECT id FROM users WHERE id = :id');
        $lookup->execute(['id' => $userId]);
        if ($lookup->fetchColumn() === false) {
            throw new \InvalidArgumentException('Register an account before loading demo data.');
        }

        $examples = [
            'Welcome to your workspace' => 'This private message belongs to your account. You can edit or delete it.',
            'A complete request lifecycle' => 'Routes, middleware, validation, persistence, and Twig work together here.',
            'Build your next application' => 'Keep application features here and update the framework through Composer.',
        ];
        $inserted = 0;
        $this->db->beginTransaction();
        try {
            $lookup = $this->db->prepare('SELECT id FROM home_messages WHERE user_id = :user AND title = :title');
            $insert = $this->db->prepare('INSERT INTO home_messages (user_id, title, body) VALUES (:user, :title, :body)');
            foreach ($examples as $title => $body) {
                $lookup->execute(['user' => $userId, 'title' => $title]);
                if ($lookup->fetchColumn() !== false) {
                    continue;
                }
                $insert->execute(['user' => $userId, 'title' => $title, 'body' => $body]);
                ++$inserted;
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $inserted;
    }
}
