<?php
require '../../vendor/autoload.php';
use phpseclib3\Net\SFTP;

// Retrieve defPath from the session
$defPath = $_SESSION['defPath'] ?? '/';

// SFTP Configuration
$host = 'IP ADDRESS';
$username = 'USERNAME';
$password = 'PASSWD';
$defaultPath = $defPath;

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

// Example usage
$sftp = initializeSFTP($host, $username, $password);
$currentPath = normalizePath($defaultPath);