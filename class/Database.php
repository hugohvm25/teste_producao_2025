<?php
declare(strict_types=1);

namespace App;

use mysqli;
use RuntimeException;

class Database
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        if ($conn->connect_errno) {
            throw new RuntimeException('Erro de conexão MySQLi: ' . $conn->connect_error);
        }
        $this->conn = $conn;
        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }
}
