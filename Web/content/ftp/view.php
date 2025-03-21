<?php
session_start();

if(!isset($_SESSION['uname'])){
    header("location: ../index.html");
    session_destroy();
    exit;
}

// Include error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'config.php';
$sftp = initializeSFTP($host, $username, $password);

if (!isset($_GET['file'])) {
    die("No file specified");
}

$filePath = $_GET['file'];
$fileName = basename($filePath);
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Check if the file exists
if (!$sftp->stat($filePath)) {
    die("File not found: $filePath");
}

// Define supported file types
$imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
$videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];

// Check if file type is supported
$isImage = in_array($fileExtension, $imageTypes);
$isVideo = in_array($fileExtension, $videoTypes);

if (!$isImage && !$isVideo) {
    die("Unsupported file type");
}

// Create a temporary file to store the content
$tempFile = tempnam(sys_get_temp_dir(), 'media_');
$sftp->get($filePath, $tempFile);

// Get file MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $tempFile);
finfo_close($finfo);

function streamFile($filepath, $mimeType) {
    $filesize = filesize($filepath);
    $offset = 0;
    $length = $filesize;

    // Handle range requests (for video seeking)
    if (isset($_SERVER['HTTP_RANGE'])) {
        // Parse the range header
        $ranges = array_map(
            'trim',
            explode(',', preg_replace('/bytes=/', '', $_SERVER['HTTP_RANGE']))
        );
        
        // Multiple ranges could be specified at the same time
        // But we'll only support a single range
        $ranges = explode('-', $ranges[0]);
        
        $offset = intval($ranges[0]);
        
        if (isset($ranges[1]) && is_numeric($ranges[1])) {
            $end = intval($ranges[1]);
            $length = $end - $offset + 1;
        } else {
            $length = $filesize - $offset;
        }

        // Return 206 Partial Content
        header('HTTP/1.1 206 Partial Content');
        header('Content-Range: bytes ' . $offset . '-' . ($offset + $length - 1) . '/' . $filesize);
    }

    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    header("Content-Length: $length");
    
    // Output the file
    $fp = fopen($filepath, 'rb');
    fseek($fp, $offset);
    $buffer = 1024 * 8;
    $sent = 0;
    
    while ($sent < $length && !feof($fp) && connection_status() == 0) {
        $amount = min($buffer, $length - $sent);
        echo fread($fp, $amount);
        $sent += $amount;
        flush();
    }
    
    fclose($fp);
    // Keep the temp file until the script ends, then PHP will automatically clean it up
    // We won't call unlink() here to allow for multiple range requests
    exit;
}

// If direct streaming is requested
if (isset($_GET['stream'])) {
    streamFile($tempFile, $mimeType);
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light' ?>">
<head>
    <title>Media Viewer - <?= htmlspecialchars($fileName) ?></title>
    <link rel="icon" href="../../img/favicon.ico" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../../css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="../../js/bootstrap.bundle.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }
        
        .custom-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            max-width: 100%;
        }
        
        .media-container {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 100%;
            max-height: 80vh;
            margin: 0 auto;
            overflow: visible;
            flex-direction: column;
        }
        
        .media-container img {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
        }
        
        .media-container video {
            max-width: 100%;
            max-height: 70vh;
        }
        
        .controls {
            margin: 20px 0;
            text-align: center;
        }
        
        footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid #ddd;
            width: 100%;
        }
        
        .hover-effect {
            transition: opacity 0.3s ease;
        }
        
        .hover-effect:hover {
            opacity: 0.8;
        }
        
        .theme-light .dark-logo {
            display: none;
        }
        
        .theme-dark .light-logo {
            display: none;
        }
        .file-info table {
            table-layout: fixed;
            word-wrap: break-word;
        }

        @media (max-width: 576px) {
            .file-info table {
                width: 95% !important;
            }
            .file-info th {
                width: 40%;
            }
            .file-info td {
                width: 60%;
            }
        }
    </style>
</head>
<body class="text-center">
<div class="d-flex justify-content-end p-3">
    <button id="themeToggle" class="btn btn-sm theme-toggle">
        <i class="bi"></i>
        <span id="themeText"></span>
    </button>
</div>
<div class="custom-container">
    <header class="row border-bottom m-5">
        <h1>Media Viewer</h1>
        <div class="mb-3 p-3">
            <a href="index.php?path=<?= urlencode(dirname($filePath)) ?>" class="btn btn-primary">Back to Files</a>
        </div>
    </header>

    <section class="row">
        <article class="col-12">
            <div class="media-container">
                <?php if ($isImage): ?>
                    <img src="view.php?file=<?= urlencode($filePath) ?>&stream=1" alt="<?= htmlspecialchars($fileName) ?>" class="img-fluid">
                <?php elseif ($isVideo): ?>
                    <video id="videoPlayer" controls autoplay>
                        <source src="view.php?file=<?= urlencode($filePath) ?>&stream=1" type="<?= $mimeType ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>

                <?php if (isset($_SESSION["downPer"]) && $_SESSION["downPer"] == true) : ?>
                <a href="download.php?file=<?= urlencode($filePath) ?>" class="btn btn-success m-3">Download</a>
                <?php endif; ?>
            </div>

            <div class="file-info mt-4">
                <h4>File Information</h4>
                <table class="table table-bordered w-auto mx-auto text-wrap">
                    <tr>
                        <th>File Name</th>
                        <td class="text-break"><?= htmlspecialchars($fileName) ?></td>
                    </tr>
                    <tr>
                        <th>File Type</th>
                        <td><?= htmlspecialchars(strtoupper($fileExtension)) ?></td>
                    </tr>
                    <tr>
                        <th>MIME Type</th>
                        <td class="text-break"><?= htmlspecialchars($mimeType) ?></td>
                    </tr>
                    <tr>
                        <th>File Size</th>
                        <td><?= formatBytes($sftp->stat($filePath)['size']) ?></td>
                    </tr>
                </table>
            </div>
        </article>
    </section>

    <footer class="d-flex flex-column justify-content-center align-items-center p-3 border-top gap-3 m-3">
        <span class="text-muted">Developed by Michal Sedlák</span>
        <div class="d-flex gap-3">
            <a href="https://github.com/michalcz10/USB-RAID-pole" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="../../img/GitHub_Logo.png" alt="GitHub Logo" class="img-fluid hover-effect light-logo" style="height: 32px;">
                <img src="../../img/GitHub_Logo_White.png" alt="GitHub Logo" class="img-fluid hover-effect dark-logo" style="height: 32px;">
            </a>
            <a href="https://app.freelo.io/public/shared-link-view/?a=81efbcb4df761b3f29cdc80855b41e6d&b=4519c717f0729cc8e953af661e9dc981" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="../../img/freelo-logo-rgb.png" alt="Freelo Logo" class="img-fluid hover-effect light-logo" style="height: 24px;">
                <img src="../../img/freelo-logo-rgb-on-dark.png" alt="Freelo Logo" class="img-fluid hover-effect dark-logo" style="height: 24px;">
            </a>
        </div>
    </footer>
</div>

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

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Clean up the temporary file if not streamed
if (!isset($_GET['stream'])) {
    unlink($tempFile);
}
// At the very end of your script, after everything else:
register_shutdown_function(function() use ($tempFile) {
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
});
?>