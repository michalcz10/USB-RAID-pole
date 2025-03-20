<?php
// Set execution time limit to prevent infinite loops
set_time_limit(900); // 15 minutes max execution time

// Only enable error reporting during development
ini_set('display_errors', 0);
error_reporting(0);

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
    
    function zipFolderRecursive($sftp, $remoteBasePath, $currentPath, $zip, $logFile, &$tempFiles) {
        file_put_contents($logFile, "Processing directory: $currentPath\n", FILE_APPEND);
        
        // Get list of files with error checking
        $files = $sftp->nlist($currentPath);
        
        if ($files === false) {
            file_put_contents($logFile, "Failed to list directory contents for: $currentPath\n", FILE_APPEND);
            throw new Exception("Failed to list directory contents for: $currentPath");
        }
        
        file_put_contents($logFile, "Found " . count($files) . " items in $currentPath\n", FILE_APPEND);
        
        foreach ($files as $file) {
            // Skip directory entries
            if ($file == '.' || $file == '..') continue;
            
            // Create full path for the remote file
            $fullRemotePath = rtrim($currentPath, '/') . '/' . $file;
            
            // Calculate the relative path for the zip file
            // This is the key change - we're now including the base directory name in the ZIP structure
            $baseDirName = basename($remoteBasePath);
            $relPathFromBase = substr($fullRemotePath, strlen(dirname($remoteBasePath)) + 1);
            file_put_contents($logFile, "Processing: $fullRemotePath (relative: $relPathFromBase)\n", FILE_APPEND);
            
            // Check if it's a file or directory
            $isDir = $sftp->is_dir($fullRemotePath);
            
            if ($isDir) {
                file_put_contents($logFile, "Found subdirectory: $fullRemotePath\n", FILE_APPEND);
                // Create directory in the zip
                $zip->addEmptyDir($relPathFromBase);
                
                // Recursively process subdirectory
                zipFolderRecursive($sftp, $remoteBasePath, $fullRemotePath, $zip, $logFile, $tempFiles);
            } else {
                // Create temporary file to store the downloaded content
                $localTempFile = tempnam(sys_get_temp_dir(), 'sftp');
                $tempFiles[] = $localTempFile;
                file_put_contents($logFile, "Downloading to temp file: $localTempFile\n", FILE_APPEND);
                
                // Download the file with timeout
                $downloadStart = time();
                $downloadSuccess = $sftp->get($fullRemotePath, $localTempFile);
                $downloadTime = time() - $downloadStart;
                
                if ($downloadSuccess) {
                    $fileSize = filesize($localTempFile);
                    file_put_contents($logFile, "Download successful ($downloadTime seconds), size: $fileSize bytes\n", FILE_APPEND);
                    
                    // Add file to zip with its relative path
                    $zip->addFile($localTempFile, $relPathFromBase);
                } else {
                    file_put_contents($logFile, "Failed to download file after $downloadTime seconds\n", FILE_APPEND);
                }
            }
        }
    }
    
    function zipFolder($sftp, $folderPath, $zipFilePath, $logFile) {
        file_put_contents($logFile, "Starting to zip folder recursively: $folderPath\n", FILE_APPEND);
        
        $zip = new ZipArchive();
        
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            file_put_contents($logFile, "Failed to create zip archive\n", FILE_APPEND);
            throw new Exception("Unable to create the zip file.");
        }
        
        // We no longer add an empty root directory here - that was causing the duplication
        
        $tempFiles = [];
        
        // Process the directory recursively
        try {
            zipFolderRecursive($sftp, $folderPath, $folderPath, $zip, $logFile, $tempFiles);
            
            // Close the zip file
            file_put_contents($logFile, "Closing zip file\n", FILE_APPEND);
            $zipSuccess = $zip->close();
            
            // Clean up temp files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            if ($zipSuccess) {
                file_put_contents($logFile, "Zip created successfully, size: " . filesize($zipFilePath) . " bytes\n", FILE_APPEND);
                return true;
            } else {
                file_put_contents($logFile, "Failed to create zip\n", FILE_APPEND);
                return false;
            }
        } catch (Exception $e) {
            // Clean up temp files on error
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            file_put_contents($logFile, "Error during zip creation: " . $e->getMessage() . "\n", FILE_APPEND);
            throw $e;
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
                    file_put_contents($logFile, "Sending zip file to browser, size: " . filesize($zipFilePath) . " bytes\n", FILE_APPEND);
                    
                    // End all output buffering
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // Send appropriate headers
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . basename($file) . '.zip"');
                    header('Content-Length: ' . filesize($zipFilePath));
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    
                    // Output file contents without any echo or print statements
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
                    // End all output buffering
                    while (ob_get_level()) {
                        ob_end_clean();
                    }
                    
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
    
    // End all output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send error response
    header("HTTP/1.1 500 Internal Server Error");
    echo $errorMessage;
}
?>