<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Course;
use mysqli;

class CourseRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findById(int $id): ?Course
    {
        $sql = "SELECT id, fullname, shortname FROM kt7u_course WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return new Course((int)$row['id'], (string)$row['fullname'], (string)$row['shortname']);
    }
}
