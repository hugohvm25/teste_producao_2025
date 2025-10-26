<?php
$this->api = $api;
$this->logger = $logger;
$this->systemPhone = $systemPhone; // número do SEU celular (remetente)
}


/**
* Função mestre equivalente ao enviarMensagem() legado.
* Decide o tipo e dispara para o endpoint correto, persistindo o envio.
*
* @param string $phone Número do cliente (destinatário)
* @param string $messageOrUrl Texto OU URL (imagem/áudio/vídeo)
* @param string|null $caption Legenda opcional para imagem/vídeo
* @return array{raw:string,json:array|null}
*/
public function send(string $phone, string $messageOrUrl, ?string $caption = null): array
{
$detector = new MessageTypeDetector();


if ($detector->isUrl($messageOrUrl)) {
$kind = $detector->detectFromUrl($messageOrUrl);
switch ($kind) {
case 'image':
$resp = $this->api->sendImage($phone, $messageOrUrl, $caption);
$this->logger->logSent($resp['json'] ?? [], $this->systemPhone, $phone, $caption, 'image/jpeg', $messageOrUrl);
return ['raw' => $resp['raw'], 'json' => $resp['json']];


case 'video':
$resp = $this->api->sendVideo($phone, $messageOrUrl, $caption);
$this->logger->logSent($resp['json'] ?? [], $this->systemPhone, $phone, $caption, 'video/mp4', $messageOrUrl);
return ['raw' => $resp['raw'], 'json' => $resp['json']];


case 'audio':
$resp = $this->api->sendAudio($phone, $messageOrUrl);
$this->logger->logSent($resp['json'] ?? [], $this->systemPhone, $phone, null, 'audio/ogg', $messageOrUrl);
return ['raw' => $resp['raw'], 'json' => $resp['json']];


default:
// arquivo não suportado => envia texto com o link
$text = 'Te enviei um arquivo que não consigo processar, aqui está o link: ' . $messageOrUrl;
$resp = $this->api->sendText($phone, $text);
$this->logger->logSent($resp['json'] ?? [], $this->systemPhone, $phone, $text, 'text', null);
return ['raw' => $resp['raw'], 'json' => $resp['json']];
}
}


// Se não for URL, é texto
$resp = $this->api->sendText($phone, $messageOrUrl);
$this->logger->logSent($resp['json'] ?? [], $this->systemPhone, $phone, $messageOrUrl, 'text', null);
return ['raw' => $resp['raw'], 'json' => $resp['json']];
}
}


?>