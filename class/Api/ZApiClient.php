<?php

$raw = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);


if ($err) {
$raw = json_encode(['error' => 'cURL Error', 'message' => $err], JSON_UNESCAPED_UNICODE);
}


$json = json_decode((string)$raw, true);
return [
'success' => is_array($json) && empty($json['error']),
'raw' => (string)$raw,
'json' => $json,
];
}


// Endpoints específicos
public function sendText(string $phone, string $message): array
{
return $this->post('/send-text', [
'phone' => $phone,
'message' => $message,
]);
}


public function sendImage(string $phone, string $imageUrl, ?string $caption): array
{
return $this->post('/send-image', [
'phone' => $phone,
'image' => $imageUrl,
'caption'=> $caption,
]);
}


public function sendAudio(string $phone, string $audioUrl): array
{
return $this->post('/send-audio', [
'phone' => $phone,
'audio' => $audioUrl,
]);
}


public function sendVideo(string $phone, string $videoUrl, ?string $caption): array
{
return $this->post('/send-video', [
'phone' => $phone,
'video' => $videoUrl,
'caption' => $caption,
]);
}
}


?>