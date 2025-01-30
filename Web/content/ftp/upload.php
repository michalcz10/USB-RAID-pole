<?php
require 'config.php';
use phpseclib3\Net\SFTP;

$sftp = initializeSFTP($host, $username, $password);

$currentPath = isset($_GET['path']) ? normalizePath($_GET['path']) : $defaultPath;
if (strpos($currentPath, $defaultPath) !== 0) {
    $currentPath = $defaultPath;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['files'])) {
    foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
        $fileName = $_FILES['files']['name'][$index];
        $remotePath = $currentPath . '/' . $fileName;

        // Check if the uploaded item is a folder (if available via webkitRelativePath)
        if (isset($_FILES['files']['webkitRelativePath'][$index]) && is_dir($tmpName)) {
            // Handle folder upload
            $folderRemotePath = $currentPath . '/' . $fileName;
            uploadFolder($sftp, $tmpName, $folderRemotePath);
        } else {
            // Upload single file
            if (!$sftp->put($remotePath, $tmpName, SFTP::SOURCE_LOCAL_FILE)) {
                echo "Failed to upload file: $fileName";
            }
        }
    }
    echo "Upload successful!";
}

function uploadFolder($sftp, $localPath, $remotePath) {
    // Create remote directory
    if (!$sftp->mkdir($remotePath)) {
        echo "Failed to create directory: $remotePath";
        return false;
    }

    // Scan and upload files from the folder
    $files = array_diff(scandir($localPath), ['.', '..']);
    foreach ($files as $file) {
        $localFilePath = $localPath . '/' . $file;
        $remoteFilePath = $remotePath . '/' . $file;
        if (is_dir($localFilePath)) {
            // Recursive folder upload
            uploadFolder($sftp, $localFilePath, $remoteFilePath);
        } else {
            // Upload individual file
            if (!$sftp->put($remoteFilePath, $localFilePath, SFTP::SOURCE_LOCAL_FILE)) {
                echo "Failed to upload file: $localFilePath";
            }
        }
    }
    return true;
}
?>
