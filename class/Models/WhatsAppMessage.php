<?php
declare(strict_types=1);

namespace App\Models;

class WhatsAppMessage
{
    public string $sender_phone;
    public string $receiver_phone;
    public string $message_text;
    public ?string $media_type;
    public ?string $media_url;
    public string $received_at;

    public function __construct(
        string $sender_phone,
        string $receiver_phone,
        string $message_text,
        ?string $media_type,
        ?string $media_url,
        string $received_at
    ) {
        $this->sender_phone = $sender_phone;
        $this->receiver_phone = $receiver_phone;
        $this->message_text = $message_text;
        $this->media_type = $media_type;
        $this->media_url = $media_url;
        $this->received_at = $received_at;
    }
}
