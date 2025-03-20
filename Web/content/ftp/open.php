<?php
// Set error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if(!isset($_SESSION['uname'])){
    header("location: ../index.html");
    session_destroy();
    exit;
}

require 'config.php';
$sftp = initializeSFTP($host, $username, $password);

// Get the file path from GET parameter
$filePath = isset($_GET['file']) ? $_GET['file'] : '';

// Initialize variables
$content = '';
$fileName = basename($filePath);
$extension = pathinfo($fileName, PATHINFO_EXTENSION);
$editable = false;

// List of editable file extensions
$editableExtensions = ['txt', 'html', 'css', 'js', 'php', 'xml', 'json', 'md', 'csv', 'log', 'ini', 'conf', 'sh', 'bat', 'py', 'rb', 'java', 'c', 'cpp', 'h', 'hpp'];

if (!empty($filePath) && $sftp->file_exists($filePath) && !$sftp->is_dir($filePath)) {
    if (in_array(strtolower($extension), $editableExtensions)) {
        $editable = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && isset($_SESSION["upPer"]) && $_SESSION["upPer"] == true) {
            $newContent = $_POST['content'];

            // Write content directly to the SFTP server
            if ($sftp->put($filePath, $newContent)) {
                $saveSuccess = true;
            } else {
                $saveError = "Failed to save changes. Check permissions.";
            }
        }
        
        // Get file contents directly from the SFTP server
        $content = $sftp->get($filePath);
        if ($content === false) {
            $error = "Failed to read file contents.";
        }
    } else {
        $error = "This file type is not supported for editing.";
    }
} else {
    $error = "File not found or is a directory.";
}

// Determine if we should show line numbers for code files
$showLineNumbers = in_array(strtolower($extension), ['php', 'js', 'html', 'css', 'py', 'java', 'c', 'cpp', 'h', 'hpp', 'rb', 'sh', 'xml', 'json']);

