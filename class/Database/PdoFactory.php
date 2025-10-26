<?php
declare(strict_types=1);


namespace App\Database;


use PDO;
use PDOException;
use RuntimeException;


final class PdoFactory
{
/** @param array{host:string,name:string,user:string,pass:string,charset:string} $db */
public static function fromMysql(array $db): PDO
{
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']);
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
try {
return new PDO($dsn, $db['user'], $db['pass'], $options);
} catch (PDOException $e) {
throw new RuntimeException('Erro ao conectar no MySQL: ' . $e->getMessage(), 0, $e);
}
}
}


?>