<?php
declare(strict_types=1);


namespace App\Config;


final class Config
{
/** @var array{host:string,name:string,user:string,pass:string,charset:string} */
private array $db;


/** @var array{base_url:string,token_url:string,token_security:string,seu_telefone_conectado:string} */
private array $api;


/** @param array{host:string,name:string,user:string,pass:string,charset:string} $db */
/** @param array{base_url:string,token_url:string,token_security:string,seu_telefone_conectado:string} $api */
public function __construct(array $db, array $api)
{
$this->db = $db;
$this->api = $api;
}


/** @return array{host:string,name:string,user:string,pass:string,charset:string} */
public function db(): array { return $this->db; }


/** @return array{base_url:string,token_url:string,token_security:string,seu_telefone_conectado:string} */
public function api(): array { return $this->api; }
}


?>