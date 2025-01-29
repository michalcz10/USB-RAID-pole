<?php
require '../../vendor/autoload.php';
use phpseclib3\Net\SFTP;

// SFTP Configuration
$host = '127.0.0.1';
$username = 'laptop';
$password = '1235';
$defaultPath = '/home/laptop/WWWData';

// Initialize SFTP connection
function initializeSFTP($host, $username, $password) {
    $sftp = new SFTP($host);
    if (!$sftp->login($username, $password)) {
        die('Login Failed');
    }
    return $sftp;
}

// Normalize path to handle navigation
function normalizePath($path) {
    $parts = array_filter(explode('/', $path), fn($part) => $part !== '' && $part !== '.');
    $stack = [];
    foreach ($parts as $part) {
        if ($part === '..') {
            array_pop($stack);
        } else {
            $stack[] = $part;
        }
    }
    return '/' . implode('/', $stack);
}
