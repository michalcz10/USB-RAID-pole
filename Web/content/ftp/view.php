<?php
session_start();

if(!isset($_SESSION['uname'])){
    header("location: ../../index.html");
    session_destroy();
    exit;
}

// Include error reporting for development
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

// Check if the file exists
if (!$sftp->stat($filePath)) {
    die("File not found: $filePath");
}

// Define supported file types
$imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
$videoTypes = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
$audioTypes = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac']; // Added audio types

// Check if file type is supported
$isImage = in_array($fileExtension, $imageTypes);
$isVideo = in_array($fileExtension, $videoTypes);
$isAudio = in_array($fileExtension, $audioTypes); // Added audio check

if (!$isImage && !$isVideo && !$isAudio) {
    die("Unsupported file type");
}

// Get file size from SFTP
$fileSize = $sftp->stat($filePath)['size'];

// MIME type mapping for all supported formats
$mimeMap = [
    // Video types
    'mp4' => 'video/mp4',
    'webm' => 'video/mp4',
    'ogg' => 'video/mp4',
    'mov' => 'video/mp4',
    'avi' => 'video/mp4',
    'mkv' => 'video/mp4',
    
    // Image types
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    
    // Audio types
    'mp3' => 'audio/mpeg',
    'wav' => 'audio/wav',
    'm4a' => 'audio/mp4',
    'flac' => 'audio/flac',
    'aac' => 'audio/aac'
];

$mimeType = isset($mimeMap[$fileExtension]) ? $mimeMap[$fileExtension] : 'application/octet-stream';

// If direct streaming is requested
if (isset($_GET['stream'])) {
    // Create a temporary file to cache a portion of the content
    // This is more reliable than direct streaming with some SFTP libraries
    $tempFile = tempnam(sys_get_temp_dir(), 'media_');
    
    // Get file range information
    $start = 0;
    $end = $fileSize - 1;
    $length = $fileSize;
    
    // Handle range requests for video seeking
    if (isset($_SERVER['HTTP_RANGE'])) {
        $rangeHeader = $_SERVER['HTTP_RANGE'];
        $matches = [];
        if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            $start = intval($matches[1]);
            
            if (!empty($matches[2])) {
                $end = intval($matches[2]);
            }
            
            $length = $end - $start + 1;
            
            // Return 206 Partial Content
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
        }
    }
    
    // Set appropriate headers
    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    header("Content-Length: $length");
    
    // Clear any output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    

    $chunkSize = 8 * 1024 * 1024; // 4MB chunks for better performance

    
    // For partial requests, adjust start position and length
    $currentPosition = $start;
    $bytesRemaining = $length;
    
    // Create a resource handle for the temporary file
    $tempHandle = fopen($tempFile, 'w+');
    
    // Process the file in chunks
    while ($bytesRemaining > 0) {
        // Determine the size of this chunk
        $readSize = min($chunkSize, $bytesRemaining);
        
        // Create a temporary file for this chunk
        $chunkTemp = tempnam(sys_get_temp_dir(), 'chunk_');
        
        // Command to get a specific portion of the file
        if ($sftp->get($filePath, $chunkTemp, $currentPosition, $readSize)) {
            // Read the chunk data
            $chunkData = file_get_contents($chunkTemp);
            
            // Write to output
            echo $chunkData;
            
            // Clean up this chunk file
            unlink($chunkTemp);
            
            // Update counters
            $bytesRemaining -= strlen($chunkData);
            $currentPosition += strlen($chunkData);
            
            // Flush output
            flush();
        } else {
            // Error reading from SFTP
            error_log("SFTP reading error at position $currentPosition");
            break;
        }
        
        // Check if client is still connected
        if (connection_status() != CONNECTION_NORMAL) {
            break;
        }
    }
    
    // Clean up
    fclose($tempHandle);
    if (file_exists($tempFile)) {
        unlink($tempFile);
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
?>