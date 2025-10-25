<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * ===================================================================
 * Função para enviar mensagem via Z-API
 * ===================================================================
 *
 * @param string $phone   O número do destinatário (ex: 5511999998888)
 * @param string $message A mensagem de texto
 * @param array  $api     Array contendo 'api_instance' (URL) e 'token_security' (Token)
 * @return string         Retorna a resposta da API (JSON) ou uma mensagem de erro do cURL
 */
function enviaWhatsapp($phone, $message, $api)
{
    // Pega os dados do array $api
    $api_instance   = $api['api_instance'];
    $token_security = $api['token_security'];

    // Limpa o número de telefone (seu código original)
    $phone = preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($phone)));

    // Prepara os dados como um array PHP
    $data = [
        'phone' => $phone,
        'message' => $message
    ];
    // Converte o array para JSON de forma segura (evita erros com aspas na mensagem)
    $payload = json_encode($data);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL            => $api_instance, // Usa a URL passada no array $api
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $payload, // Envia o JSON de forma segura
        CURLOPT_HTTPHEADER     => array(
            "client-token: $token_security", // Envia o token no header
            "content-type: application/json"
        ),
        
        // Ignora a verificação SSL (necessário para testes em WAMP/localhost)
        CURLOPT_SSL_VERIFYPEER => false,
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
];

// 2. Defina o destinatário e a mensagem
$telefone_para_teste = '5521992491608'; // Seu número de teste (já está certo)
$mensagem_para_teste = 'Teste final de Mensagem, somos o Teste em Produção! 🚀 Enviado às ' . date('H:i:s');

// 3. Chame a função
echo "Enviando mensagem para: $telefone_para_teste ...<br>";
$resultado = enviaWhatsapp($telefone_para_teste, $mensagem_para_teste, $api_config);

// 4. Mostre o resultado
echo "<h2>Resposta da API:</h2>";
echo "<pre>";

// Decodifica o JSON para exibir de forma legível
$resposta_array = json_decode($resultado, true);
print_r($resposta_array); 

echo "</pre>";

// Verifica se a API retornou um ID de mensagem (sinal de sucesso)
if (isset($resposta_array['zaapId'])) {
    echo "<h2>SUCESSO! Mensagem enviada.</h2>";
} elseif (isset($resposta_array['error'])) {
    echo "<h2>FALHA. A API retornou um erro.</h2>";
}

echo "<hr><strong>Resposta Bruta (JSON original):</strong> " . htmlspecialchars($resultado);

?>