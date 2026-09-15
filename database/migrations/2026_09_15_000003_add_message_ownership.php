<?php

declare(strict_types=1);

use WpsMicro\Core\Migration;

return new class () extends Migration {
    /** Preserve existing demo records with a null owner. */
    public function up(PDO $db): void
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $db->exec('ALTER TABLE home_messages ADD COLUMN user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE');
        } else {
            $db->exec('ALTER TABLE home_messages ADD COLUMN user_id INT UNSIGNED NULL,
                ADD CONSTRAINT home_messages_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
        }
        $db->exec('CREATE INDEX home_messages_user_id_id ON home_messages (user_id, id)');
    }

    /** Remove ownership metadata without rewriting historical migrations. */
    public function down(PDO $db): void
    {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $db->exec('DROP INDEX home_messages_user_id_id');
        } else {
            $db->exec('ALTER TABLE home_messages DROP FOREIGN KEY home_messages_user_fk');
            $db->exec('DROP INDEX home_messages_user_id_id ON home_messages');
        }
        $db->exec('ALTER TABLE home_messages DROP COLUMN user_id');
    }
};
