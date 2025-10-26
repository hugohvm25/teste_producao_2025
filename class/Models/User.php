<?php
declare(strict_types=1);

namespace App\Models;

class User
{
    public int $id;
    public string $firstname;
    public string $lastname;
    public string $email;
    public ?string $phone2;
    public ?string $city;

    public function __construct(
        int $id,
        string $firstname,
        string $lastname,
        string $email,
        ?string $phone2,
        ?string $city
    ) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->phone2 = $phone2;
        $this->city = $city;
    }
}
