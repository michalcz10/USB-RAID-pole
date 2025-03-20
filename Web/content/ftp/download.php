<?php
// Set execution time limit to prevent infinite loops
set_time_limit(300); // 5 minutes max execution time

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to capture any errors
ob_start();

try {
    require 'config.php';
    
    // Create a log file for debugging
    $logFile = __DIR__ . '/download_log.txt';
    file_put_contents($logFile, "Download started: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    
    // Initialize SFTP with error checking
    $sftp = null;
    try {
        $sftp = initializeSFTP($host, $username, $password);
        file_put_contents($logFile, "SFTP connection established\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFile, "SFTP connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
        throw new Exception("Failed to connect to SFTP server: " . $e->getMessage());
    }
    
    function zipFolder($sftp, $folderPath, $zipFilePath, $logFile) {
        file_put_contents($logFile, "Starting to zip folder: $folderPath\n", FILE_APPEND);
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            file_put_contents($logFile, "Failed to create zip archive\n", FILE_APPEND);
            throw new Exception("Unable to create the zip file.");
        }
        
        // Get list of files with error checking
        file_put_contents($logFile, "Listing files in: $folderPath\n", FILE_APPEND);
        $files = $sftp->nlist($folderPath);
        
        if ($files === false) {
            file_put_contents($logFile, "Failed to list directory contents\n", FILE_APPEND);
            throw new Exception("Failed to list directory contents");
        }
        
        file_put_contents($logFile, "Found " . count($files) . " items\n", FILE_APPEND);
        
        foreach ($files as $file) {
            // Skip directory entries
            if ($file == '.' || $file == '..') continue;
            
            // Create full path for the remote file
            $fullRemotePath = rtrim($folderPath, '/') . '/' . $file;
            file_put_contents($logFile, "Processing: $fullRemotePath\n", FILE_APPEND);
            
            // Check if it's a file or directory
            if ($sftp->is_dir($fullRemotePath)) {
                file_put_contents($logFile, "Skipping directory: $file\n", FILE_APPEND);
                continue;
            }
            
            // Create temporary file to store the downloaded content
            $localTempFile = tempnam(sys_get_temp_dir(), 'sftp');
            file_put_contents($logFile, "Downloading to temp file: $localTempFile\n", FILE_APPEND);
            
            // Download the file with timeout
            $downloadStart = time();
            $downloadSuccess = $sftp->get($fullRemotePath, $localTempFile);
            $downloadTime = time() - $downloadStart;
            
            if ($downloadSuccess) {
                file_put_contents($logFile, "Download successful ($downloadTime seconds), size: " . filesize($localTempFile) . " bytes\n", FILE_APPEND);
                
                // Add file to zip
                $zip->addFile($localTempFile, $file);
                
                // We'll clean up temp files manually at the end
            } else {
                @unlink($localTempFile);
                file_put_contents($logFile, "Failed to download file after $downloadTime seconds\n", FILE_APPEND);
            }
        }
        
        // Close the zip file
        file_put_contents($logFile, "Closing zip file\n", FILE_APPEND);
        $zipSuccess = $zip->close();
        
        // Clean up temp files
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;
            $fullRemotePath = rtrim($folderPath, '/') . '/' . $file;
            if (!$sftp->is_dir($fullRemotePath)) {
                $localTempFile = tempnam(sys_get_temp_dir(), 'sftp');
                if (file_exists($localTempFile)) {
                    @unlink($localTempFile);
                }
            }
        }
        
        if ($zipSuccess) {
            file_put_contents($logFile, "Zip created successfully, size: " . filesize($zipFilePath) . " bytes\n", FILE_APPEND);
            return true;
        } else {
            file_put_contents($logFile, "Failed to create zip\n", FILE_APPEND);
            return false;
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'])) {
        $file = $_POST['file'];
        file_put_contents($logFile, "Requested file: $file\n", FILE_APPEND);
        
        // Make sure the file path exists on SFTP server
        $fileExists = $sftp->file_exists($file);
        $isDir = $sftp->is_dir($file);
        
        file_put_contents($logFile, "File exists: " . ($fileExists ? "Yes" : "No") . "\n", FILE_APPEND);
        file_put_contents($logFile, "Is directory: " . ($isDir ? "Yes" : "No") . "\n", FILE_APPEND);
        
        if (!$fileExists && !$isDir) {
            throw new Exception("File not found: $file");
        }
        
        if ($isDir) {
            // Handle directory download
            $zipFilePath = tempnam(sys_get_temp_dir(), 'folder_') . '.zip';
            file_put_contents($logFile, "Creating zip at: $zipFilePath\n", FILE_APPEND);
            
            if (zipFolder($sftp, $file, $zipFilePath, $logFile)) {
                // Check if the zip file was created and has content
                if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
                    file_put_contents($logFile, "Sending zip file to browser\n", FILE_APPEND);
                    
                    // Clear any output that might have been sent
                    ob_clean();
                    
                    // Send appropriate headers
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . basename($file) . '.zip"');
                    header('Content-Length: ' . filesize($zipFilePath));
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    
                    // Output file contents and exit
                    readfile($zipFilePath);
                    file_put_contents($logFile, "Download completed\n", FILE_APPEND);
                    @unlink($zipFilePath);
                    exit;
                } else {
                    throw new Exception("Failed to create zip file or zip file is empty");
                }
            } else {
                throw new Exception("Failed to create zip archive");
            }
        } else {
            // Handle single file download
            $localFile = basename($file);
            file_put_contents($logFile, "Downloading single file: $localFile\n", FILE_APPEND);
            
            // Create a temporary file to verify content before sending
            $tempFile = tempnam(sys_get_temp_dir(), 'file_');
            
            $downloadStart = time();
            $downloadSuccess = $sftp->get($file, $tempFile);
            $downloadTime = time() - $downloadStart;
            
            file_put_contents($logFile, "Download " . ($downloadSuccess ? "successful" : "failed") . " ($downloadTime seconds)\n", FILE_APPEND);
            
            if ($downloadSuccess) {
                // Check if file has content
                $fileSize = filesize($tempFile);
                file_put_contents($logFile, "Downloaded file size: $fileSize bytes\n", FILE_APPEND);
                
                if ($fileSize > 0) {
                    // Clear any output that might have been sent
                    ob_clean();
                    
                    // Send appropriate headers
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $localFile . '"');
                    header('Content-Length: ' . $fileSize);
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    
                    // Output file and clean up
                    readfile($tempFile);
                    file_put_contents($logFile, "Download completed\n", FILE_APPEND);
                    @unlink($tempFile);
                    exit;
                } else {
                    @unlink($tempFile);
                    throw new Exception("Downloaded file is empty");
                }
            } else {
                @unlink($tempFile);
                throw new Exception("Failed to download file from SFTP server");
            }
        }
    } else {
        throw new Exception("Invalid request method or missing file parameter");
    }
} catch (Exception $e) {
    // Log the error
    $errorMessage = "Error: " . $e->getMessage();
    file_put_contents($logFile, $errorMessage . "\n", FILE_APPEND);
    
    // Clear any output that might have been sent
    ob_clean();
    
    // Send error response
    header("HTTP/1.1 500 Internal Server Error");
    echo $errorMessage;
}
?>