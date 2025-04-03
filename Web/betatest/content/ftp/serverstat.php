<?php
// Include this at the top to see potential errors
// Comment out in production
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    session_destroy();
    header("location: ../../index.php");
    exit();
}

require '../../vendor/autoload.php';

use phpseclib3\Net\SSH2;

$ssh_host = 'localhost';
$ssh_username = 'dataacc';
$ssh_password = 'micalis1235';

$ssh = new SSH2($ssh_host);
if (!$ssh->login($ssh_username, $ssh_password)) {
    die('SSH login failed');
}

function getDiskData($ssh, $command) {
    $output = $ssh->exec($command);
    return $output ?: "⚠️ Command failed or returned empty result";
}

$diskStats = [
    'Disk1' => getDiskData($ssh, 'df -h /dev/ubuntu-vg/ubuntu-lv | awk \'NR==1{print "┌──────────────┬────────┬────────┬────────┬──────┬─────────────┐\\n│ Filesystem   │ Size   │ Used   │ Avail  │ Use% │ Mounted on  │\\n├──────────────┼────────┼────────┼────────┼──────┼─────────────┤"} NR>1{printf "│ %-12s │ %6s │ %6s │ %6s │ %4s │ %-11s │\\n", $1, $2, $3, $4, $5, $6} END{print "└──────────────┴────────┴────────┴────────┴──────┴─────────────┘"}\''),
    'Disk2' => getDiskData($ssh, 'df -h /dev/sdb1 | awk \'NR==1{print "┌──────────────┬────────┬────────┬────────┬──────┬─────────────┐\\n│ Filesystem   │ Size   │ Used   │ Avail  │ Use% │ Mounted on  │\\n├──────────────┼────────┼────────┼────────┼──────┼─────────────┤"} NR>1{printf "│ %-12s │ %6s │ %6s │ %6s │ %4s │ %-11s │\\n", $1, $2, $3, $4, $5, $6} END{print "└──────────────┴────────┴────────┴────────┴──────┴─────────────┘"}\''),
    'RAID' => getDiskData($ssh, 'cat /proc/mdstat | sed "s/^/    /"'),
    'CPU' => getDiskData($ssh, 'mpstat 1 1 | awk \'/all/{printf "User: %5.1f%%\\nSystem: %5.1f%%\\nIdle: %5.1f%%\\nI/O Wait: %5.1f%%\\n", $4, $6, $13, $7}\''),
    'Memory' => getDiskData($ssh, 'free -h | awk \'/^Mem:/{printf "Total: %8s\\nUsed: %8s\\nFree: %8s\\nAvailable: %5s\\n", $2, $3, $4, $7} /^Swap:/{printf "Swap Total: %5s\\nSwap Used: %5s\\n", $2, $3}\''),
    'Last Updated' => date('Y-m-d H:i:s')
];

$disks = [
    [
        'id' => 'Disk1',
        'title' => 'Disk1 (sda)',
        'icon' => 'hdd',
        'color' => 'primary'
    ],
    [
        'id' => 'Disk2',
        'title' => 'Disk2 (sdb)',
        'icon' => 'hdd',
        'color' => 'primary'
    ],
    [
        'id' => 'RAID',
        'title' => 'RAID Status',
        'icon' => 'shield-check',
        'color' => 'warning'
    ],
    [
        'id' => 'CPU',
        'title' => 'CPU Usage',
        'icon' => 'cpu',
        'color' => 'info'
    ],
    [
        'id' => 'Memory',
        'title' => 'Memory Usage',
        'icon' => 'memory',
        'color' => 'success'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Server Status</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="../../img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../../css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/serverstat.css">
    <script src="../../js/bootstrap.bundle.js"></script>
</head>

<body class="container-fluid text-center">
<div class="d-flex justify-content-end p-3">
    <button id="themeToggle" class="btn btn-sm theme-toggle">
        <i class="bi"></i>
        <span id="themeText"></span>
    </button>
</div>

<header class="row border-bottom m-5">
    <h1>Server Status</h1>
    <div class="mb-3 p-3">
        <a href="../logout.php" class="btn btn-danger">Logout</a>
        <a href="../changepassword.php" class="btn btn-warning">Change Password</a>
        <a href="../adminpanel.php" class="btn btn-primary">Admin Panel</a>
        <a href="index.php" class="btn btn-primary">SFTP</a>
    </div>
</header>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm bg-body-tertiary">
                <div class="card-header monitor-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h4 mb-0">
                            <i class="bi bi-server text-primary me-2"></i>
                            Server Disk Status
                        </h1>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-clock me-1"></i>
                            <?= htmlspecialchars($diskStats['Last Updated']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row g-4">
                        <?php foreach ($disks as $disk): ?>
                            <div class="col-md-6">
                                <div class="monitor-card card h-100 border-top-0 border-start-0 border-end-0 border-bottom-3 border-<?= $disk['color'] ?>">
                                    <div class="card-header">
                                        <h3 class="h6 mb-0">
                                            <i class="bi bi-<?= $disk['icon'] ?> text-<?= $disk['color'] ?> me-2"></i>
                                            <?= $disk['title'] ?>
                                        </h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <pre class="disk-output p-3 mb-0"><?= 
                                            // Special formatting for disk entries
                                            ($disk['id'] === 'Disk1' || $disk['id'] === 'Disk2') 
                                                ? str_replace(
                                                    ['┌', '─', '┬', '┐', '├', '┼', '┤', '└', '┴', '┘', '│'], 
                                                    ['╔', '═', '╤', '╗', '╟', '┼', '╢', '╚', '╧', '╝', '║'],
                                                    htmlspecialchars($diskStats[$disk['id']])
                                                )
                                                : htmlspecialchars($diskStats[$disk['id']])
                                        ?></pre>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="d-flex flex-column justify-content-center align-items-center p-3 border-top gap-3">
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
<script src="../../js/theme.js"></script>
</body>
</html>