<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("Access denied.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("UPDATE Admins SET status='active' WHERE adminID=?");
$stmt->execute([$id]);

header("Location: super_dashboard.php");
exit;
?>
