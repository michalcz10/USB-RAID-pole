<?php
    session_start();

    if(!isset($_SESSION['uname'])){
        header("location: ../index.html");
        session_destroy();
    }
?>