<?php

// 1. Defina sua Chave de API
// NUNCA exponha esta chave publicamente. Use variáveis de ambiente em produção.
$apiKey = 'AIzaSyAE1zNhiQxRM6yR7_gnzZzIXTrZ4qjKnmk';

// Modelo com suporte a vídeo/áudio (ex.: gemini-2.5-flash)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

// Caminho do vídeo local
$videoPath = __DIR__ . '/video.mp4';

// Detecta MIME (requer fileinfo). Ajuste manualmente se precisar: 'video/mp4', 'video/webm', 'video/ogg', etc.
$mimeType = function_exists('mime_content_type') ? mime_content_type($videoPath) : 'video/mp4';

// Lê e codifica o vídeo em base64
$videoData = base64_encode(file_get_contents($videoPath));

// Prompt pedindo análise estruturada (visual + áudio) e JSON como saída
$prompt = 'PROMPT
Você receberá um vídeo. 
1) Descreva, em linguagem natural, o que acontece nas cenas (ações, objetos, pessoas, locais). 
2) Faça a transcrição integral do áudio (verbatim), em português do Brasil se estiver nesse idioma; caso contrário, mantenha no idioma original e indique o idioma.
3) Crie uma linha do tempo com trechos (início/fim em segundos) resumindo o que ocorre na imagem e o que é falado.
4) Se houver textos visíveis (placas/legendas), extraia-os (OCR) com timestamps aproximados.
5) Identifique sons relevantes (música, aplausos, ruídos).

Responda em JSON **válido**, com esta estrutura exata:
{
  "visual_summary": "string",
  "audio_transcript": "string",
  "timeline": [
    { "start": 0, "end": 5, "visual": "string", "speech": "string" }
  ],
  "ocr": [
    { "time": 12.3, "text": "string" }
  ],
  "sounds": ["string"]
}
PROMPT';

// Monta o payload: texto + VÍDEO em inlineData e saída como application/json
$data = [
  'contents' => [[
    'parts' => [
      ['text' => $prompt],
      [
        'inlineData' => [
          'mimeType' => $mimeType, // ex.: 'video/mp4'
          'data'     => $videoData
        ]
      ]
    ]
  ]],
  'generationConfig' => [
    'temperature' => 0.0,
    'response_mime_type' => 'application/json'
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

// A API retorna JSON como TEXTO dentro de parts[0].text
$res = json_decode($response);
if (isset($res->candidates[0]->content->parts[0]->text)) {
  $jsonText = $res->candidates[0]->content->parts[0]->text;

  // Tenta decodificar o JSON gerado pelo modelo
  $analysis = json_decode($jsonText, true);

  echo "<h1>Análise do Vídeo</h1>";

  if (json_last_error() === JSON_ERROR_NONE && is_array($analysis)) {
    // Visual summary
    if (!empty($analysis['visual_summary'])) {
      echo "<h2>Resumo Visual</h2><p>" . nl2br(htmlspecialchars($analysis['visual_summary'])) . "</p>";
    }

    // Audio transcript
    if (!empty($analysis['audio_transcript'])) {
      echo "<h2>Transcrição do Áudio</h2><pre style='white-space:pre-wrap;'>"
         . htmlspecialchars($analysis['audio_transcript'])
         . "</pre>";
    }

    // Timeline
    if (!empty($analysis['timeline']) && is_array($analysis['timeline'])) {
      echo "<h2>Linha do Tempo</h2>";
      echo "<table border='1' cellpadding='6' cellspacing='0'><thead><tr>"
         . "<th>Início (s)</th><th>Fim (s)</th><th>Visual</th><th>Fala</th>"
         . "</tr></thead><tbody>";
      foreach ($analysis['timeline'] as $t) {
        $start = htmlspecialchars((string)($t['start'] ?? ''));
        $end   = htmlspecialchars((string)($t['end'] ?? ''));
        $vis   = htmlspecialchars((string)($t['visual'] ?? ''));
        $sp    = htmlspecialchars((string)($t['speech'] ?? ''));
        echo "<tr><td>{$start}</td><td>{$end}</td><td>{$vis}</td><td>{$sp}</td></tr>";
      }
      echo "</tbody></table>";
    }

    // OCR
    if (!empty($analysis['ocr']) && is_array($analysis['ocr'])) {
      echo "<h2>Textos Detectados (OCR)</h2><ul>";
      foreach ($analysis['ocr'] as $o) {
        $time = htmlspecialchars((string)($o['time'] ?? ''));
        $text = htmlspecialchars((string)($o['text'] ?? ''));
        echo "<li><strong>{$time}s:</strong> {$text}</li>";
      }
      echo "</ul>";
    }

    // Sounds
    if (!empty($analysis['sounds']) && is_array($analysis['sounds'])) {
      echo "<h2>Sons Relevantes</h2><ul>";
      foreach ($analysis['sounds'] as $s) {
        echo "<li>" . htmlspecialchars((string)$s) . "</li>";
      }
      echo "</ul>";
    }

  } else {
    // Se não for JSON válido, mostra bruto para depuração
    echo "<h2>Retorno (texto)</h2><pre>" . htmlspecialchars($jsonText) . "</pre>";
  }

} elseif (isset($res->error)) {
  echo "<h1>Erro da API</h1>";
  echo "<p>Mensagem: " . htmlspecialchars($res->error->message ?? 'Erro desconhecido') . "</p>";
  echo "<h3>Detalhes</h3><pre>" . htmlspecialchars($response) . "</pre>";
} else {
  echo "<h1>Resposta inesperada</h1><pre>" . htmlspecialchars($response) . "</pre>";
}