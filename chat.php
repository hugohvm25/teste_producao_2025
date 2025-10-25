<?php

// 1. Defina sua Chave de API
// NUNCA exponha esta chave publicamente. Use variáveis de ambiente em produção.
$apiKey = 'AIzaSyDsuRV2JKGEAO0QPOnS8N1ALYnrbzGeY2M';

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro-latest:generateContent?key=' . $apiKey;

// 3. Defina o prompt e os dados da requisição
$prompt = "Escreva uma breve descrição para um produto: um café gourmet do Brasil.";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ]
];

// 4. Converta os dados para JSON
$jsonData = json_encode($data);

// 5. Inicialize o cURL
$ch = curl_init($url);

// 6. Configure as opções do cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retorna a resposta como string
curl_setopt($ch, CURLOPT_POST, true);           // Define o método como POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Define o corpo da requisição (JSON)
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Recomenda-se manter a verificação SSL

// 7. Execute a requisição
$response = curl_exec($ch);

// 8. Verifique por erros no cURL
if (curl_errno($ch)) {
    echo 'Erro no cURL: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// 9. Feche a sessão cURL
curl_close($ch);

// 10. Decodifique a resposta JSON
$responseData = json_decode($response);

// 11. Processe e exiba a resposta
if (isset($responseData->candidates[0]->content->parts[0]->text)) {
    // Resposta de sucesso
    $generatedText = $responseData->candidates[0]->content->parts[0]->text;
    echo "<h1>Resposta do Gemini:</h1>";
    echo nl2br(htmlspecialchars($generatedText)); // nl2br para preservar quebras de linha

} elseif (isset($responseData->error)) {
    // Erro da API
    echo "<h1>Erro da API:</h1>";
    echo "<p>Mensagem: " . htmlspecialchars($responseData->error->message) . "</p>";
    
    // Imprime a resposta completa para depuração
    echo "<h3>Detalhes do Erro:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
} else {
    // Resposta inesperada
    echo "<h1>Resposta inesperada:</h1>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

?>