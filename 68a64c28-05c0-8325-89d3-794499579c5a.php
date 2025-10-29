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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Electoral Council Voter Management</title>
<link href="https://elitelearnersacademy.com/CSS/bootstrap.min.css" rel="stylesheet">
<style>
/* Sidebar base */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 220px;
    height: 100%;
    background: #2c3e50;
    color: #fff;
    padding-top: 60px;
    transition: transform 0.3s ease;
    z-index: 1000;
}
.sidebar.hidden { transform: translateX(-100%); }
.sidebar-header {
    position: fixed;
    top: 0;
    left: 0;
    width: 220px;
    height: 60px;
    background: #1a252f;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px;
    color: #fff;
    z-index: 1001;
}
#toggleBtn { background: transparent; border: none; font-size: 20px; color: #fff; cursor: pointer; }

.sidebar-menu { list-style: none; padding: 0; margin: 0; }
.sidebar-menu li { border-bottom: 1px solid rgba(255,255,255,0.1); }
.sidebar-menu li a { display: block; padding: 12px 20px; text-decoration: none; color: #fff; transition: background 0.2s; }
.sidebar-menu li a:hover,
.sidebar-menu li.active a { background: #34495e; }

/* Content area */
.content { margin-left: 220px; margin-top:60px; padding: 20px; transition: margin-left 0.3s ease; }
.sidebar.hidden ~ .content { margin-left: 0; }
.content.expanded {
    margin-left: 0 !important;
}

.top-navbar { position: fixed; top: 0; left: 0; width: 100%; height: 60px; background-color: #1a252f; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; z-index: 1100; #fff }
.top-navbar .toggle-btn { font-size: 24px; cursor: pointer; }
.top-navbar h1 { font-size: 20px; margin: 0; }

.dropdown-menu {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    border-radius: 6px;
    min-width: 250px;
    padding: 10px;
    z-index: 1000;
}
.dropdown-menu.show {
    display: block;
}
.dropdown {
    position: relative;
}


#voterStatusTable {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
  font-size: 16px;
  font-family: "Segoe UI", Tahoma, sans-serif;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
}

/* Header styling */
#voterStatusTable thead {
  background-color: #2c3e50;
  color: #fff;
  text-align: left;
}

#voterStatusTable th, 
#voterStatusTable td {
  padding: 12px 15px;
  border-bottom: 1px solid #ddd;
}

/* Row hover effect */
#voterStatusTable tbody tr:hover {
  background-color: #f1f1f1;
  cursor: pointer;
}

/* Status color coding */
.eligible { 
  background-color: #eafaf1; 
  color: #2e7d32; 
  font-weight: 600; 
  border-left: 5px solid #2ecc71;
}

.ineligible { 
  background-color: #fdecea; 
  color: #c62828; 
  font-weight: 600; 
  border-left: 5px solid #e74c3c;
}

.pending { 
  background-color: #fff8e1; 
  color: #f57c00; 
  font-weight: 600; 
  border-left: 5px solid #f39c12;
}

/* Center "Reason" column for clarity */
#voterStatusTable td:last-child {
  text-align: center;
  font-style: italic;
}

/* Summary bar container */
#summaryBar {
  display: flex;
  gap: 20px;
  margin: 15px 0;
  font-family: "Segoe UI", Tahoma, sans-serif;
}

