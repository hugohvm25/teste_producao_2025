<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db_host = 'localhost';//Host
$db_name = 'u490880839_7xii0';//NOME DO BANCO
$db_user = 'u490880839_7ZrhP';//USUÁRIO DO BANCO
$db_pass = '&Senha121&'; //SENHA DO BANCO
$charset = 'utf8mb4';

function enviaWhatsapp($phone, $message, $api)
{
    // Pega os dados do array $api
    $api_instance   = $api['api_instance'];
    $token_security = $api['token_security'];

    // Limpa o número de telefone
    $phone = preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($phone)));

    // Prepara os dados como um array PHP
    $data = [
        'phone' => $phone,
        'message' => $message
    ];
    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL            => $api_instance,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => array(
            "client-token: $token_security",
            "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false, // Para WAMP/localhost
    ));

    $response = curl_exec($curl);
    $err      = curl_error($curl);

    curl_close($curl);
    if ($err) 
    {
        return "cURL Error #:" . $err;
    } 
    else 
    {
        return $response;
    }
}

echo "<h1>Teste de Envio Z-API</h1>";

// 1. Defina os dados da sua API
$api_config = [
    'api_instance'   => 'https://api.z-api.io/instances/3E93D7FC741B61AF08719E400CFFE64E/token/DD42686BD48BB58A1D9D412D/send-text',
    'token_security' => 'F7a1f9dcc50a94d12aa5f8b4db10a6b78S', 
    'seu_telefone_conectado' => '5521965368839' 
];

// 2. Defina o destinatário e a mensagem
$telefone_para_teste = '5521992491608'; // O número do CLIENTE
$mensagem_para_teste = 'Teste final de Mensagem ENVIADA e salva no DB! 🚀 ' . date('H:i:s');

// 3. Chame a função
echo "Enviando mensagem para: $telefone_para_teste ...<br>";
$resultado = enviaWhatsapp($telefone_para_teste, $mensagem_para_teste, $api_config);

// 4. Mostre o resultado
echo "<h2>Resposta da API:</h2>";
echo "<pre>";

$resposta_array = json_decode($resultado, true);
print_r($resposta_array); 

echo "</pre>";

// 5. Verifique o SUCESSO e salve no banco
if (isset($resposta_array['zaapId'])) {
    echo "<h2>SUCESSO! Mensagem enviada.</h2>";
    echo "<p>Tentando salvar no banco de dados...</p>";

    // --- CÓDIGO PARA SALVAR NO BANCO ---
    try {
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);

        // Prepara os dados para o banco
        $message_id = $resposta_array['zaapId'];
        $sender_phone = $api_config['seu_telefone_conectado']; // NOSSO telefone
        $receiver_phone = $telefone_para_teste;              // Telefone do CLIENTE
        $message_text = $mensagem_para_teste;
        $sender_name = "Você"; // Ou 'Atendente', 'Sistema', etc.
        $api_timestamp = round(microtime(true) * 1000); // Timestamp atual em milissegundos

        $sql = "INSERT IGNORE INTO whatsapp_messages 
                    (message_id, sender_phone, receiver_phone, message_text, sender_name, api_timestamp)
                VALUES 
                    (:message_id, :sender_phone, :receiver_phone, :message_text, :sender_name, :api_timestamp)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'message_id' => $message_id,
            'sender_phone' => $sender_phone,
            'receiver_phone' => $receiver_phone,
            'message_text' => $message_text,
            'sender_name' => $sender_name,
            'api_timestamp' => $api_timestamp
        ]);

        echo "<h3 style='color:green;'>Mensagem ENVIADA salva no banco com sucesso!</h3>";

    } catch (\PDOException $e) {
        // Se der erro no banco, mostra o erro na tela
        echo "<h3 style='color:red;'>ERRO AO SALVAR NO BANCO:</h3>";
        echo "<pre>" . $e->getMessage() . "</pre>";
    }

} elseif (isset($resposta_array['error'])) {
    echo "<h2>FALHA. A API retornou um erro.</h2>";
}

echo "<hr><strong>Resposta Bruta (JSON original):</strong> " . htmlspecialchars($resultado);

?>