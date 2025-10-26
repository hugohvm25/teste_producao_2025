<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$db_config = [
    'host' => 'localhost',
    'name' => 'u490880839_7xii0',
    'user' => 'u490880839_7ZrhP',
    'pass' => '&Senha121&',
    'charset' => 'utf8mb4'
];
function enviarMensagem($phone, $message_or_url, $api_config, $db_config, $caption = null)
{
    echo "<h1>Disparando Mensagem...</h1>";
    $image_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $video_exts = ['mp4', 'mov', '3gp', 'mkv'];
    $audio_exts = ['ogg', 'mp3', 'aac', 'opus', 'wav', 'm4a'];
    // 1. Verifica se é uma URL (http:// ou https://)
    if (strpos(strtolower($message_or_url), 'http://') === 0 || strpos(strtolower($message_or_url), 'https://') === 0) {
        
        // É uma URL. Vamos descobrir o tipo pela extensão
        $ext = strtolower(pathinfo($message_or_url, PATHINFO_EXTENSION));

        if (in_array($ext, $image_exts)) {
            // É Imagem
            echo "<h2>Reconhecido: IMAGEM</h2>";
            return enviaImagem($phone, $message_or_url, $caption, $api_config, $db_config);
            
        } elseif (in_array($ext, $video_exts)) {
            // É Vídeo
            echo "<h2>Reconhecido: VÍDEO</h2>";
            return enviaVideo($phone, $message_or_url, $caption, $api_config, $db_config);
            
        } elseif (in_array($ext, $audio_exts)) {
            // É Áudio
            echo "<h2>Reconhecido: ÁUDIO</h2>";
            // Áudio não tem legenda (caption) na API do Z-API
            return enviaAudio($phone, $message_or_url, $api_config, $db_config);
            
        } else {
            // URL de um tipo desconhecido (PDF, DOCX, etc.)
            echo "<h2>Reconhecido: ARQUIVO (não suportado)</h2>";
            $text_message = "Te enviei um arquivo que não consigo processar, aqui está o link: " . $message_or_url;
            return enviaTexto($phone, $text_message, $api_config, $db_config);
        }

    } else {
        // Não é uma URL, então é texto!
        echo "<h2>Reconhecido: TEXTO</h2>";
        return enviaTexto($phone, $message_or_url, $api_config, $db_config);
    }
}
function enviaTexto($phone, $message, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-text';
    $payload = ['phone' => $phone, 'message' => $message];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $message, 'text', null);
    }
    return $resultado;
}
// --- ENVIAR IMAGEM ---
function enviaImagem($phone, $public_image_url, $caption, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-image'; 
    $payload = ['phone' => $phone, 'image' => $public_image_url, 'caption' => $caption];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaImagem): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'image/jpeg', $public_image_url);
    }
    return $resultado;
}
// --- ENVIAR ÁUDIO ---
function enviaAudio($phone, $public_audio_url, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-audio'; 
    $payload = ['phone' => $phone, 'audio' => $public_audio_url];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaAudio): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, null, 'audio/ogg', $public_audio_url);
    }
    return $resultado;
}
// --- ENVIAR VÍDEO ---
function enviaVideo($phone, $public_video_url, $caption, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-video'; 
    $payload = ['phone' => $phone, 'video' => $public_video_url, 'caption' => $caption];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    echo "<strong>DEBUG (enviaVideo): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'video/mp4', $public_video_url);
    }
    return $resultado;
}
function callZApi($api_url, $token, $payload)
{
    $jsonData = json_encode($payload);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL            => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $jsonData,
        CURLOPT_HTTPHEADER     => array(
            "client-token: $token",
            "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $response = curl_exec($curl);
    $err      = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return json_encode(['error' => 'cURL Error', 'message' => $err]);
    } else {
        return $response;
    }
}
function salvarEnvioNoDB($db_config, $api_response, $sender_phone, $receiver_phone, $message_text, $media_type, $media_url)
{
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
        $message_id = $api_response['zaapId'] ?? 'envio-' . microtime(true);
        $sender_name = "Você (Sistema)";
        $api_timestamp = round(microtime(true) * 1000);
        $sql = "INSERT IGNORE INTO whatsapp_messages 
                    (message_id, sender_phone, receiver_phone, message_text, sender_name, api_timestamp, media_type, media_url)
                VALUES 
                    (:message_id, :sender_phone, :receiver_phone, :message_text, :sender_name, :api_timestamp, :media_type, :media_url)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'message_id' => $message_id,
            'sender_phone' => $sender_phone,
            'receiver_phone' => $receiver_phone,
            'message_text' => $message_text,
            'sender_name' => $sender_name,
            'api_timestamp' => $api_timestamp,
            'media_type' => $media_type,
            'media_url' => $media_url
        ]);
        return "<h3 style='color:green;'>Mensagem ENVIADA salva no banco com sucesso!</h3>";
    } catch (\PDOException $e) {
        return "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO: " . $e->getMessage() . "</h3>";
    }
}
?>