<?php

    $servername = "srv1056.hstgr.io"; // geralmente 'localhost'
    $dbname     = "u490880839_7xii0";
    $username   = "u490880839_7ZrhP";
    $password   = "&Senha121&";

    // Cria a conexão com o banco de dados
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verifica a conexão
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
?>