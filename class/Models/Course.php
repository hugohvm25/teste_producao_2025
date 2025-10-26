<?php
declare(strict_types=1);

namespace App\Models;

class Course
{
    public int $id;
    public string $fullname;
    public string $shortname;

    public function __construct(int $id, string $fullname, string $shortname)
    {
        $this->id = $id;
        $this->fullname = $fullname;
        $this->shortname = $shortname;
    }
}
