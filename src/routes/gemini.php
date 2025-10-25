<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Carrega variáveis do .env sem Composer
require_once __DIR__ . '/../helpers/env.php';
loadEnv(__DIR__ . '/../../.env');

// Verifica se foi enviada uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método não permitido. Use POST."]);
    exit;
}

// Lê JSON enviado
$input = json_decode(file_get_contents("php://input"), true);
$prompt = $input['prompt'] ?? null;
$fileBase64 = $input['file'] ?? null;
$mimeType = $input['mimeType'] ?? null;

if (!$prompt && !$fileBase64) {
    echo json_encode(["error" => "Prompt ou arquivo é obrigatório."]);
    exit;
}

$apiKey = getenv('GEMINI_API_KEY') ?: '';
if (!$apiKey) {
    echo json_encode(["error" => "Chave da API GEMINI_API_KEY não encontrada no .env"]);
    exit;
}

// Endpoint do Gemini
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

// Monta conteúdo multimodal
$contents = [];

if ($prompt) {
    $contents[] = ["parts" => [["text" => $prompt]]];
}

if ($fileBase64 && $mimeType) {
    $part = [];

    if (str_starts_with($mimeType, 'image/')) {
        $part['image'] = [
            "type" => $mimeType,
            "data" => $fileBase64
        ];
    } elseif (str_starts_with($mimeType, 'audio/')) {
        $part['audio'] = [
            "type" => $mimeType,
            "data" => $fileBase64
        ];
    }

    if ($part) {
        $contents[] = ["parts" => [$part]];
    }
}

// Corpo da requisição
$data = ["contents" => $contents];

// Envia para Gemini
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Decodifica resposta
$json = json_decode($response, true);
$outputText = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Sem resposta do Gemini.';

$responseData = [
    "prompt" => $prompt,
    "response" => $outputText,
    "file_attached" => ($fileBase64 && $mimeType) ? true : false,
    "file_type" => $mimeType ?? null
];

echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
