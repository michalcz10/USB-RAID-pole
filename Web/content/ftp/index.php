<?php
    session_start();

    if(!isset($_SESSION['uname'])){
        header("location: ../index.html");
        session_destroy();
        exit;
    }

    // Include this at the top to see potential errors
    // Comment out in production
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    require 'config.php';
    $sftp = initializeSFTP($host, $username, $password);

    $currentPath = isset($_GET['path']) ? normalizePath($_GET['path']) : $defaultPath;
    if (strpos($currentPath, $defaultPath) !== 0) {
        $currentPath = $defaultPath;
    }
    
    // Check SFTP connection before using it
    if (!$sftp->chdir($currentPath)) {
        die("Failed to navigate to the selected folder: $currentPath");
    }

    $items = $sftp->nlist();
	
	$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
	$videoExtensions = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];
	$editableExtensions = ['txt', 'html', 'css', 'js', 'php', 'xml', 'json', 'md', 'csv', 'log', 'ini', 'conf', 'sh', 'bat', 'py', 'rb', 'java', 'c', 'cpp', 'h', 'hpp'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>FTP</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../../css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/index.css">
    <script src="../../js/bootstrap.bundle.js"></script>
</head>
<body class="text-center">
<div class="d-flex justify-content-end p-3">
    <button id="themeToggle" class="btn btn-sm theme-toggle">
        <i class="bi"></i>
        <span id="themeText"></span>
    </button>
</div>
<div class="custom-container">
<header class="row border-bottom m-5">
    <h1>USB RAID Array</h1>
    <div class="mb-3 p-3">
        <a href="../logout.php" class="btn btn-danger">Logout</a>
        <a href="../changepassword.php" class="btn btn-warning">Change Password</a>
        <?php if (isset($_SESSION["admin"]) && $_SESSION["admin"] == true) { ?>
            <a href="../adminpanel.php" class="btn btn-primary">Admin Panel</a>
        <?php } ?>
    </div>
</header>

<section class="row">

    <article class="col-8 border border-2 border-primary rounded p-2">
        <div class="col">
            <h4>Current Path: <?= htmlspecialchars($currentPath) ?></h4>
        </div>

        <div class="col">
            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if (isset($_SESSION["upPer"]) && $_SESSION["upPer"] == true) { ?>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFileModal">Create File</button>
                    <button type="button" class="btn btn-success" onclick="document.getElementById('fileInput').click()">Upload Files</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDirModal">Create Directory</button>
                    <button type="button" class="btn btn-info" onclick="document.getElementById('dirInput').click()">Upload Directory</button>
                    <input type="file" id="fileInput" multiple style="display: none;" onchange="handleFileSelect(event)">
                    <input type="file" id="dirInput" webkitdirectory directory multiple style="display: none;" onchange="handleFileSelect(event)">
                <?php } ?>
            </div>

            <!-- Create Directory Dialog -->
            <div class="modal fade" id="createFileModal" tabindex="-1" aria-labelledby="createFileModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createFileModalLabel">Create New File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="fileName" class="form-label">File Name (with extension)</label>
                                <input type="text" class="form-control" id="fileName" placeholder="example.txt">
                                <div class="form-text">Supported extensions: .txt, .html, .css, .js, .php, etc.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="createFile()">Create</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create File Dialog -->
            <div class="modal fade" id="createFileModal" tabindex="-1" aria-labelledby="createFileModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createFileModalLabel">Create New File</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="fileName" class="form-label">File Name (with extension)</label>
                                <input type="text" class="form-control" id="fileName" placeholder="Enter file name (e.g., myfile.txt)">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="createFile()">Create</button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION["upPer"]) && $_SESSION["upPer"] == true) { ?>
                <div class="dropzone" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                    Drop files or folders here to upload
                </div>
            <?php } ?>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $directories = [];
                    $files = [];

                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;

                        if ($sftp->is_dir($item)) {
                            $directories[] = $item;
                        } else {
                            $files[] = $item;
                        }
                    }

                    // Sort directories and files alphabetically
                    sort($directories, SORT_STRING | SORT_FLAG_CASE);
                    sort($files, SORT_STRING | SORT_FLAG_CASE);

                    if ($currentPath !== $defaultPath) : ?>
                        <tr>
                            <td colspan="2"><a class="text-danger" href="?path=<?= urlencode(dirname($currentPath)) ?>"><b>.. (Go Back)</b></a></td>
                        </tr>
                    <?php endif;

                    foreach ($directories as $directory) : ?>
                        <tr>
                            <td>
                                <a href="?path=<?= urlencode($currentPath . '/' . $directory) ?>"><?= htmlspecialchars($directory) ?>/</a>
                            </td>
                            <td>
                                <?php if (isset($_SESSION["delPer"]) && $_SESSION["delPer"] == true) : ?>
                                    <form method="POST" action="delete.php" style="display:inline;">
                                        <input type="hidden" name="delete" value="<?= htmlspecialchars($currentPath . '/' . $directory) ?>">
                                        <button type="submit" class="btn btn-danger" onclick="confirmDelete(event)">Delete</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (isset($_SESSION["downPer"]) && $_SESSION["downPer"] == true) : ?>
                                    <form method="POST" action="download.php" style="display:inline;">
                                        <input type="hidden" name="file" value="<?= htmlspecialchars($currentPath . '/' . $directory) ?>">
                                        <button type="submit" class="btn btn-success">Download</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;

                    foreach ($files as $file) : ?>
                        <tr>
                            <?php
                            //getting file extension
                            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                            $isImage = in_array($fileExtension, $imageExtensions);
                            $isVideo = in_array($fileExtension, $videoExtensions);
                            $isMedia = $isImage || $isVideo;
                            $isEditable = in_array($fileExtension, $editableExtensions);
                            ?>
                            <tr>
                                <td>
                                    <?php if ($isMedia): ?>
                                        <a class="text-info-emphasis" href="view.php?file=<?= urlencode($currentPath . '/' . $file) ?>">
                                            <?= htmlspecialchars($file) ?>
                                        </a>
                                        <?php if ($isImage): ?>
                                            <span class="badge bg-success rounded-pill">Image</span>
                                        <?php elseif ($isVideo): ?>
                                            <span class="badge bg-primary rounded-pill">Video</span>
                                        <?php endif; ?>
                                    <?php elseif ($isEditable): ?>
                                        <a class="text-warning-emphasis" href="open.php?file=<?= urlencode($currentPath . '/' . $file) ?>">
                                            <?= htmlspecialchars($file) ?>
                                        </a>
                                        <span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($fileExtension) ?></span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($file) ?>
                                        <span class="badge bg-light text-dark rounded-pill"><?= htmlspecialchars($fileExtension) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($_SESSION["delPer"]) && $_SESSION["delPer"] == true) : ?>
                                        <form method="POST" action="delete.php" style="display:inline;">
                                            <input type="hidden" name="delete" value="<?= htmlspecialchars($currentPath . '/' . $file) ?>">
                                            <button type="submit" class="btn btn-danger" onclick="confirmDelete(event)">Delete</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (isset($_SESSION["downPer"]) && $_SESSION["downPer"] == true) : ?>
                                        <form method="POST" action="download.php" style="display:inline;">
                                            <input type="hidden" name="file" value="<?= htmlspecialchars($currentPath . '/' . $file) ?>">
                                            <button type="submit" class="btn btn-success">Download</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </article>

        <footer class="d-flex flex-column justify-content-center align-items-center p-3 border-top gap-3 m-3">
        <span class="text-muted">Developed by Michal Sedlák</span>
        <div class="d-flex gap-3">
            <a href="https://github.com/michalcz10/USB-RAID-pole" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="../../img/GitHub_Logo.png" alt="GitHub Logo" class="img-fluid hover-effect light-logo" style="height: 32px;">
                <img src="../../img/GitHub_Logo_White.png" alt="GitHub Logo" class="img-fluid hover-effect dark-logo" style="height: 32px;">
            </a>
            <a href="https://app.freelo.io/public/shared-link-view/?a=81efbcb4df761b3f29cdc80855b41e6d&b=4519c717f0729cc8e953af661e9dc981" class="text-decoration-none" target="_blank" rel="noopener noreferrer">
                <img src="../../img/freelo-logo-rgb.png" alt="Freelo Logo" class="img-fluid hover-effect light-logo" style="height: 24px;">
                <img src="../../img/freelo-logo-rgb-on-dark.png" alt="Freelo Logo" class="img-fluid hover-effect dark-logo" style="height: 24px;">
            </a>
        </div>
        </footer>
    </div>
    <script>
        function handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('dragover');
    }

    function handleDragLeave(event) {
        event.currentTarget.classList.remove('dragover');
    }

    function handleDrop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');

        if (event.dataTransfer.items) {
            const items = event.dataTransfer.items;
            processDroppedItems(items);
        } else {
            const files = event.dataTransfer.files;
            uploadFiles(files);
        }
    }

    function processDroppedItems(items) {
        const allFiles = [];
        let pendingItems = 0;
        
        function handleEntry(entry, path = '') {
            if (entry.isFile) {
                pendingItems++;
                entry.file(file => {
                    Object.defineProperty(file, 'webkitRelativePath', {
                        value: path + file.name
                    });
                    
                    allFiles.push(file);
                    pendingItems--;

                    if (pendingItems === 0) {
                        uploadFiles(allFiles);
                    }
                });
            } else if (entry.isDirectory) {
                const reader = entry.createReader();
                readEntries(reader, path + entry.name + '/');
            }
        }
        
        // Function to read entries from a directory reader
        function readEntries(reader, path) {
            pendingItems++;
            reader.readEntries(entries => {
                if (entries.length > 0) {
                    for (const entry of entries) {
                        handleEntry(entry, path);
                    }
                    readEntries(reader, path);
                }
                pendingItems--;
                
                if (pendingItems === 0 && allFiles.length > 0) {
                    uploadFiles(allFiles);
                }
            });
        }
        
        // Process each dropped item
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            if (item.kind !== 'file') continue;
            
            const entry = item.webkitGetAsEntry ? item.webkitGetAsEntry() : item.getAsEntry();
            if (entry) {
                handleEntry(entry);
            }
        }
        
        // If there are no items to process, show an error
        if (pendingItems === 0 && allFiles.length === 0) {
            alert('No valid files or directories found.');
        }
    }

    function handleFileSelect(event) {
        const files = event.target.files;
        uploadFiles(files);
    }

    function uploadFiles(files) {
        // Validate files
        if (!files || files.length === 0) {
            alert('No files selected for upload.');
            return;
        }

        const formData = new FormData();
        formData.append('currentPath', '<?php echo $currentPath; ?>');

        let filesAdded = 0;
        let directories = new Set();
        
        // Track directories to create
        for (const file of files) {
            const relativePath = file.webkitRelativePath || '';
            
            // If there's a relative path, extract directories
            if (relativePath) {
                const parts = relativePath.split('/');
                let currentPath = '';
                for (let i = 0; i < parts.length - 1; i++) {
                    currentPath += (i > 0 ? '/' : '') + parts[i];
                    if (currentPath) {
                        directories.add(currentPath);
                    }
                }
            }
            
            formData.append('files[]', file);
            formData.append('paths[]', relativePath);
            filesAdded++;
        }

        // Add directories to create
        if (directories.size > 0) {
            formData.append('create_dirs', JSON.stringify(Array.from(directories)));
        }

        if (filesAdded === 0) {
            alert('No valid files selected for upload.');
            return;
        }

        // Show upload progress
        const uploadStatus = document.createElement('div');
        uploadStatus.className = 'alert alert-info';
        uploadStatus.textContent = 'Uploading files, please wait...';
        document.querySelector('.action-buttons').after(uploadStatus);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php', true);
        
        // Handle errors
        xhr.onerror = () => {
            uploadStatus.className = 'alert alert-danger';
            uploadStatus.textContent = 'Network error occurred during upload.';
            setTimeout(() => uploadStatus.remove(), 5000);
        };
        
        // Handle timeouts
        xhr.timeout = 300000; // 5 minutes
        xhr.ontimeout = () => {
            uploadStatus.className = 'alert alert-danger';
            uploadStatus.textContent = 'Upload timed out. Try with smaller files or fewer files.';
            setTimeout(() => uploadStatus.remove(), 5000);
        };

        xhr.onload = () => {
            if (xhr.status === 200) {
                uploadStatus.className = 'alert alert-success';
                uploadStatus.textContent = 'Upload successful!';
                setTimeout(() => {
                    uploadStatus.remove();
                    window.location.reload(); // Reload to show new files
                }, 1500);
            } else {
                uploadStatus.className = 'alert alert-danger';
                uploadStatus.textContent = 'Upload failed: ' + (xhr.responseText || xhr.statusText);
                setTimeout(() => uploadStatus.remove(), 5000);
            }
        };

        xhr.send(formData);
    }

    function createDirectory() {
        const dirName = document.getElementById('dirName').value.trim();
        if (!dirName) {
            alert('Please enter a directory name.');
            return;
        }

        const formData = new FormData();
        formData.append('currentPath', '<?php echo $currentPath; ?>');
        formData.append('dirName', dirName);
        formData.append('action', 'createDir');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'createdir.php', true);
        xhr.onload = () => {
            if (xhr.status === 200) {
                alert('Directory created successfully!');
                const modal = bootstrap.Modal.getInstance(document.getElementById('createDirModal'));
                if (modal) modal.hide();
                window.location.reload();
            } else {
                alert('Failed to create directory: ' + xhr.responseText || xhr.statusText);
            }
        };
        xhr.send(formData);
    }
    function confirmDelete(event) {
        event.preventDefault();
        
        if (confirm("Do you really want to delete this file?")) {
            event.target.form.submit();
        }
    }
    function createFile() {
        const fileName = document.getElementById('fileName').value.trim();
        if (!fileName) {
            alert('Please enter a file name.');
            return;
        }
        
        // Check if file has an extension
        if (fileName.indexOf('.') === -1) {
            alert('Please include a file extension (e.g., .txt, .html, .php)');
            return;
        }

        const formData = new FormData();
        formData.append('currentPath', '<?php echo $currentPath; ?>');
        formData.append('fileName', fileName);
        formData.append('action', 'createFile');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'createfile.php', true);
        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('File created successfully!');
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createFileModal'));
                        if (modal) modal.hide();
                        window.location.reload();
                    } else {
                        alert('Failed to create file: ' + (response.message || 'Unknown error'));
                    }
                } catch (e) {
                    alert('Error processing response: ' + xhr.responseText);
                }
            } else {
                alert('Failed to create file: ' + xhr.responseText || xhr.statusText);
            }
        };
        xhr.send(formData);
    }
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
</body>
</html>