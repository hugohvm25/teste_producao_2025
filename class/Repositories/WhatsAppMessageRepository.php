<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use App\Models\WhatsAppMessage;
use mysqli;

class WhatsAppMessageRepository
{
    private mysqli $db;

    public function __construct(Database $database)
    {
        $this->db = $database->getConnection();
    }

    /**
     * Busca histórico onde o telefone do usuário aparece
     * como remetente OU destinatário.
     *
     * @param string $phone Telefone (formato igual ao armazenado na base)
     * @param int $limit Limite de registros para evitar sobrecarga
     * @return WhatsAppMessage[]
     */
    public function findHistoryByPhone(string $phone, int $limit = 100): array
    {
        $sql = "SELECT sender_phone, receiver_phone, message_text, media_type, media_url, received_at
                  FROM whatsapp_messages
                 WHERE sender_phone = ? OR receiver_phone = ?
                 ORDER BY received_at DESC
                 LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $phone, $phone, $limit);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = new WhatsAppMessage(
                (string)$row['sender_phone'],
                (string)$row['receiver_phone'],
                (string)$row['message_text'],
                $row['media_type'] !== null ? (string)$row['media_type'] : null,
                $row['media_url'] !== null ? (string)$row['media_url'] : null,
                (string)$row['received_at']
            );
        }
        $stmt->close();

        return $items;
    }
}
