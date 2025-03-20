<?php
// Include this at the top to see potential errors
// Comment out in production
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if(!isset($_SESSION['uname'])){
    header("location: ../index.html");
    session_destroy();
    exit;
}

// Check if user has upload permissions
if(!isset($_SESSION["upPer"]) || $_SESSION["upPer"] != true) {
    http_response_code(403);
    echo "You don't have permission to create files.";
    exit;
}

require 'config.php';

$sftp = initializeSFTP($host, $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'createFile') {
    $currentPath = isset($_POST['currentPath']) ? normalizePath($_POST['currentPath']) : $defaultPath;
    
    if (!isset($_POST['fileName'])) {
        http_response_code(400);
        echo "File name is required";
        exit;
    }
    
    $fileName = $_POST['fileName'];
    
    // Validate file name has an extension
    if (strpos($fileName, '.') === false) {
        http_response_code(400);
        echo "File name must include an extension (e.g., .txt, .html, .php)";
        exit;
    }
    
    // Sanitize file name
    $fileName = preg_replace('/[^\w\-\.]/', '_', $fileName);
    
    // Ensure the path is within the allowed directory
    if (strpos($currentPath, $defaultPath) !== 0) {
        $currentPath = $defaultPath;
    }
    
    $newFilePath = $currentPath . '/' . $fileName;
    
    // Create a temp file with empty content
    $tempFile = tempnam(sys_get_temp_dir(), 'new_file_');
    file_put_contents($tempFile, '');
    
    // Upload the empty file
    if ($sftp->put($newFilePath, $tempFile)) {
        // Clean up the temp file
        @unlink($tempFile);
        
        // Redirect to editor
        echo json_encode(['success' => true, 'filePath' => $newFilePath]);
    } else {
        // Clean up the temp file
        @unlink($tempFile);
        
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create file.']);
    }
    exit;
}

http_response_code(400);
echo "Invalid request";
exit;