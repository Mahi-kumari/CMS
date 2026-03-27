<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
start_secure_session('CRM_USERSESSID');

$hasUser = !empty($_SESSION["user_id"]);
if (!$hasUser) {
  header("Location: logout.php");
  exit;
}

$fullName = $_SESSION["full_name"] ?? "User";
$email = $_SESSION["email"] ?? "user@example.com";

$parts = preg_split("/\\s+/", trim($fullName));
$initials = "";
foreach ($parts as $p) {
  if ($p !== "") {
    $initials .= strtoupper($p[0]);
  }
  if (strlen($initials) >= 2) break;
}
if ($initials === "") $initials = "U";

require __DIR__ . "/../config/crm.php";
$leadCount = 0;
try {
    $mysqli = db_connect();
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads");
    if ($res) {
        $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
        $res->free();
    }
    $mysqli->close();
} catch (Throwable $e) {
    $leadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM — Search, filter, and manage all your leads." />
    <title>View Leads | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/view-leads.css" />
</head>

<body>
    <!-- ═══════════════ TOP NAVBAR ═══════════════ -->
    <nav class="navbar" id="navbar">
        <div class="navbar-brand">
            <button class="navbar-hamburger show-mobile" id="hamburgerBtn" aria-label="Toggle menu">☰</button>
            <div class="navbar-logo">IC</div>
            <div class="navbar-brand-text hide-mobile">
                <span class="navbar-brand-name">ICSS CRM</span>
                <span class="navbar-brand-sub">Management Suite</span>
            </div>
        </div>
        </div>
        <div class="navbar-actions">
            <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">🔔<span
                    class="notification-dot"></span></button>
            <div class="profile-wrapper">
                <div class="profile-trigger">
                    <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="profile-info hide-mobile">
                        <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                        </div>
                    <span class="profile-chevron hide-mobile">▾</span>
                </div>
                <div class="profile-dropdown">
                    <div class="dropdown-header">
                        <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="settings.html"><span class="dropdown-icon">👤</span> My
                            Profile</a>
                        <a class="dropdown-item" href="settings.html"><span class="dropdown-icon">⚙️</span> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item danger" href="logout.php"><span
                                class="dropdown-icon">🚪</span> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-shell">
        <!-- ═══════════════ LEFT SIDEBAR ═══════════════ -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
            </button>
                    <nav class="sidebar-nav">
          <div class="sidebar-section">
            <div class="sidebar-section-label">Main</div>
            <a href="index.php" class="sidebar-link" data-tooltip="Dashboard">
              <span class="sidebar-icon">&#x1F4CA;</span>
              <span class="sidebar-link-text">Dashboard</span>
            </a>
            <a href="create-lead.php" class="sidebar-link" data-tooltip="Create Lead">
              <span class="sidebar-icon">&#x2795;</span>
              <span class="sidebar-link-text">Create Lead</span>
            </a>
            <a href="view-leads.php" class="sidebar-link active" data-tooltip="View Leads">
              <span class="sidebar-icon">&#x1F4CB;</span>
              <span class="sidebar-link-text">View Leads</span>
              <span class="sidebar-badge"><?php echo (int)$leadCount; ?></span>
            </a>
            <a href="followups.php" class="sidebar-link" data-tooltip="Today''s Follow-up">
              <span class="sidebar-icon">&#x1F4C5;</span>
              <span class="sidebar-link-text">Today''s Follow-up</span>
            </a>
            <a href="faculty-routine.php" class="sidebar-link" data-tooltip="Faculty Routine">
              <span class="sidebar-icon">&#x1F4DA;</span>
              <span class="sidebar-link-text">Faculty Routine</span>
            </a>
          </div>

          <div class="sidebar-section">
            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link" data-tooltip="Settings">
              <span class="sidebar-icon">&#x2699;&#xFE0F;</span>
              <span class="sidebar-link-text">Settings</span>
            </a>
          </div>
        </nav>
</aside>

        <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
        <main class="main-content">
            <div class="page-header"
                style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1>View Leads</h1>
                    <div class="breadcrumb">
                        <a href="index.php">Dashboard</a>
                        <span class="separator">/</span>
                        <span>View Leads</span>
                    </div>
                </div>
                <a href="create-lead.php" class="btn btn-primary">➕ <span class="btn-text">New Lead</span></a>
            </div>

            <!-- Toolbar -->
            <div class="leads-toolbar">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <span class="search-icon">🔍</span>
                        <input type="text" id="leadsSearch" placeholder="Search by name, phone, email…" />
                    </div>
                </div>
                <div class="toolbar-right">
                    <div class="filter-group">
                        <select class="filter-select" id="filterCourse">\n                            <option value="">All Courses</option>\n                        </select>
                        <select class="filter-select" id="filterSource">\n                            <option value="">All Sources</option>\n                        </select>
                        </div>
                </div>
            </div>

            <!-- Table -->
            <div class="leads-table-container">
                <div class="leads-table-header">
                    <div class="leads-count" id="leadsCount">Showing <strong>0</strong> leads</div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Lead Name</th>
                                <th>Phone</th>
                                <th class="hide-mobile">Email</th>
                                <th>Course</th>
                                <th class="hide-mobile">Source</th>
                                <th class="hide-mobile">Assigned</th>
                                <th>Status</th>
                                <th class="hide-mobile">Next Followup</th>
                                <th class="hide-mobile">Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leadsTbody">
                            <!-- Rendered by JS -->
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <div class="table-footer-info" id="leadsCount2"></div>
                    <div class="pagination" id="pagination"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- ═══════════════ View Lead Modal ═══════════════ -->
    <div class="modal-overlay" id="viewLeadModal">
        <div class="modal" style="max-width: 640px;">
            <div class="modal-header">
                <h3>Lead Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="lead-detail-grid" id="leadDetailGrid">
                    <!-- Rendered by JS -->
                </div>
            </div>
            <div class="modal-footer">
                <?php if (!empty($_SESSION["is_admin"])) : ?>
                    <button class="btn btn-primary btn-sm" id="editLeadBtn"><span class="btn-text">Edit Lead</span></button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ═══════════════ Delete Confirm Modal ═══════════════ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 420px;">
            <div class="modal-header">
                <h3>Delete Lead</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color: var(--clr-text-secondary); font-size: 0.875rem;">Are you sure you want to delete this
                    lead? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm modal-close">Cancel</button>
                <button class="btn btn-danger btn-sm" id="confirmDeleteBtn"><span
                        class="btn-text">Delete</span></button>
            </div>
        </div>
    </div>

    <script src="js/app.js?v=4"></script>
    <script src="js/view-leads.js?v=6"></script>
</body>

</html>

