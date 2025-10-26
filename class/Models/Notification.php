<?php
declare(strict_types=1);

namespace App\Models;

class Notification
{
    public int $id;
    public int $useridto;
    public string $subject;
    public string $fullmessage;
    public ?string $contexturl;

    public function __construct(
        int $id,
        int $useridto,
        string $subject,
        string $fullmessage,
        ?string $contexturl
    ) {
        $this->id = $id;
        $this->useridto = $useridto;
        $this->subject = $subject;
        $this->fullmessage = $fullmessage;
        $this->contexturl = $contexturl;
    }
}
