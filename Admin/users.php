<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
start_secure_session('CRM_ADMINSESSID');

$hasAdmin = !empty($_SESSION["is_admin"]) && !empty($_SESSION["admin_id"]);
if (!$hasAdmin) {
  header("Location: login.php");
  exit;
}

require __DIR__ . "/../config/crm.php";

$fullName = $_SESSION["admin_username"] ?? "Admin";
$email = $_SESSION["admin_username"] ?? "admin";
$leadCount = 0;

$parts = preg_split("/\\s+/", trim($fullName));
$initials = "";
foreach ($parts as $p) {
  if ($p !== "") {
    $initials .= strtoupper($p[0]);
  }
  if (strlen($initials) >= 2) break;
}
if ($initials === "") $initials = "A";

try {
  $mysqli = db_connect();
  if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM leads")) {
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
    <meta name="description" content="ICSS CRM — Admin user management." />
    <title>Users | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Dashboard/css/global.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/navbar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/sidebar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/view-leads.css?v=5" />
  </head>

  <body>
    <nav class="navbar" id="navbar">
      <div class="navbar-brand">
        <button class="navbar-hamburger show-mobile" id="hamburgerBtn" aria-label="Toggle menu">&#x2630;</button>
        <div class="navbar-logo">IC</div>
        <div class="navbar-brand-text hide-mobile">
          <span class="navbar-brand-name">ICSS CRM</span>
          <span class="navbar-brand-sub">Management Suite</span>
        </div>
      </div>
      <div class="navbar-actions">
        <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">&#x1F514;
          <span class="notification-dot"></span>
        </button>
        <div class="profile-wrapper">
          <div class="profile-trigger">
            <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="profile-info hide-mobile">
              <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
            </div>
            <span class="profile-chevron hide-mobile">&#x25BE;</span>
          </div>
          <div class="profile-dropdown">
            <div class="dropdown-header">
              <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
              <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
            </div>
            <div class="dropdown-menu">
              <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x1F464;</span> My Profile</a>
              <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x2699;&#xFE0F;</span> Settings</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item danger" href="logout.php"><span class="dropdown-icon">&#x1F6AA;</span> Logout</a>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-shell">
      <aside class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
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
            <a href="view-leads.php" class="sidebar-link" data-tooltip="View Leads">
              <span class="sidebar-icon">&#x1F4CB;</span>
              <span class="sidebar-link-text">View Leads</span>
              <span class="sidebar-badge"><?php echo (int)$leadCount; ?></span>
            </a>
            <a href="followups.php" class="sidebar-link" data-tooltip="Today''s Follow-up">
              <span class="sidebar-icon">&#x1F4C5;</span>
              <span class="sidebar-link-text">Today's Follow-up</span>
            </a>
            <a href="faculty-routine.php" class="sidebar-link" data-tooltip="Faculty Routine">
              <span class="sidebar-icon">&#x1F4DA;</span>
              <span class="sidebar-link-text">Faculty Routine</span>
            </a>
            <a href="data-management.php" class="sidebar-link" data-tooltip="Data Management">
              <span class="sidebar-icon">&#x1F5C4;</span>
              <span class="sidebar-link-text">Data Management</span>
            </a>
            <a href="users.php" class="sidebar-link" data-tooltip="Users">
              <span class="sidebar-icon">&#x1F465;</span>
              <span class="sidebar-link-text">Users</span>
            </a>
            <a href="lead-ownership.php" class="sidebar-link" data-tooltip="Lead Ownership Audit">
              <span class="sidebar-icon">&#x1F4DD;</span>
              <span class="sidebar-link-text">Lead Ownership</span>
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

      <main class="main-content">
        <div class="page-header">
          <h1>Users</h1>
          <div class="breadcrumb">
            <a href="index.php">Dashboard</a>
            <span class="separator">/</span>
            <span>Users</span>
          </div>
        </div>

        <div class="leads-table-container">
          <div class="leads-table-header">
            <div class="leads-count" id="usersCount">All users</div>
          </div>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Last Login</th>
                  <th>Total Leads</th>
                </tr>
              </thead>
              <tbody id="adminUsersTbody">
                <tr>
                  <td colspan="5" style="text-align:center; color: var(--clr-text-secondary);">Loading...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="table-footer">
            <div class="table-footer-info" id="usersCount2"></div>
            <div class="pagination" id="usersPagination"></div>
          </div>
        </div>
      </main>
    </div>

    <script>window.CRM_ROLE = 'admin';</script>
    <script src="../Dashboard/js/app.js?v=4"></script>
    <script src="../Dashboard/js/admin-users.js?v=1"></script>
  </body>
</html>



