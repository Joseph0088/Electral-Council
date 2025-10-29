<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // redirect to login form if accessed directly
    header("Location: index.php");
    exit();
}

// get posted values
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT AdminID, Username, Email, Password, Role, Status FROM Admins WHERE Email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    die("Invalid credentials. <a href='index.php'>Try again</a>");
}

if (!password_verify($password, $user['Password'])) {
    die("Invalid credentials. <a href='index.php'>Try again</a>");
}

if ($user['Status'] !== 'active') {
    die("Your account is not active (status: {$user['Status']}).");
}

// set session variables
$_SESSION['user_id'] = $user['AdminID'];
$_SESSION['username'] = $user['Username'];
$_SESSION['role'] = strtolower($user['Role']);

// redirect by role
if ($_SESSION['role'] === 'super') {
    header("Location: super_dashboard.php");
} else {
    header("Location: 68a64c28-05c0-8325-89d3-794499579c5a.php");
}
exit();
