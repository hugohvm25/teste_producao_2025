<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Fluxo;
use mysqli;

class FluxoRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findByUserAndCourse(int $userId, int $courseId): ?Fluxo
    {
        $sql = "SELECT id_user, id_course, id_status, pref_type 
                  FROM fluxo 
                 WHERE id_user = ? AND id_course = ? 
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return new Fluxo(
            (int)$row['id_user'],
            (int)$row['id_course'],
            (int)$row['id_status'],
            $row['pref_type'] !== null ? (string)$row['pref_type'] : null
        );
    }
}
