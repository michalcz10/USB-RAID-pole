<?php
require 'config.php';

$sftp = initializeSFTP($host, $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $itemToDelete = $_POST['delete'];
    $parentDir = dirname($itemToDelete);
    
    // Make sure to return to the main site if we're deleting at the default path level
    if ($parentDir === '/' || $parentDir === '.') {
        $parentDir = $defaultPath;
    }

    if ($sftp->is_dir($itemToDelete)) {
        // Recursively delete folder
        function deleteFolder($sftp, $folderPath) {
            $items = $sftp->nlist($folderPath);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $itemPath = $folderPath . '/' . $item;
                if ($sftp->is_dir($itemPath)) {
                    deleteFolder($sftp, $itemPath);
                } else {
                    $sftp->delete($itemPath);
                }
            }
            return $sftp->rmdir($folderPath);
        }

        $success = deleteFolder($sftp, $itemToDelete);
    } else {
        $success = $sftp->delete($itemToDelete);
    }
    
    // Redirect back to the index page at the parent directory
    header("Location: index.php?path=" . urlencode($parentDir));
    exit;
}

// If we get here, something went wrong - redirect to the main page
header("Location: index.php");
exit;