.summary-item {
  flex: 1;
  padding: 12px;
  border-radius: 8px;
  text-align: center;
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-eligible { background-color: #2ecc71; }
.summary-ineligible { background-color: #e74c3c; }
.summary-pending { background-color: #f39c12; }

/* Highlight status cells as clickable */
.status-cell {
  cursor: pointer;
  text-align: center;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.status-cell:hover {
  opacity: 0.8;
  transform: scale(1.03);
}

</style>
</head>
<body>

    <!-- Sidebar Header -->
    <div class="top-navbar">
        <h2 style='color:white;'>VVS</h2>
        <button id="toggleBtn">☰</button>
    </div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">


    <ul class="sidebar-menu">
        <!-- Greeting -->
        <li class="active">
            <span>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        </li>

       <!-- Table Selector -->
       <li data-page="reports">
           <div class="mb-3 p-2">
                  <label for="tableSelect" class="form-label text-white">Select Table:</label>
                  <select id="tableSelect" class="form-select w-100">
                      <option value="registeredVOTERS">Registered Voters</option>
                      <option value="voterSTATUS">Voter Eligibility</option>
                  </select>
            </div>
       </li>

        <!-- Add Voters Dropdown -->
        <li class="dropdown">
            <a href="#" class="dropdown-toggle">➕ Add Voters</a>
            <ul class="dropdown-menu">
                <li>
                    <div class="card mb-4 shadow-sm p-2">
                        <h5 class="card-title">Upload Voter CSV</h5>
                              <form id="csvForm" enctype="multipart/form-data" class="d-flex gap-2">
                                  <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                                  <button type="submit" class="btn btn-success">Upload & Verify</button>
                              </form>
                              <div id="uploadResult" class="mt-2"></div>

                    </div>
                </li>
            </ul>
        </li>

        <!-- Bulk Verify Button -->
        <li>
            <button id="bulkVerify" class="btn btn-success w-100 mb-2">✔ Verify Voter</button>
        </li>

        <!-- Logout -->
        <li>
            <a href="logout.php" data-page="logout">🚪 Logout</a>
        </li>
    </ul>
</aside>

<!-- Content Area -->
<main class="content" id="content">
    <div class="container mt-4">

        <!-- Search Input -->
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by Firstname, Surname, or City">
        </div>
        <!-- Sorting Options -->
        <div class="mb-3 d-flex gap-2">
            <select id="sortSelect" class="form-select w-auto">
                <option value="">Sort by...</option>
                <option value="City">City</option>
                <option value="EDU_Status">EDU_Status</option>
            </select>
            <button id="btnSort" class="btn btn-primary">Sort</button>
        </div>

        <h2 class="mb-3">Members Table</h2>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>zsmID</th>
                    <th>Firstname</th>
                    <th>Surname</th>
                    <th>Membership</th>
                    <th>City</th>
                    <th>EDU_Status</th>
                </tr>
            </thead>
            <tbody id="membersTableBody"></tbody>
        </table>


    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Voter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalVoterDetails"></p>
                    <div class="mb-3">
                        <label for="modalStatus" class="form-label">New Status</label>
                        <select id="modalStatus" class="form-select">
                            <option value="Eligible">Eligible</option>
                            <option value="Ineligible">Ineligible</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalReason" class="form-label">Reason (optional)</label>
                        <textarea id="modalReason" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="modalSubmit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
        
    </div>
<div class="container mt-4">
<h2 id="voterTableTitle" style="font-family:'Segoe UI',sans-serif; color:#2c3e50; margin-bottom:10px;">
    Registered Voters
</h2>

<div id="summaryBar">
  <div class="summary-item summary-eligible">🟢 Eligible: <span id="eligibleCount">0</span></div>
  <div class="summary-item summary-ineligible">🔴 Ineligible: <span id="ineligibleCount">0</span></div>
  <div class="summary-item summary-pending">🟡 Pending: <span id="pendingCount">0</span></div>
</div>

<table id="voterStatusTable">
  <thead>
    <tr>
      <th><input type="checkbox" id="selectAll"></th>
      <th>ID</th>
      <th>Firstname</th>
      <th>Surname</th>
      <th>City</th>
      <th>Academic Year</th>
      <th>Status</th>
      <th>Reason</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

        <!-- Bulk Actions -->
        <div class="mb-3 d-flex gap-2">
            <button class="btn btn-success" id="bulkEligible">Mark Selected as Eligible</button>
            <button class="btn btn-danger" id="bulkIneligible">Mark Selected as Ineligible</button>
        </div>

</div>

</main>

<!-- JS -->
<script src="https://elitelearnersacademy.com/JS/bootstrap.bundle.min.js"></script>
<script src="k8n2b4q7p1x5r9s3z6d0c1v2l.js" defer></script>
<script>
  // Handle dropdown toggle
document.addEventListener("DOMContentLoaded", () => {
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener("click", (e) => {
            e.preventDefault();
            const dropdown = toggle.parentElement;
            const menu = dropdown.querySelector(".dropdown-menu");

            // Close other dropdowns
            document.querySelectorAll(".dropdown-menu").forEach(m => {
                if (m !== menu) m.classList.remove("show");
            });

            // Toggle current dropdown
            menu.classList.toggle("show");
        });
    });

    // Close dropdowns if clicked outside
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".dropdown")) {
            document.querySelectorAll(".dropdown-menu").forEach(menu => menu.classList.remove("show"));
        }
    });
});



document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById("toggleBtn");
    const sidebar = document.getElementById("sidebar");
    const content = document.querySelector(".content");

    toggleBtn.addEventListener("click", () => {
        sidebar.classList.toggle("hidden");
        content.classList.toggle("expanded");
    });
});
</script>



</body>
</html>

