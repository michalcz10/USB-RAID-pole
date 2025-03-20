<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['uname'])) {
    header("location: ../index.html");
    exit();
}

$servername = "localhost:3306";
$username = "userlogin";
$password = "zl*eDJmgT5sQNTuj";
$db = "usbraidlogin";

// Process form submission
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $current_password = htmlspecialchars($_POST['current_password']);
        $new_password = htmlspecialchars($_POST['new_password']);
        $confirm_password = htmlspecialchars($_POST['confirm_password']);
        $uname = $_SESSION['uname'];

        // Validate password match
        if ($new_password !== $confirm_password) {
            $message = "New passwords do not match!";
            $messageType = "danger";
        } else {
            // Connect to database
            $conn = new mysqli($servername, $username, $password, $db);
            $conn->set_charset("utf8");

            if ($conn->connect_error) {
                $message = "Database connection failed!";
                $messageType = "danger";
            } else {
                // Verify current password
                $sql = "SELECT pswd FROM users WHERE uname=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $uname);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $stored_hash = $row["pswd"];

                    if (password_verify($current_password, $stored_hash)) {
                        // Update password
                        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $update_sql = "UPDATE users SET pswd=? WHERE uname=?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("ss", $new_hash, $uname);

                        if ($update_stmt->execute()) {
                            $message = "Password changed successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error updating password: " . $conn->error;
                            $messageType = "danger";
                        }
                        $update_stmt->close();
                    } else {
                        $message = "Current password is incorrect!";
                        $messageType = "danger";
                    }
                } else {
                    $message = "User not found!";
                    $messageType = "danger";
                }

                $stmt->close();
                $conn->close();
            }
        }
    } else {
        $message = "All fields are required!";
        $messageType = "danger";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <script src="../js/bootstrap.bundle.js"></script>
    <style>
        .topRow {
            height: 20vh;
        }
        .custom-container {
            max-width: 60%;
            margin: 0 auto;
        }
        .change-password-form {
            max-width: 400px;
            margin: 0 auto;
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
                <a href="ftp/index.php" class="btn btn-primary">Back to Files</a>
                <?php if (isset($_SESSION["admin"]) && $_SESSION["admin"] == true) { ?>
                    <a href="adminpanel.php" class="btn btn-primary">Admin Panel</a>
                <?php } ?>
            </div>
        </header>
        <section class="row">
            <aside class="col-2"></aside>
            <article class="col border border-2 border-primary rounded p-2">
                <h2 class="mb-4">Change Password</h2>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="mb-4 change-password-form">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </article>
            <aside class="col-2"></aside>
        </section>
        <footer class="row m-5">
            <span>Developed by Michal Sedlák</span>
        </footer>
    </div>
</body>
</html>
