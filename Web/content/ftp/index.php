<?php
    session_start();

    if(!isset($_SESSION['uname'])){
        header("location: ../index.html");
        session_destroy();
    }
?>

<?php
require 'config.php';
$sftp = initializeSFTP($host, $username, $password);

$currentPath = isset($_GET['path']) ? normalizePath($_GET['path']) : $defaultPath;
if (strpos($currentPath, $defaultPath) !== 0) {
    $currentPath = $defaultPath;
}
if (!$sftp->chdir($currentPath)) {
    die("Failed to navigate to the selected folder: $currentPath");
}

$items = $sftp->nlist();

?>


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
        </style>
        <style>
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .dropzone { width: 100%; padding: 20px; border: 2px dashed #007bff; border-radius: 10px; text-align: center; margin-bottom: 20px; cursor: pointer; }
        .dropzone.dragover { background-color: #e0f7fa; }
    </style>
    </head>
    <body class="container-fluid text-center">
        <header class="row topRow border-dark border-bottom m-5">
            <h1>USB Raid pole</h1>
            <form action="../logout.php" method="get">
                <div class="mb-3">
                <input type="submit" class="btn btn-danger" value="Logout" name="logoutBtn">
                </div>
            </form>
        </header>

        <section class="row">
            <aside class="col">
            </aside>

            <article class="col-8 border border-2 border-primary rounded p-2">
                <div class="col">

                </div>

                <div class="col">
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
                    <form method="POST" action="delete.php" style="display:inline;">
                        <input type="hidden" name="delete" value="<?= htmlspecialchars($currentPath . '/' . $item) ?>">
                        <button type="submit">Delete</button>
                    </form>
                    <?php if (!$sftp->is_dir($item)): ?>
                    <form method="POST" action="download.php" style="display:inline;">
                        <input type="hidden" name="file" value="<?= htmlspecialchars($currentPath . '/' . $item) ?>">
                        <button type="submit">Download</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

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

    const files = event.dataTransfer.files;
    const formData = new FormData();

    for (const file of files) {
        formData.append('files[]', file, file.webkitRelativePath || file.name);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);
    xhr.onload = () => {
        if (xhr.status === 200) alert('Upload successful!');
        else alert('Upload failed: ' + xhr.statusText);
    };
    xhr.send(formData);
}
</script>
                </div>

                <div class="col">
                    
                </div>
            </article>

            <aside class="col">
            </aside>
        </section>
        <footer class="row m-5">
</footer>
    </body>
