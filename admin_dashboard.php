<?php
session_start();
require_once "config.php";

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: signin.php");
    exit();
}

// Ensure the role is admin
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied. Not an admin.";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h1>Welcome Admin</h1>
    <p>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    <a href="logout.php">Logout</a>
</body>
</html>