$parentDir = dirname($filePath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit File - <?= htmlspecialchars($fileName) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../../css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="../../js/bootstrap.bundle.js"></script>
    <style>
        .editor-container {
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 20px;
            position: relative;
        }
        
        #editor {
            width: 100%;
            min-height: 400px;
            font-family: monospace;
            padding: 10px;
            white-space: pre;
            overflow: auto;
            resize: vertical;
            tab-size: 4;
            -moz-tab-size: 4;
            padding-left: 55px; /* Make room for line numbers */
        }
        
        .line-numbers {
            position: absolute;
            left: 0;
            top: 0;
            width: 45px;
            text-align: right;
            padding: 10px 5px 10px 0;
            border-right: 1px solid #ccc;
            background-color: transparent;
            color: #999;
            user-select: none;
            font-family: monospace;
            overflow: hidden;
            z-index: 1;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .readonly-notice {
            color: #dc3545;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
<div class="d-flex justify-content-end p-3">
    <button id="themeToggle" class="btn btn-sm theme-toggle">
        <i class="bi"></i>
        <span id="themeText"></span>
    </button>
</div>
    <div class="container mt-4">
        <div class="header-container">
            <h1>Edit File: <?= htmlspecialchars($fileName) ?></h1>
            <a href="index.php?path=<?= urlencode($parentDir) ?>" class="btn btn-secondary">Back to File List</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($editable): ?>
            <?php if (isset($saveSuccess)): ?>
                <div class="alert alert-success">File saved successfully!</div>
            <?php endif; ?>
            
            <?php if (isset($saveError)): ?>
                <div class="alert alert-danger"><?= $saveError ?></div>
            <?php endif; ?>
            
            <form method="POST" id="editorForm">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="editor" class="form-label">File Content</label>
                        <?php if (!isset($_SESSION["upPer"]) || $_SESSION["upPer"] != true): ?>
                            <span class="readonly-notice">Read-only mode (you don't have upload permissions)</span>
                        <?php endif; ?>
                    </div>
                    <div class="editor-container">
                        <?php if ($showLineNumbers): ?>
                            <div id="lineNumbers" class="line-numbers"></div>
                        <?php endif; ?>
                        <textarea id="editor" name="content" class="form-control" <?= (!isset($_SESSION["upPer"]) || $_SESSION["upPer"] != true) ? 'readonly' : '' ?>><?= htmlspecialchars($content) ?></textarea>
                    </div>
                </div>
                
                <?php if (isset($_SESSION["upPer"]) && $_SESSION["upPer"] == true): ?>
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;
            const themeText = document.getElementById('themeText');
            const themeIcon = themeToggle.querySelector('.bi');
            
            function setTheme(theme) {
                html.setAttribute('data-bs-theme', theme);
                document.body.classList.remove('theme-light', 'theme-dark');
                document.body.classList.add('theme-' + theme);
                localStorage.setItem('theme', theme);
                
                if (theme === 'dark') {
                    themeText.textContent = 'Light Mode';
                    themeIcon.className = 'bi bi-sun';
                    themeToggle.classList.remove('btn-dark');
                    themeToggle.classList.add('btn-light');
                } else {
                    themeText.textContent = 'Dark Mode';
                    themeIcon.className = 'bi bi-moon';
                    themeToggle.classList.remove('btn-light');
                    themeToggle.classList.add('btn-dark');
                }
            }
            
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                setTheme(prefersDark ? 'dark' : 'light');
            }
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });
        });
    </script>

    <script>
        // Function to update line numbers
        function updateLineNumbers() {
            const editor = document.getElementById('editor');
            const lineNumbers = document.getElementById('lineNumbers');
            
            if (!lineNumbers) return; // Skip if line numbers are not shown
            
            const lines = editor.value.split('\n');
            const lineCount = lines.length;
            
            let html = '';
            for (let i = 1; i <= lineCount; i++) {
                html += i + '<br>';
            }
            
            lineNumbers.innerHTML = html;
            
            // Update height and scroll position immediately
            syncLineNumbersHeight();
            lineNumbers.scrollTop = editor.scrollTop;
        }
        
        // Function to sync line numbers height with editor height
        function syncLineNumbersHeight() {
            const editor = document.getElementById('editor');
            const lineNumbers = document.getElementById('lineNumbers');
            
            if (!lineNumbers || !editor) return;
            
            // Set line numbers height to match the editor's client height (visible area)
            lineNumbers.style.height = editor.clientHeight + 'px';
        }
        
        // Initialize line numbers and set up event handlers
        document.addEventListener('DOMContentLoaded', function() {
            const editor = document.getElementById('editor');
            const lineNumbers = document.getElementById('lineNumbers');
            
            if (editor && lineNumbers) {
                // Force immediate rendering of line numbers
                setTimeout(() => {
                    updateLineNumbers();
                    syncLineNumbersHeight();
                }, 0);
                
                // Update line numbers on input
                editor.addEventListener('input', updateLineNumbers);
                
                // Handle tab key for indentation
                editor.addEventListener('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        
                        const start = this.selectionStart;
                        const end = this.selectionEnd;
                        
                        // Insert tab at cursor position
                        this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                        
                        // Move cursor after tab
                        this.selectionStart = this.selectionEnd = start + 4;
                        
                        // Update line numbers
                        updateLineNumbers();
                    }
                });
                
                // Handle scroll sync between editor and line numbers
                editor.addEventListener('scroll', function() {
                    if (lineNumbers) {
                        lineNumbers.scrollTop = this.scrollTop;
                    }
                });
                
                // Ensure height sync happens on multiple events
                editor.addEventListener('mouseup', syncLineNumbersHeight);
                
                // Use mutation observer to detect style changes (like height)
                const observer = new MutationObserver(function(mutations) {
                    syncLineNumbersHeight();
                });
                
                observer.observe(editor, { 
                    attributes: true, 
                    attributeFilter: ['style'] 
                });
                
                // For browsers that support ResizeObserver
                if (typeof ResizeObserver === 'function') {
                    const resizeObserver = new ResizeObserver(() => {
                        syncLineNumbersHeight();
                    });
                    resizeObserver.observe(editor);
                } else {
                    // Fallback for browsers without ResizeObserver
                    window.addEventListener('resize', syncLineNumbersHeight);
                }
            }
        });
    </script>
</body>
</html>