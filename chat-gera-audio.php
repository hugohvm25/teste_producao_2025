<?php

// 1. Defina sua Chave de API
// NUNCA exponha esta chave publicamente. Use variáveis de ambiente em produção.
$apiKey = 'AIzaSyAE1zNhiQxRM6yR7_gnzZzIXTrZ4qjKnmk';

// Texto de entrada
$texto = "Olá Rafael, tudo bem? Senti sua falta aqui no curso de atualização, quer marcar uma conversa pra te atualizar?";

// Endpoint (REST). Usaremos o header x-goog-api-key.
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-tts:generateContent';

// Monta o corpo conforme a doc oficial:
// - responseModalities: ["AUDIO"]
// - speechConfig.voiceConfig.prebuiltVoiceConfig.voiceName: "Kore"
$body = [
  "contents" => [[
    "parts" => [["text" => $texto]]
  ]],
  "generationConfig" => [
    "responseModalities" => ["AUDIO"],
    "speechConfig" => [
      "voiceConfig" => [
        "prebuiltVoiceConfig" => [
          "voiceName" => "Kore"  // troque por outra voz se quiser
        ]
      ]
    ]
  ],
  // Opcional (o path já indica o modelo, mas a doc também mostra esse campo):
  "model" => "gemini-2.5-flash-preview-tts"
];

// ---- Chamada HTTP com cURL ----
$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey
  ],
  CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE)
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
  die('Erro cURL: ' . curl_error($ch));
}
curl_close($ch);

// ---- Trata resposta ----
$res = json_decode($response, true);

// Caminho do áudio inline (base64)
$dataPath = $res['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? null;

if (!$dataPath) {
  echo "❌ Falha ao gerar áudio:\n";
  print_r($res);
  exit;
}

// Decodifica PCM bruto (s16le, 24 kHz, mono)
$pcm = base64_decode($dataPath);

// Salva como WAV gerando o cabeçalho RIFF
function savePcmAsWav(string $pcmData, string $file, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): void {
  $byteRate   = $sampleRate * $channels * ($bitsPerSample / 8);
  $blockAlign = $channels * ($bitsPerSample / 8);
  $dataSize   = strlen($pcmData);
  $chunkSize  = 36 + $dataSize;

  $header =
    "RIFF" .
    pack('V', $chunkSize) .
    "WAVE" .
    "fmt " .
    pack('V', 16) .              // Subchunk1Size (PCM)
    pack('v', 1) .               // AudioFormat (1=PCM)
    pack('v', $channels) .
    pack('V', $sampleRate) .
    pack('V', $byteRate) .
    pack('v', $blockAlign) .
    pack('v', $bitsPerSample) .
    "data" .
    pack('V', $dataSize);

  file_put_contents($file, $header . $pcmData);
}

$file = 'voz_gemini.wav';
savePcmAsWav($pcm, $file);
echo "✅ Áudio gerado: {$file}\n";