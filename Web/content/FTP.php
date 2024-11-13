<?php
    session_start();

    if(!isset($_SESSION['uname'])){
        header("location: ../index.html");
        session_destroy();
    }
?>


    <head>
        <title>FTP</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <script src="../js/bootstrap.bundle.js"></script>
        <style>
            .topRow{
                height: 20vh;
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

            <article class="col-8 border border-2 border-primary rounded p-2">
                <div class="col">

                </div>

                <div class="col">
                    
                </div>

                <div class="col">
                    
                </div>
            </article>

            <aside class="col">
            </aside>
        </section>
    </body>
