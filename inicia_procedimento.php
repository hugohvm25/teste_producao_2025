<?php

    include_once __DIR__ . '/conn.php'; 

    //Inicia com usuario do Rafael
    $user = 3;

    //Inicia com curso "Uso de Adornos e Riscos Associados"
    $id_curso = 3;

    //Inicia prompt com notificação
    $id_prompt = 1;

    //Busca usuario e retorna os dados
    $sql = "SELECT * FROM kt7u_user WHERE id = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $user);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {        
        $firstname = $row['firstname'];
        $lastname  = $row['lastname'];
        $email     = $row['email'];
        $phone2    = $row['phone2'];
        $city      = $row['city'];
    }

    //Busca notificação para o usuario
    $sql = "SELECT * FROM kt7u_notifications WHERE useridto = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $user);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        $subject     = $row['subject'];
        $fullmessage = $row['fullmessage'];
        $contexturl  = $row['contexturl'];
    }

    //Busca descrição do curso
    $sql = "SELECT * FROM kt7u_course WHERE id = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id_curso);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        $fullname   = $row['fullname'];
        $shortname  = $row['shortname'];
    }

    //Busca descrição do prompt
    $sql = "SELECT * FROM prompt WHERE id_prompt = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id_prompt);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        $prompt_type = $row['prompt_type'];
        $description = $row['description'];
        $prompt_text = $row['prompt_text'];
    }

    //Busca andamento do fluxo
    $sql = "SELECT * FROM fluxo WHERE id_user = ? and id_course = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $user, $id_course);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        $id_status = $row['id_status'];
        $pref_type = $row['pref_type'];
    }

    //Busca historico de mensagem
    $sql = "SELECT * FROM whatsapp_messages WHERE sender_phone = ? or receiver_phone = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $phone2, $phone2);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $history_message = array();

    while($row = mysqli_fetch_array($result))
    {
        $history_message = array(
            'sender_phone'   => $row['sender_phone'],
            'receiver_phone' => $row['receiver_phone'],
            'message_text'   => $row['message_text'],
            'media_type'     => $row['media_type'],
            'media_url'      => $row['media_url'],
            'received_at'    => $row['received_at'],
        );
    }


?>