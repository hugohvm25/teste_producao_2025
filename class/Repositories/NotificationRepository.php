<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Notification;
use mysqli;

class NotificationRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findFirstForUser(int $userId): ?Notification
    {
        $sql = "SELECT id, useridto, subject, fullmessage, contexturl 
                  FROM kt7u_notifications 
                 WHERE useridto = ? 
                 ORDER BY id DESC 
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return new Notification(
            (int)$row['id'],
            (int)$row['useridto'],
            (string)$row['subject'],
            (string)$row['fullmessage'],
            $row['contexturl'] !== null ? (string)$row['contexturl'] : null
        );
    }
}
