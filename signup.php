<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($password !== $confirmPassword) {
        die("Passwords do not match. <a href='signup.php'>Try again</a>");
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Check if email or username already exists
    $stmt = $conn->prepare("SELECT * FROM Admins WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->rowCount() > 0) {
        die("Username or email already exists. <a href='signup.php'>Try again</a>");
    }

    // Insert new admin as pending
    $stmt = $conn->prepare("INSERT INTO Admins (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);

    echo "Your account request has been submitted and is pending approval by the super admin.";
}
?>
