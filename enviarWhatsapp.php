<?php
$db_host = 'localhost';
$db_name = 'u490880839_7xii0';
$db_user = 'u490880839_7ZrhP';
$db_pass = '&Senha121&';
$charset = 'utf8mb4';
// --- 2. PEGAR E LOGAR OS DADOS ---
$json_dados = file_get_contents('php://input');
$dados = json_decode($json_dados, true);
$log_file = __DIR__ . '/log_webhook.txt';

$log_message = "========================================\n";
$log_message .= "Recebido em: " . date('Y-m-d H:i:s') . "\n";

// Se não houver dados, para aqui.
if (!$dados) {
    $log_message .= "AVISO: Nenhum dado recebido no 'php://input'.\n\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No data']);
    exit;
}

$log_message .= print_r($dados, true) . "\n";


// --- 3. EXTRAIR OS DADOS PRINCIPAIS ---
$phone = $dados['phone'] ?? null;
$connectedPhone = $dados['connectedPhone'] ?? null;
$messageId = $dados['messageId'] ?? null;
$senderName = $dados['senderName'] ?? null;
$apiTimestamp = $dados['momment'] ?? null;

// --- 4. ROTEADOR DE TIPO DE MENSAGEM ---
$message = null; // Texto ou Legenda
$media_type = null; // Tipo da mídia (ex: image/jpeg)
$media_download_url = null; // URL para baixar
$media_url_for_db = null; // Caminho que vamos salvar no DB

if (isset($dados['text']) && is_array($dados['text']) && isset($dados['text']['message'])) {
    // --- É MENSAGEM DE TEXTO ---
    $message = $dados['text']['message'];
    $media_type = 'text';
    $log_message .= "INFO: Mensagem de TEXTO detectada.\n";

} elseif (isset($dados['image']) && is_array($dados['image'])) {
    // --- É IMAGEM ---
    $message = $dados['image']['caption'] ?? null; // Pega a legenda
    $media_type = $dados['image']['mimeType'];
    $media_download_url = $dados['image']['imageUrl'];
    $log_message .= "INFO: Mídia de IMAGEM detectada.\n";

} elseif (isset($dados['audio']) && is_array($dados['audio'])) {
    // --- É ÁUDIO ---
    $message = null; // Áudio não tem legenda
    $media_type = $dados['audio']['mimeType'];
    $media_download_url = $dados['audio']['audioUrl'];
    $log_message .= "INFO: Mídia de ÁUDIO detectada.\n";

} elseif (isset($dados['video']) && is_array($dados['video'])) {
    // --- É VÍDEO (NOVO BLOCO) ---
    $message = $dados['video']['caption'] ?? null; // Pega a legenda
    $media_type = $dados['video']['mimeType'];
    $media_download_url = $dados['video']['videoUrl'];
    $log_message .= "INFO: Mídia de VÍDEO detectada.\n";
    
} else {
    // --- OUTRO TIPO (ex: status, documento, etc.) ---
    $log_message .= "AVISO: Tipo de mensagem não suportado. Ignorando.\n\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    http_response_code(200);
    echo json_encode(['status' => 'received_unsupported_type']);
    exit;
}

// --- 5. BAIXAR A MÍDIA (Se houver) ---
// (Este bloco não precisa mudar, ele é genérico e funciona)
if ($media_download_url) {
    
    // Pega a extensão do arquivo (jpg, ogg, mp4, etc.)
    $file_extension = pathinfo($media_download_url, PATHINFO_EXTENSION);
    if (empty($file_extension)) { 
        $file_extension = explode('/', explode(';', $media_type)[0])[1] ?? 'dat';
    }

    $new_filename = $messageId . '.' . $file_extension;
    $save_path_on_server = __DIR__ . '/uploads/' . $new_filename;
    
    $file_data = @file_get_contents($media_download_url);
    
    if ($file_data === false) {
        $log_message .= "ERRO: Falha ao baixar o arquivo de: $media_download_url\n";
    } else {
        file_put_contents($save_path_on_server, $file_data);
        
        $media_url_for_db = '/api_whatsapp/uploads/' . $new_filename; 
        $log_message .= "SUCESSO: Mídia salva em: $save_path_on_server\n";
    }
}


// --- 6. CONECTAR E INSERIR NO BANCO ---
// (Este bloco também não precisa mudar)
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $log_message .= "INFO: Conexão com DB OK.\n";

    $sql = "INSERT IGNORE INTO whatsapp_messages 
                (message_id, sender_phone, receiver_phone, message_text, sender_name, api_timestamp, media_type, media_url)
            VALUES 
                (:message_id, :sender_phone, :receiver_phone, :message_text, :sender_name, :api_timestamp, :media_type, :media_url)";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        'message_id' => $messageId,
        'sender_phone' => $phone,
        'receiver_phone' => $connectedPhone,
        'message_text' => $message, // Salva o texto OU a legenda
        'sender_name' => $senderName,
        'api_timestamp' => $apiTimestamp,
        'media_type' => $media_type,
        'media_url' => $media_url_for_db
    ]);

    $log_message .= "SUCESSO: Mensagem inserida no DB.\n\n";
    
    http_response_code(200);
    echo json_encode(['status' => 'received_and_saved']);

} catch (\PDOException $e) {
    $log_message .= "ERRO DE BANCO DE DADOS: " . $e->getMessage() . "\n\n";
    http_response_code(500); 
    echo json_encode(['error' => 'Database operation failed']);
}

// --- 7. SALVAR O LOG NO ARQUIVO ---
file_put_contents($log_file, $log_message, FILE_APPEND);

?>