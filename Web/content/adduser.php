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
        <title>AddUser</title>
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
        <header class="row topRow border-dark border-bottom m-5">
            <h1>USB Raid pole</h1>
            <form action="logout.php" method="get">
                <div class="mb-3">
                <input type="submit" class="btn btn-danger" value="Logout" name="logoutBtn">
                </div>
            </form>
        </header>

        <section class="row">
            <aside class="col">
            </aside>

            <article class="col border border-2 border-primary rounded p-2 fixed">

                <form action="addusermanager.php" method="post">
                    <div class="mb-3">
                        <label for="uname">Username:</label>
                        <br>
                        <input type="text" name="uname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="pswd">Password:</label>
                        <br>
                        <input type="password" name="pswd" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <input type="submit" class="btn btn-primary" value="Add user">
                    </div>
                </form>
            </article>

            <aside class="col">
            </aside>
        </section>
    </body>
</html>