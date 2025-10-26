<?php
declare(strict_types=1);


namespace App\Logger;


use PDO;


final class MessageLogger
{
private PDO $pdo;


public function __construct(PDO $pdo)
{
$this->pdo = $pdo;
}


/**
* Salva mensagem enviada na tabela whatsapp_messages.
* Mantém a mesma lógica do código legado (message_id = zaapId | microtime).
* Retorna true em caso de sucesso.
*
* @param array $apiResponse Decodificado de JSON (resposta Z-API)
*/
public function logSent(
array $apiResponse,
string $senderPhone,
string $receiverPhone,
?string $messageText,
?string $mediaType,
?string $mediaUrl
): bool {
$messageId = $apiResponse['zaapId'] ?? ('envio-' . microtime(true));
$senderName = 'Você (Sistema)';
$apiTimestamp = (int) round(microtime(true) * 1000);


$sql = "INSERT IGNORE INTO whatsapp_messages
(message_id, sender_phone, receiver_phone, message_text, sender_name, api_timestamp, media_type, media_url)
VALUES
(:message_id, :sender_phone, :receiver_phone, :message_text, :sender_name, :api_timestamp, :media_type, :media_url)";
    
$stmt = $this->pdo->prepare($sql);
    
return $stmt->execute([
'message_id' => $messageId,
'sender_phone' => $senderPhone,
'receiver_phone'=> $receiverPhone,
'message_text' => $messageText,
'sender_name' => $senderName,
'api_timestamp' => $apiTimestamp,
'media_type' => $mediaType,
'media_url' => $mediaUrl,
]);
    
}
}


?>