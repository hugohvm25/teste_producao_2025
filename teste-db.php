<?php

    include_once("conn.php");

    $id_curso = 2;

    $sql = "SELECT * FROM kt7u_course WHERE id = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $id_curso);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        extract($row);
        
        echo $row['fullname'];
        echo "<br>";
        echo $row['shortname'];
        echo "<br>";
    }

    $id_user = 3;

    $sql = "SELECT * FROM kt7u_user WHERE id = ? ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $id_user);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row    = mysqli_fetch_array($result);

    if(!empty($row))
    {
        extract($row);
        
        echo $row['username'];
        echo "<br>";
        echo $row['firstname'];
        echo "<br>";
        echo $row['lastname'];
        echo "<br>";
        echo $row['phone2'];
        echo "<br>";
    }
