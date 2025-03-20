<?php
session_start();
if (empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    session_destroy();
    header("location: ../index.html");
    exit();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

$servername = "localhost:3306";
$username = "userlogin";
$password = "zl*eDJmgT5sQNTuj";
$db = "usbraidlogin";

$conn = new mysqli($servername, $username, $password, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("<script>alert('Database connection failed!');</script>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<script>console.log('Form submitted!');</script>";
    echo "<script>console.log(" . json_encode($_POST) . ");</script>";

    if (isset($_POST['uname'], $_POST['pswd'], $_POST['defPath'])) {
        $uname = htmlspecialchars($_POST['uname']);
        $pswd = htmlspecialchars($_POST['pswd']);
        $is_admin = isset($_POST['admin']) ? 1 : 0;
        $defPath = htmlspecialchars($_POST['defPath']);
        $delPer = isset($_POST['delPer']) ? 1 : 0;
        $dowPer = isset($_POST['downPer']) ? 1 : 0;
        $upPer = isset($_POST['upPer']) ? 1 : 0;

        $sql = "SELECT * FROM users WHERE uname=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Error: Username already exists!');</script>";
        } else {
            $sql = "INSERT INTO users (uname, pswd, admin, defPath, delPer, downPer, upPer) VALUES (?, ?, ?, ?, ?, ?, ?)";
            echo "<script>console.log('SQL: " . $sql . "');</script>";
            echo "<script>console.log('Params: " . json_encode([$uname, $pswd, $is_admin, $defPath, $delPer, $dowPer, $upPer]) . "');</script>";

            $stmt = $conn->prepare($sql);
            $hash = password_hash($pswd, PASSWORD_BCRYPT);
            $stmt->bind_param("ssisiii", $uname, $hash, $is_admin, $defPath, $delPer, $dowPer, $upPer);

            if ($stmt->execute()) {
                echo "<script>alert('User added successfully!');</script>";
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }
        }
    } else {
        echo "<script>alert('Error: Missing form data!');</script>";
    }
}

if (isset($_GET['delete'])) {
    $delete_uname = htmlspecialchars($_GET['delete']);
    $sql = "DELETE FROM users WHERE uname=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $delete_uname);
    $stmt->execute();
    echo "<script>alert('User deleted successfully!'); window.location.href='adminpanel.php';</script>";
}

$result = $conn->query("SELECT uname, admin, defPath, delPer, downPer, upPer FROM users");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <script src="../js/bootstrap.bundle.js"></script>
    <script>
        function confirmDelete(uname) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'adminpanel.php?delete=' + encodeURIComponent(uname);
            }
        }
    </script>
    <style>
        .topRow {
            height: 20vh;
        }
        .custom-container {
            max-width: 60%;
            margin: 0 auto;
        }
        .add-user-form {
            max-width: 400px;
            margin: 0 auto;
        }
        .form-check {
            display: flex;
            align-items: center;
            width: 100%;
            justify-content: flex-start;
        }
        .form-check-input {
            margin-right: 10px;
        }
        .form-check-label {
            margin-left: 0.5rem;
        }
        body {
                min-width: 950px;
        }
    </style>
</head>
<body class="text-center">
<div class="custom-container">
    <header class="row topRow border-dark border-bottom m-5">
        <h1>USB Raid pole</h1>
        <div class="mb-3 p-3">
            <a href="logout.php" class="btn btn-danger">Logout</a>
            <a href="changepassword.php" class="btn btn-warning">Change Password</a>
            <a href="ftp/index.php" class="btn btn-primary">SFTP</a>
        </div>
    </header>
    <section class="row">
        <aside class="col-2"></aside>
        <article class="col border border-2 border-primary rounded p-2">
            <h2 class="mb-4">User Management</h2>
            <form method="POST" action="" class="mb-4 add-user-form">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="uname" class="form-control" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="pswd" class="form-control" placeholder="Password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Default Path</label>
                    <input type="text" name="defPath" class="form-control" placeholder="Default Path (e.g., /mnt/raid)" required>
                </div>
                <div class="d-flex flex-column align-items-center mb-3">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="admin" id="adminCheck" value="1" class="form-check-input">
                        <label class="form-check-label" for="adminCheck">Admin</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="delPer" id="delPerCheck" value="1" class="form-check-input">
                        <label class="form-check-label" for="delPerCheck">Delete Permission</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="downPer" id="downPerCheck" value="1" class="form-check-input">
                        <label class="form-check-label" for="downPerCheck">Download Permission</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="upPer" id="upPerCheck" value="1" class="form-check-input">
                        <label class="form-check-label" for="upPerCheck">Upload Permission</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add User</button>
            </form>
            <h3 class="mb-3">Users List</h3>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Username</th>
                        <th>Admin</th>
                        <th>Default Path</th>
                        <th>Delete Permission</th>
                        <th>Download Permission</th>
                        <th>Upload Permission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['uname']); ?></td>
                            <td><?php echo $row['admin'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo htmlspecialchars($row['defPath']); ?></td>
                            <td><?php echo $row['delPer'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $row['downPer'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $row['upPer'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete('<?php echo htmlspecialchars($row['uname']); ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </article>
        <aside class="col-2"></aside>
    </section>
    <footer class="row m-5">
        <span>Developed by Michal Sedlák</span>
    </footer>
</div>
</body>
</html>
<?php
$conn->close();
?>