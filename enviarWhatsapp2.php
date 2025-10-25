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

/**
 * ===================================================================
 * FUNÇÃO GENÉRICA PARA CHAMAR A API (cURL)
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
        CURLOPT_TIMEOUT        => 30, // Timeout maior para envio de mídia
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $jsonData,
        CURLOPT_HTTPHEADER     => array(
            "client-token: $token",
            "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false, // Para WAMP/localhost. Mantenha se seu Hostinger tiver problemas
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
 * FUNÇÃO PARA SALVAR A MENSAGEM *ENVIADA* NO BANCO
 * ===================================================================
 */
function salvarEnvioNoDB($db_config, $api_response, $sender_phone, $receiver_phone, $message_text, $media_type, $media_url)
{
    global $db_host, $db_name, $db_user, $db_pass, $charset;
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], $options);
        
        $message_id = $api_response['zaapId'] ?? 'envio-' . microtime(true);
        $sender_name = "Você (Sistema)"; // Você está enviando
        $api_timestamp = round(microtime(true) * 1000); // Agora

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

// --- 1. ENVIAR TEXTO ---
function enviaTexto($phone, $message, $api_config, $db_config)
{
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-text';
    $payload = [
        'phone' => $phone,
        'message' => $message
    ];
    
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);

    // Se a API enviou, salva no banco
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB(
            $db_config, 
            $resposta_array, 
            $api_config['seu_telefone_conectado'], // sender_phone (Você)
            $phone,                               // receiver_phone (Cliente)
            $message,                             // message_text
            'text',                               // media_type
            null                                  // media_url
        );
    }
    return $resultado;
}

// --- 2. ENVIAR IMAGEM ---
function enviaImagem($phone, $public_image_url, $caption, $api_config, $db_config)
{
    // ASSUMINDO O ENDPOINT /send-image
    $api_url = $api_config['base_url'] . $api_config['token_url'] . '/send-image'; 
    $payload = [
        'phone' => $phone,
        'imageUrl' => $public_image_url, // ASSUMINDO O PARÂMETRO 'imageUrl'
        'caption' => $caption
    ];
    
    $resultado = callZApi($api_url, $api_config['token_security'], $payload);
    $resposta_array = json_decode($resultado, true);

    // Se a API enviou, salva no banco
    if (isset($resposta_array['zaapId'])) {
        echo salvarEnvioNoDB(
            $db_config, 
            $resposta_array, 
            $api_config['seu_telefone_conectado'], // sender_phone (Você)
            $phone,                               // receiver_phone (Cliente)
            $caption,                             // message_text (legenda)
            'image/jpeg',                         // media_type (apenas um palpite)
            $public_image_url                     // media_url (link público que você enviou)
        );
    }
    return $resultado;
}

// (Você pode adicionar enviaAudio e enviaVideo aqui, copiando o modelo de enviaImagem)


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


// --- TESTE 1: ENVIAR TEXTO (como antes) ---
/*
echo "<h2>Enviando Texto...</h2>";
$mensagem_para_teste = 'Teste de envio de texto refatorado! ' . date('H:i:s');
$resultado_texto = enviaTexto($telefone_para_teste, $mensagem_para_teste, $api_config, $db_config);
echo "<strong>Resposta Bruta da API:</strong> " . htmlspecialchars($resultado_texto);
*/


// --- TESTE 2: ENVIAR IMAGEM ---
// PASSO 1: Envie uma foto para sua pasta /api_whatsapp/uploads/ (ex: 'foto_teste.jpg')
// PASSO 2: Coloque a URL pública dela aqui
$url_publica_da_imagem = 'https://studio401.com.br/api_whatsapp/uploads/Teste2.png'; // << MUDE AQUI
$legenda_da_imagem = 'Olha a imagem que estou te enviando!';

echo "<h2>Enviando Imagem...</h2>";
$resultado_imagem = enviaImagem($telefone_para_teste, $url_publica_da_imagem, $legenda_da_imagem, $api_config, $db_config);
echo "<strong>Resposta Bruta da API:</strong> " . htmlspecialchars($resultado_imagem);

?>