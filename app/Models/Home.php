<?php

declare(strict_types=1);

namespace App\Models;

use WpsMicro\Core\Model;

class Home extends Model
{
    /**
     * Return only public demonstration messages, never private user records.
     *
     * @return list<array{id: int, title: string, body: string, created_at: string}>
     */
    public function latestMessages(int $limit = 5): array
    {
        $statement = $this->db->prepare(
            'SELECT id, title, body, created_at FROM home_messages WHERE user_id IS NULL ORDER BY id DESC LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** Return the number of messages belonging to one user. */
    public function countForUser(int $userId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM home_messages WHERE user_id = :user');
        $statement->execute(['user' => $userId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Return a bounded page of private messages.
     *
     * @return list<array{id: int, title: string, body: string, created_at: string}>
     */
    public function pageForUser(int $userId, int $page, int $perPage = 10): array
    {
        $statement = $this->db->prepare('SELECT id, title, body, created_at FROM home_messages
            WHERE user_id = :user ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':user', $userId, \PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Look up a record within its owner's scope.
     *
     * @return null|array{id: int, title: string, body: string, created_at: string}
     */
    public function findForUser(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT id, title, body, created_at FROM home_messages WHERE id = :id AND user_id = :user');
        $statement->execute(['id' => $id, 'user' => $userId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** Store a private message with a server-resolved owner. */
    public function createForUser(int $userId, string $title, string $body): int
    {
        $statement = $this->db->prepare('INSERT INTO home_messages (user_id, title, body) VALUES (:user, :title, :body)');
        $statement->execute(['user' => $userId, 'title' => $title, 'body' => $body]);

        return (int) $this->db->lastInsertId();
    }

    /** Update only a record belonging to the current user. */
    public function updateForUser(int $id, int $userId, string $title, string $body): void
    {
        $statement = $this->db->prepare('UPDATE home_messages SET title = :title, body = :body WHERE id = :id AND user_id = :user');
        $statement->execute(['id' => $id, 'user' => $userId, 'title' => $title, 'body' => $body]);
    }

    /** Delete only a record belonging to the current user. */
    public function deleteForUser(int $id, int $userId): void
    {
        $statement = $this->db->prepare('DELETE FROM home_messages WHERE id = :id AND user_id = :user');
        $statement->execute(['id' => $id, 'user' => $userId]);
    }
}
