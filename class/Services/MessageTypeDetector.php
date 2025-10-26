<?php
declare(strict_types=1);


namespace App\Service;


final class MessageTypeDetector
{
/** @var string[] */
private array $imageExts = ['jpg','jpeg','png','webp','gif'];
/** @var string[] */
private array $videoExts = ['mp4','mov','3gp','mkv'];
/** @var string[] */
private array $audioExts = ['ogg','mp3','aac','opus','wav','m4a'];


public function isUrl(string $text): bool
{
$lower = strtolower($text);
return (strpos($lower, 'http://') === 0 || strpos($lower, 'https://') === 0);
}


public function detectFromUrl(string $url): string
{
$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
if (in_array($ext, $this->imageExts, true)) return 'image';
if (in_array($ext, $this->videoExts, true)) return 'video';
if (in_array($ext, $this->audioExts, true)) return 'audio';
return 'file';
}
}


?>