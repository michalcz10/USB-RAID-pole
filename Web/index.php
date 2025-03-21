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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="js/bootstrap.bundle.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding-top: 7%;
        }

        .fixed {
            -ms-flex: 0 0 300px;
            flex: 0 0 300px;
            min-width: 300px;
        }

        .hover-effect {
            transition: opacity 0.3s ease;
        }

        .hover-effect:hover {
            opacity: 0.8;
        }

        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .theme-light .dark-logo {
            display: none;
        }

        .theme-dark .light-logo {
            display: none;
        }
    </style>
</head>
<body class="container-fluid text-center">
    <div class="d-flex justify-content-end p-3">
        <button id="themeToggle" class="btn btn-sm theme-toggle">
            <i class="bi"></i>
            <span id="themeText"></span>
        </button>
    </div>
    
    <header class="row border-bottom m-5">
        <h1>USB RAID Array</h1>
    </header>

    <main>
        <section class="row m-3">
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
        </section>
    </main>

    <footer class="d-flex flex-column justify-content-center align-items-center p-3 border-top gap-3">
        <span class="text-muted">Developed by Michal Sedlák</span>
        <div class="d-flex gap-3">
            <a href="https://github.com/michalcz10/USB-RAID-pole" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="img/GitHub_Logo.png" alt="GitHub Logo" class="img-fluid hover-effect light-logo" style="height: 32px;">
                <img src="img/GitHub_Logo_White.png" alt="GitHub Logo" class="img-fluid hover-effect dark-logo" style="height: 32px;">
            </a>
            <a href="https://app.freelo.io/public/shared-link-view/?a=81efbcb4df761b3f29cdc80855b41e6d&b=4519c717f0729cc8e953af661e9dc981" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="img/freelo-logo-rgb.png" alt="Freelo Logo" class="img-fluid hover-effect light-logo" style="height: 24px;">
                <img src="img/freelo-logo-rgb-on-dark.png" alt="Freelo Logo" class="img-fluid hover-effect dark-logo" style="height: 24px;">
            </a>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;
            const themeText = document.getElementById('themeText');
            const themeIcon = themeToggle.querySelector('.bi');
            
            function setTheme(theme) {
                html.setAttribute('data-bs-theme', theme);
                document.body.classList.remove('theme-light', 'theme-dark');
                document.body.classList.add('theme-' + theme);
                localStorage.setItem('theme', theme);
                
                if (theme === 'dark') {
                    themeText.textContent = 'Light Mode';
                    themeIcon.className = 'bi bi-sun';
                    themeToggle.classList.remove('btn-dark');
                    themeToggle.classList.add('btn-light');
                } else {
                    themeText.textContent = 'Dark Mode';
                    themeIcon.className = 'bi bi-moon';
                    themeToggle.classList.remove('btn-light');
                    themeToggle.classList.add('btn-dark');
                }
            }
            
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                setTheme(prefersDark ? 'dark' : 'light');
            }
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });
        });
    </script>
</body>
</html>