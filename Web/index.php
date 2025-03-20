<?php
session_start();

if (empty($_SESSION)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
} elseif (isset($_SESSION["uname"])) {
    header("location: content/ftp/index.php");
    exit();
} elseif (isset($_SESSION['login_error'])) {
    echo "<script>alert('" . $_SESSION['login_error'] . "');</script>";
    unset($_SESSION['login_error']);
}
?>


<!DOCTYPE html>
<html lang="cz">
    <head>
        <title>Login</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="css/bootstrap.css">
        <script src="js/bootstrap.bundle.js"></script>
        <style>
            .topRow{
                height: 20vh;
            }
            .fixed {
                -ms-flex: 0 0 300px;
                flex: 0 0 300px;
                min-width: 300px;
            }
            body {
                min-width: 500px;
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

                <form action="content/login.php" method="post">
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
                        <input type="submit" class="btn btn-primary" value="Login">
                    </div>
                </form>
                
            </article>

            <aside class="col">
            </aside>
        </section>
        <footer class="row m-5">
            <span>Developed by Michal Sedlák</span>
        </footer>
    </body>
</html>