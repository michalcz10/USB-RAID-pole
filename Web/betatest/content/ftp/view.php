<?php
session_start();

if(!isset($_SESSION['uname'])){
    header("location: ../../index.php");
    session_destroy();
    exit;
}

// Include this at the top to see potential errors
// Comment out in production
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');

require 'config.php';
$sftp = initializeSFTP($host, $username, $password);

if (!isset($_GET['file'])) {
    die("No file specified");
}

$filePath = $_GET['file'];
$fileName = basename($filePath);
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!$sftp->stat($filePath)) {
    die("File not found: $filePath");
}

$imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
$videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
$audioTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];

$isImage = in_array($fileExtension, $imageTypes);
$isVideo = in_array($fileExtension, $videoTypes);
$isAudio = in_array($fileExtension, $audioTypes);

if (!$isImage && !$isVideo && !$isAudio) {
    die("Unsupported file type");
}

$fileSize = $sftp->stat($filePath)['size'];

$mimeMap = [
    'mp4' => 'video/mp4',
    'webm' => 'video/mp4',
    'ogg' => 'video/mp4',
    'mov' => 'video/mp4',
    'avi' => 'video/mp4',
    'mkv' => 'video/mp4',

    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',

    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'm4a' => 'audio/mp4',
    'flac' => 'audio/flac',
    'aac' => 'audio/aac'
];

$mimeType = isset($mimeMap[$fileExtension]) ? $mimeMap[$fileExtension] : 'application/octet-stream';

if (isset($_GET['stream'])) {
    // Close session to allow other scripts to run
    session_write_close();

    // Prevent output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Disable output compression
    if (ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'Off');
    }

    // Initialize range variables
    $start = 0;
    $end = $fileSize - 1;
    $length = $fileSize;

    // Handle range requests
    if (isset($_SERVER['HTTP_RANGE'])) {
        $rangeHeader = $_SERVER['HTTP_RANGE'];
        $matches = [];
        if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = intval($matches[1]);
            
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
            
            $length = $end - $start + 1;

            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        }
    }

    // Set headers for streaming
    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    header("Content-Length: $length");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Debug headers
    if (isset($_GET['debug'])) {
        header("X-Stream-Info: Chunked SFTP Streaming");
        header("X-File-Path: " . basename($filePath));
        header("X-File-Size: $fileSize");
    }

    // Set timeout to 0 to prevent script termination
    set_time_limit(0);

    // Chunk settings
    $minChunkSize = 64 * 1024;    // 64KB minimum
    $maxChunkSize = 2 * 1024 * 1024; // 2MB maximum
    $chunkSize = 256 * 1024;      // Start with 256KB

    $currentPosition = $start;
    $bytesRemaining = $length;
    $lastChunkTime = microtime(true);

    try {
        while ($bytesRemaining > 0) {
            // Check client connection and server status
            if (connection_aborted() || connection_status() !== CONNECTION_NORMAL) {
                if (isset($_GET['debug'])) {
                    error_log("Client disconnected at position $currentPosition");
                }
                break;
            }

            // Calculate adaptive chunk size
            $readSize = min($chunkSize, $bytesRemaining);
            
            // Get chunk from SFTP
            $chunkData = $sftp->get($filePath, false, $currentPosition, $readSize);
            
            if ($chunkData !== false) {
                $bytesSent = strlen($chunkData);
                
                // Output chunk
                echo $chunkData;
                $bytesRemaining -= $bytesSent;
                $currentPosition += $bytesSent;
                
                // Flush buffers
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                // Adaptive chunk sizing based on transfer speed
                $currentTime = microtime(true);
                $timeDiff = $currentTime - $lastChunkTime;
                $lastChunkTime = $currentTime;

                if ($timeDiff > 0) {
                    $speed = $bytesSent / $timeDiff; // bytes/second
                    $chunkSize = min(
                        max($minChunkSize, $chunkSize * (($speed > 512 * 1024) ? 1.5 : 0.8)),
                        $maxChunkSize
                    );
                }

                if (isset($_GET['debug'])) {
                    header("X-Chunk-Size: $bytesSent");
                    header("X-Position: $currentPosition");
                    header("X-Remaining: $bytesRemaining");
                }
            } else {
                error_log("SFTP read error at position $currentPosition");
                break;
            }

            // Throttle to prevent CPU overload
            usleep(100000); // 100ms
        }
    } catch (Exception $e) {
        error_log("Streaming error: " . $e->getMessage());
        if (isset($_GET['debug'])) {
            header("X-Stream-Error: " . $e->getMessage());
        }
    }

    if (isset($_GET['debug'])) {
        error_log("Streaming completed. Sent $currentPosition bytes of $fileSize");
    }
    exit;
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
    <link rel="stylesheet" href="css/view.css">
    <script src="../../js/bootstrap.bundle.js"></script>
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
            <div class="media-container position-relative">
                <?php if ($isImage): ?>
                    <img src="view.php?file=<?= urlencode($filePath) ?>&stream=1" alt="<?= htmlspecialchars($fileName) ?>" class="img-fluid">
                <?php elseif ($isVideo): ?>
                <video id="videoPlayer" controls autoplay playsinline>
                    <source src="view.php?file=<?= urlencode($filePath) ?>&stream=1" type="<?= $mimeType ?>">
                    Your browser does not support this video format. Try downloading the file instead.
                </video>
                <div class="alert alert-warning mt-2">
                    Note: For best results, use MP4 format (H.264 codec). Other formats may not play correctly in all browsers.
                </div>
                <?php elseif ($isAudio): ?>
                    <audio controls autoplay style="width: 80%;">
                        <source src="view.php?file=<?= urlencode($filePath) ?>&stream=1" type="<?= $mimeType ?>">
                        Your browser does not support the audio element.
                    </audio>
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
                        <td><?= formatBytes($fileSize) ?></td>
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

<script src="../../js/theme.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mediaElement = document.querySelector('video, audio');
    
    if (mediaElement) {
        // Restore volume from localStorage
        const savedVolume = localStorage.getItem('mediaVolume');
        if (savedVolume !== null) {
            mediaElement.volume = parseFloat(savedVolume);
        }

        // Save volume to localStorage when it changes
        mediaElement.addEventListener('volumechange', () => {
            localStorage.setItem('mediaVolume', mediaElement.volume);
        });
    }
});
</script>
</body>
</html>

<?php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>