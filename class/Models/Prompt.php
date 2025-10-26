<?php
declare(strict_types=1);

namespace App\Models;

class Prompt
{
    public int $id_prompt;
    public string $prompt_type;
    public string $description;
    public string $prompt_text;

    public function __construct(int $id_prompt, string $prompt_type, string $description, string $prompt_text)
    {
        $this->id_prompt = $id_prompt;
        $this->prompt_type = $prompt_type;
        $this->description = $description;
        $this->prompt_text = $prompt_text;
    }
}
