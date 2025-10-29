<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    die("Access denied.");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("DELETE FROM Admins WHERE adminID=? AND status='pending'");
$stmt->execute([$id]);

header("Location: super_dashboard.php");
exit;
?>
