<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * ===================================================================
 * CONFIGURAÇÕES DO BANCO DE DADOS
 * ===================================================================
 */
$db_config = [
    'host' => 'localhost',
    'name' => 'u490880839_7xii0',
    'user' => 'u490880839_7ZrhP',
    'pass' => '&Senha121&', // Sua senha
    'charset' => 'utf8mb4'
];
// ===================================================================


/**
 * ===================================================================
 * FUNÇÃO GENÉRICA PARA CHAMAR A API (cURL) - (Sem alterações)
 * ===================================================================
 */
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

/**
 * ===================================================================
 * FUNÇÃO PARA SALVAR A MENSAGEM *ENVIADA* NO BANCO - (Sem alterações)
 * ===================================================================
 */
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


/**
 * ===================================================================
 * FUNÇÕES PRINCIPAIS DE ENVIO (TEXTO, IMAGEM, ETC.)
 * ===================================================================
 */

// --- 1. ENVIAR TEXTO (Correta) ---
function enviaTexto($phone, $message, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-text';
    $payload = [
        'phone' => $phone,
        'message' => $message
    ];
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $message, 'text', null);
    }
    return $resultado;
}

// --- 2. ENVIAR IMAGEM (CORRIGIDA) ---
function enviaImagem($phone, $public_image_url, $caption, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-image'; 
    $payload = [
        'phone' => $phone,
        'image' => $public_image_url, // <-- CORREÇÃO AQUI (de 'imageUrl' para 'image')
        'caption' => $caption
    ];
    
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);

    // Linha de Debug (pode apagar depois)
    echo "<strong>DEBUG (enviaImagem): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";

    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'image/jpeg', $public_image_url);
    }
    return $resultado;
}

// --- 3. ENVIAR ÁUDIO (NOVA FUNÇÃO) ---
function enviaAudio($phone, $public_audio_url, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-audio'; 
    $payload = [
        'phone' => $phone,
        'audio' => $public_audio_url, // <-- PARÂMETRO CORRETO: 'audio'
    ];
    
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    
    echo "<strong>DEBUG (enviaAudio): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";

    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, null, 'audio/ogg', $public_audio_url);
    }
    return $resultado;
}

// --- 4. ENVIAR VÍDEO (NOVA FUNÇÃO) ---
function enviaVideo($phone, $public_video_url, $caption, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-video'; 
    $payload = [
        'phone' => $phone,
        'video' => $public_video_url, // <-- PARÂMETRO CORRETO: 'video'
        'caption' => $caption
    ];
    
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);
    
    echo "<strong>DEBUG (enviaVideo): Resposta completa da API:</strong> " . htmlspecialchars($resultado) . "<hr>";

    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB($db_config, $resposta_array, $api_config['seu_telefone_conectado'], $phone, $caption, 'video/mp4', $public_video_url);
    }
    return $resultado;
}

/**
 * ===================================================================
 * BLOCO DE TESTE
 * ===================================================================
 */

echo "<h1>Teste de Envio Z-API</h1>";

// 1. Defina os dados da sua API
$api_config = [
    'base_url' => 'https://api.z-api.io/instances/3E93D7FC741B61AF08719E400CFFE64E',
    'token_url' => '/token/DD42686BD48BB58A1D9D412D',
    'token_security' => 'F7a1f9dcc50a94d12aa5f8b4db10a6b78S', 
    'seu_telefone_conectado' => '5521965368839' // O número do SEU celular
];

$telefone_para_teste = '5521992491608'; // O número do CLIENTE


// --- ESCOLHA O TESTE QUE QUER RODAR ---

/* // --- TESTE DE IMAGEM (COM URL PÚBLICA DO IMGUR) ---
echo "<h2>Enviando Imagem (Teste do Imgur)...</h2>";
$url_publica_da_imagem = 'hhttps://studio401.com.br/api_whatsapp/uploads/Teste2.png'; // Imagem de teste 100% pública
$legenda_da_imagem = 'Teste com código CORRIGIDO. Esta deve chegar. ' . date('H:i:s');
$resultado_imagem = enviaImagem($telefone_para_teste, $url_publica_da_imagem, $legenda_da_imagem, $api_config, $db_config);
echo "<strong>Resposta Bruta da API:</strong> " . htmlspecialchars($resultado_imagem); */



// --- TESTE DE VÍDEO (URL PÚBLICA) ---
echo "<h2>Enviando Vídeo...</h2>";
$url_publica_video = 'https://studio401.com.br/api_whatsapp/uploads/video1.mp4'; // URL de vídeo de teste
$legenda_video = 'Teste de envio de vídeo. ' . date('H:i:s');
$resultado_video = enviaVideo($telefone_para_teste, $url_publica_video, $legenda_video, $api_config, $db_config);
echo "<strong>Resposta Bruta da API:</strong> " . htmlspecialchars($resultado_video);


/*
// --- TESTE DE ÁUDIO (URL PÚBLICA) ---
echo "<h2>Enviando Áudio...</h2>";
$url_publica_audio = 'https://www.w3schools.com/html/horse.ogg'; // URL de áudio de teste
$resultado_audio = enviaAudio($telefone_para_teste, $url_publica_audio, $api_config, $db_config);
echo "<strong>Resposta Bruta da API:</strong> " . htmlspecialchars($resultado_audio);
*/
?>