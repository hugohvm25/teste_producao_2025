<?php

// 1. Defina sua Chave de API
// NUNCA exponha esta chave publicamente. Use variáveis de ambiente em produção.
$apiKey = 'AIzaSyAE1zNhiQxRM6yR7_gnzZzIXTrZ4qjKnmk';

// Modelo com visão (ex.: gemini-2.5-flash)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// Caminho da imagem local
$imagePath = 'teste.webp';

// Mime type (requer extensão fileinfo habilitada)
$mimeType = function_exists('mime_content_type')
  ? mime_content_type($imagePath)
  : 'image/jpeg'; // fallback

// Lê e codifica a imagem em base64
$imageData = base64_encode(file_get_contents($imagePath));

// Prompt + imagem como "parts"
$data = [
  'contents' => [[
    'parts' => [
      ['text' => "Descreva o que você esta vendo na imagem e o que ela tem de propriedade."],
      [
        'inlineData' => [
          'mimeType' => $mimeType,
          'data' => $imageData
        ]
      ]
    ]
  ]],
  // Opcional: configurações de geração
  'generationConfig' => [
    'temperature' => 0.7,
    // 'maxOutputTokens' => 512,
  ]
];

$jsonData = json_encode($data);

// cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $jsonData,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
  ],
  CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
  echo 'Erro no cURL: ' . curl_error($ch);
  curl_close($ch);
  exit;
}

curl_close($ch);

$responseData = json_decode($response);

// Lê o texto retornado
if (isset($responseData->candidates[0]->content->parts[0]->text)) {
  $generatedText = $responseData->candidates[0]->content->parts[0]->text;
  echo "<h1>Resposta do Gemini:</h1>";
  echo nl2br(htmlspecialchars($generatedText));
} elseif (isset($responseData->error)) {
  echo "<h1>Erro da API:</h1>";
  echo "<p>Mensagem: " . htmlspecialchars($responseData->error->message) . "</p>";
  echo "<h3>Detalhes do Erro:</h3><pre>" . htmlspecialchars($response) . "</pre>";
} else {
  echo "<h1>Resposta inesperada:</h1><pre>" . htmlspecialchars($response) . "</pre>";
}
?>