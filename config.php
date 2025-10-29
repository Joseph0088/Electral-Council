<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {

session_start();
}

try {
    // adjust DB name/user/pass as needed
    $dsn  = 'mysql:host=localhost;dbname=ElectralCouncilzasam;charset=utf8mb4';
    $user = 'root';
    $pass = 'mary1234....J2002t11';

    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('DB connection failed: ' . $e->getMessage());
}

?>
