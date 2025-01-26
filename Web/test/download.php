<?php
require 'config.php';

$sftp = initializeSFTP($host, $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'])) {
    $file = $_POST['file'];
    $localFile = basename($file);

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $localFile . '"');

    $sftp->get($file, 'php://output');
    exit;
}
