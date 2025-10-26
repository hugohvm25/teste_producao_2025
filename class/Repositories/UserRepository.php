<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\User;
use mysqli;

class UserRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findById(int $id): ?User
    {
        $sql = "SELECT id, firstname, lastname, email, phone2, city FROM kt7u_user WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        return new User(
            (int)$row['id'],
            (string)$row['firstname'],
            (string)$row['lastname'],
            (string)$row['email'],
            $row['phone2'] !== null ? (string)$row['phone2'] : null,
            $row['city'] !== null ? (string)$row['city'] : null
        );
    }
}
