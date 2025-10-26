<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\Prompt;
use mysqli;

class PromptRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    public function findById(int $id_prompt): ?Prompt
    {
        $sql = "SELECT id_prompt, prompt_type, description, prompt_text 
                  FROM prompt 
                 WHERE id_prompt = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id_prompt);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) return null;

        return new Prompt(
            (int)$row['id_prompt'],
            (string)$row['prompt_type'],
            (string)$row['description'],
            (string)$row['prompt_text']
        );
    }
}
