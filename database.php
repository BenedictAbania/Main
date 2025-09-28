<?php
    $host = "db";
    $user = "root";
    $password = "rootpassword";
    $dbname = "LibraryManagementDB";
    $conn = new mysqli($host,$user,$password,$dbname);
    if($conn->connect_error){
        die("connection failed: " . $conn->connect_error);
    }
?>