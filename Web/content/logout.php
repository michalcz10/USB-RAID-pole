<?php
if(isset($_GET['logoutBtn']))
{
    session_start();

    session_destroy();
}
header("location: ../index.html");
?>