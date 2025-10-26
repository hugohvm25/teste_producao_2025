<?php
declare(strict_types=1);

namespace App\Models;

class Fluxo
{
    public int $id_user;
    public int $id_course;
    public int $id_status;
    public ?string $pref_type;

    public function __construct(int $id_user, int $id_course, int $id_status, ?string $pref_type)
    {
        $this->id_user = $id_user;
        $this->id_course = $id_course;
        $this->id_status = $id_status;
        $this->pref_type = $pref_type;
    }
}
