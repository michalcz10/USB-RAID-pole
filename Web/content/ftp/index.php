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
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>FTP</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link rel="stylesheet" href="../../css/bootstrap.css">
        <script src="../../js/bootstrap.bundle.js"></script>
        <style>
            .topRow{
                height: 20vh;
            }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            .dropzone { width: 100%; padding: 20px; border: 2px dashed #007bff; border-radius: 10px; text-align: center; margin-bottom: 20px; cursor: pointer; }
            .dropzone.dragover { background-color: #e0f7fa; }
            .action-buttons { margin-bottom: 15px; }
            .action-buttons button { margin-right: 10px; }
            body {
                min-width: 500px;
            }
        </style>
    </head>
    <body class="text-center">
    <div class="custom-container">
        <header class="row topRow border-dark border-bottom m-5">
            <h1>USB Raid pole</h1>
            
            <div class="mb-3">
                <a href="../logout.php" class="btn btn-danger">Logout</a>
                <br>
                <br>
                <?php if (isset($_SESSION["admin"]) && $_SESSION["admin"] == true) { ?>
                    <a href="../adminpanel.php" class="btn btn-primary">Admin Panel</a>
                <?php } ?>
            </div>

        </header>

        <section class="row">
            <aside class="col">
            </aside>

            <article class="col-8 border border-2 border-primary rounded p-2">
                <div class="col">
                    <h4>Current Path: <?= htmlspecialchars($currentPath) ?></h4>
                </div>

                <div class="col">
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDirModal">Create Directory</button>
                        <button type="button" class="btn btn-success" onclick="document.getElementById('fileInput').click()">Upload Files</button>
                        <button type="button" class="btn btn-info" onclick="document.getElementById('dirInput').click()">Upload Directory</button>
                        <input type="file" id="fileInput" multiple style="display: none;" onchange="handleFileSelect(event)">
                        <input type="file" id="dirInput" webkitdirectory directory multiple style="display: none;" onchange="handleFileSelect(event)">
                    </div>

                    <!-- Create Directory Dialog -->
                    <div class="modal fade" id="createDirModal" tabindex="-1" aria-labelledby="createDirModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="createDirModalLabel">Create New Directory</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="dirName" class="form-label">Directory Name</label>
                                        <input type="text" class="form-control" id="dirName" placeholder="Enter directory name">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="createDirectory()">Create</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dropzone" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                        Drop files or folders here to upload
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($currentPath !== $defaultPath): ?>
                            <tr>
                                <td colspan="2"><a href="?path=<?= urlencode(dirname($currentPath)) ?>">.. (Go Back)</a></td>
                            </tr>
                            <?php endif; ?>

                            <?php foreach ($items as $item): ?>
                                <?php if ($item === '.' || $item === '..') continue; ?>
                                <tr>
                                    <td>
                                        <?php if ($sftp->is_dir($item)): ?>
                                            <a href="?path=<?= urlencode($currentPath . '/' . $item) ?>"><?= htmlspecialchars($item) ?>/</a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($item) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(isset($_SESSION["delPer"]) && $_SESSION["delPer"] == true) { ?>
                                        <form method="POST" action="delete.php" style="display:inline;">
                                            <input type="hidden" name="delete" value="<?= htmlspecialchars($currentPath . '/' . $item) ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                        <?php } ?>

                                        <?php if(isset($_SESSION["downPer"]) && $_SESSION["downPer"] == true) { ?>
                                        <?php if (!$sftp->is_dir($item)): ?>
                                        <form method="POST" action="download.php" style="display:inline;">
                                            <input type="hidden" name="file" value="<?= htmlspecialchars($currentPath . '/' . $item) ?>">
                                            <button type="submit" class="btn btn-success">Download</button>
                                        </form>
                                        <?php endif; ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="col">
                    
                </div>
            </article>

            <aside class="col">
            </aside>
        </section>
        <footer class="row m-5">
        </footer>

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

                // Check if items are available (for directory support)
                if (event.dataTransfer.items) {
                    // Use DataTransferItemList interface to access the files
                    const items = event.dataTransfer.items;
                    processDroppedItems(items);
                } else {
                    // Use DataTransfer interface to access the files
                    const files = event.dataTransfer.files;
                    uploadFiles(files);
                }
            }

            function processDroppedItems(items) {
                // Create array to hold all files
                const allFiles = [];
                let pendingItems = 0;
                
                // Function to handle file entries
                function handleEntry(entry, path = '') {
                    if (entry.isFile) {
                        pendingItems++;
                        // Get file from entry
                        entry.file(file => {
                            // Store the path information for this file
                            Object.defineProperty(file, 'webkitRelativePath', {
                                value: path + file.name
                            });
                            
                            allFiles.push(file);
                            pendingItems--;
                            
                            // If no more pending items, upload all files
                            if (pendingItems === 0) {
                                uploadFiles(allFiles);
                            }
                        });
                    } else if (entry.isDirectory) {
                        // Read all entries in this directory
                        const reader = entry.createReader();
                        readEntries(reader, path + entry.name + '/');
                    }
                }
                
                // Function to read entries from a directory reader
                function readEntries(reader, path) {
                    pendingItems++;
                    reader.readEntries(entries => {
                        if (entries.length > 0) {
                            // Process each entry
                            for (const entry of entries) {
                                handleEntry(entry, path);
                            }
                            // Continue reading (readEntries only returns some entries at a time)
                            readEntries(reader, path);
                        }
                        pendingItems--;
                        
                        // If no more pending items, upload all files
                        if (pendingItems === 0 && allFiles.length > 0) {
                            uploadFiles(allFiles);
                        }
                    });
                }
                
                // Process each dropped item
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    // Skip if not a file
                    if (item.kind !== 'file') continue;
                    
                    // Get entry object (works for files and directories)
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
                    // Extract the relative path if it exists
                    const relativePath = file.webkitRelativePath || '';
                    
                    // If there's a relative path, extract directories
                    if (relativePath) {
                        const parts = relativePath.split('/');
                        // Add each directory level to the Set
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
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createDirModal'));
                        if (modal) modal.hide();
                        // Reload the page to show the new directory
                        window.location.reload();
                    } else {
                        alert('Failed to create directory: ' + xhr.responseText || xhr.statusText);
                    }
                };
                xhr.send(formData);
            }
        </script>
 </div>
    </body>
</html>