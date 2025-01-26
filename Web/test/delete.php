<?php
require 'config.php';

$sftp = initializeSFTP($host, $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $itemToDelete = $_POST['delete'];

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

        if (deleteFolder($sftp, $itemToDelete)) {
            echo "Folder deleted successfully!";
        } else {
            echo "Failed to delete folder.";
        }
    } else {
        if ($sftp->delete($itemToDelete)) {
            echo "File deleted successfully!";
        } else {
            echo "Failed to delete file.";
        }
    }
}
