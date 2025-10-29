<?php
session_start();
require_once "config.php";

// Ensure user is logged in and is super admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super') {
    header("Location: signin.php");
    exit();
}

// Fetch all pending admin requests
$stmt = $conn->prepare("SELECT adminID, username, email, created_at, status 
                        FROM Admins 
                        WHERE status = 'pending'");
$stmt->execute();
$pendingAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link href="https://elitelearnersacademy.com/CSS/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; }
        .sidebar { position: fixed; top: 0; left: -220px; width: 220px; height: 100%; background-color: #343a40; color: #fff; transition: left 0.3s; overflow-y: auto; z-index: 1000; padding-top: 60px; }
        .sidebar.active { left: 0; }
        .sidebar h2 { text-align: center; margin-bottom: 20px; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { border-bottom: 1px solid #495057; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #fff; text-decoration: none; }
        .sidebar-menu li a:hover, .sidebar-menu li.active a { background-color: #007bff; }
        .content { margin-left: 0; transition: margin-left 0.3s; padding: 20px; }
        .content.shifted { margin-left: 220px; }
        .top-navbar { position: fixed; top: 0; left: 0; width: 100%; height: 60px; background-color: #fff; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; z-index: 1100; }
        .top-navbar .toggle-btn { font-size: 24px; cursor: pointer; }
        .top-navbar h1 { font-size: 20px; margin: 0; }
        .card { border-radius: 10px; }
        .table th, .table td { vertical-align: middle; }
        .action-buttons a { width: 80px; }
        .badge-pending { background-color: #ffc107; color: #212529; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <h2>Super Admin</h2>
    <ul class="sidebar-menu">
        <li class="active"><a href="#" data-page="dashboard">Dashboard</a></li>
        <li><a href="#" data-page="pending_admins">Pending Admin Requests</a></li>
        <li><a href="#" data-page="settings">Settings</a></li>
        <li><a href="#" data-page="bulk_emails">Bulk Emails</a></li>
        <li></li>
    </ul>
    <a href="logout.php" data-page="logout">Logout</a>
</aside>

<!-- Top Navbar -->
<div class="top-navbar">
    <span class="toggle-btn" id="toggleBtn">☰</span>
    <h1>Hi, <?= htmlspecialchars($_SESSION['username']); ?></h1>
</div>

<!-- Main Content -->
<div class="content" id="content">
    <div id="dashboard" class="page">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h4 class="card-title mb-4">📊 Dashboard</h4>
                <p>Welcome to the Super Admin Dashboard. Use the sidebar to navigate.</p>

             
            </div>
        </div>
    </div>

    <div id="pending_admins" class="page" style="display:none;">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h4 class="card-title mb-4">📋 Pending Admin Requests</h4>
                <?php if (count($pendingAdmins) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Date Requested</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingAdmins as $index => $admin): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($admin['username']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td><?= htmlspecialchars($admin['created_at'] ?? '-') ?></td>
                                    <td><span class="badge badge-pending"><?= htmlspecialchars($admin['status']) ?></span></td>
                                    <td class="text-center action-buttons">
                                        <a href="approve_admin.php?adminID=<?= $admin['adminID'] ?>" 
                                           class="btn btn-success btn-sm me-1">Approve</a>
                                        <a href="reject_admin.php?adminID=<?= $admin['adminID'] ?>" 
                                           class="btn btn-danger btn-sm">Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending admin requests at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="settings" class="page" style="display:none;">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h4 class="card-title mb-4">⚙ Settings</h4>
                <p>Super Admin settings and preferences will appear here.</p>
            </div>
        </div>
    </div>
</div>

<div id="bulk_emails" class="page" style="display:none;">
    <div class="card shadow-sm mt-5">
        <div class="card-body">
            <h4 class="card-title mb-4">📧 Send Bulk Emails</h4>
            <form method="post" action="">
                <button type="submit" name="send_bulk_emails" class="btn btn-primary">
                    Send Emails to Eligible Voters
                </button>
            </form>
            <?php
            if (isset($_POST['send_bulk_emails'])) {
                require 'vendor/autoload.php';
               /* use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception; */

                // Fetch only eligible voters
                $sql = "SELECT r.Firstname, r.Surname, r.Email, r.City, r.VoterNumber
                        FROM registeredVOTERS r
                        INNER JOIN voterSTATUS s ON r.voterID = s.voterID
                        WHERE s.Status = 'Eligible' AND r.Email IS NOT NULL";

                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($voters) {
                    $mail = new PHPMailer(true);
                    try {
                        // Mail server config (replace with your SMTP details)
                        $mail->isSMTP();
                        $mail->Host = 'smtp.yourserver.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'youremail@example.com';
                        $mail->Password = 'yourpassword';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;
                        $mail->setFrom('youremail@example.com', 'Electoral Council');

                        $sent = 0;
                        foreach ($voters as $voter) {
                            $mail->clearAllRecipients();
                            $mail->addAddress($voter['Email'], $voter['Firstname'] . ' ' . $voter['Surname']);
                            $mail->Subject = "Your Voter Information";
                            $mail->Body = "Dear {$voter['Firstname']} {$voter['Surname']},\n\n"
                                        . "✅ You are eligible to vote.\n\n"
                                        . "Voter Number: {$voter['VoterNumber']}\n"
                                        . "City: {$voter['City']}\n\n"
                                        . "Please keep this voter number safe.\n\n"
                                        . "Regards,\nElectoral Council";
                            $mail->send();
                            $sent++;
                        }
                        echo "<div class='alert alert-success mt-3'>✅ Successfully sent {$sent} emails.</div>";
                    } catch (Exception $e) {
                        echo "<div class='alert alert-danger mt-3'>❌ Email error: {$mail->ErrorInfo}</div>";
                    }
                } else {
                    echo "<div class='alert alert-warning mt-3'>⚠ No eligible voters found with email addresses.</div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("content");
    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("active");
        content.classList.toggle("shifted");
    });

    // Sidebar menu navigation
    const pages = document.querySelectorAll('.page');
    document.querySelectorAll('.sidebar-menu li a').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const pageId = link.dataset.page;
            pages.forEach(p => p.style.display = 'none');
            const activePage = document.getElementById(pageId);
            if (activePage) activePage.style.display = 'block';

            // Highlight active menu item
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
            link.parentElement.classList.add('active');
        });
    });
</script>

</body>
</html>
