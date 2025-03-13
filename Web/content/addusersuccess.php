<?php
session_start();
    if (empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        session_destroy();
        header("location: ../index.html");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="cz">
    <head>
        <title>Login</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <script src="../js/bootstrap.bundle.js"></script>
        <style>
            .topRow{
                height: 20vh;
            }
            .fixed {
                -ms-flex: 0 0 300px;
                flex: 0 0 300px;
                min-width: 300px;
            }
        </style>
    </head>
    <body class="container-fluid text-center">
        <header class="row topRow p-5">
            <h1 class="border-dark border-bottom">USB Raid pole</h1>
        </header>

        <section class="row">
            <aside class="col">
            </aside>

            <article class="col border border-2 border-primary rounded p-2 fixed">
                <h4 class="text-success p-3">User added successfully</h4>
                <a class="btn btn-primary" href="adduser.html">Zpět</a>
            </article>

            <aside class="col">
            </aside>
        </section>
    </body>
</html>