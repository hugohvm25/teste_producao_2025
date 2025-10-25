<?php

// 1. Defina sua Chave de API
// NUNCA exponha esta chave publicamente. Use variáveis de ambiente em produção.
$apiKey = 'AIzaSyAE1zNhiQxRM6yR7_gnzZzIXTrZ4qjKnmk';

// Modelo com suporte a áudio (ex.: gemini-2.5-flash)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// Caminho do áudio local
$audioPath = __DIR__ . '/audio.ogg';

// Detecta MIME (requer fileinfo). Ajuste manualmente se precisar: 'audio/ogg', 'audio/mpeg', 'audio/wav', etc.
$mimeType = function_exists('mime_content_type') ? mime_content_type($audioPath) : 'audio/ogg';

// Lê e codifica o áudio em base64
$audioData = base64_encode(file_get_contents($audioPath));

// Prompt pedindo transcrição VERBATIM (sem interpretar)
$prompt = "Transcreva integralmente (verbatim) o áudio a seguir em português do Brasil. 
Mantenha pontuação natural e quebras de linha onde fizer sentido. 
Não faça resumo, apenas a transcrição fiel.";

// Monta o payload: texto + áudio em inlineData
$data = [
  'contents' => [[
    'parts' => [
      ['text' => $prompt],
      [
        'inlineData' => [
          'mimeType' => $mimeType, // ex.: 'audio/ogg'
          'data'     => $audioData
        ]
      ]
    ]
  ]],
  // Opcional: peça resposta como texto puro
  'generationConfig' => [
    'temperature' => 0.0,
    'response_mime_type' => 'text/plain'
  ]
];

$jsonData = json_encode($data);

// cURL
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => $jsonData,
  CURLOPT_HTTPHEADER     => [
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

// Exibe transcrição
if (isset($responseData->candidates[0]->content->parts[0]->text)) {
  $transcricao = $responseData->candidates[0]->content->parts[0]->text;
  echo "<h1>Transcrição:</h1>";
  echo nl2br(htmlspecialchars($transcricao));
} elseif (isset($responseData->error)) {
  echo "<h1>Erro da API:</h1>";
  echo "<p>Mensagem: " . htmlspecialchars($responseData->error->message) . "</p>";
  echo "<h3>Detalhes do Erro:</h3><pre>" . htmlspecialchars($response) . "</pre>";
} else {
  echo "<h1>Resposta inesperada:</h1><pre>" . htmlspecialchars($response) . "</pre>";
}
?>