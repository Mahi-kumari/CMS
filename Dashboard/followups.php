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
$followups = [];
$leadCount = 0;
try {
    $mysqli = db_connect();
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads");
    if ($res) {
        $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
        $res->free();
    }
    $stmt = $mysqli->prepare("SELECT full_name, phone_number, course_applied, counselor_name, follow_up_date, counseling_status FROM leads WHERE follow_up_date = CURDATE() ORDER BY follow_up_date DESC, lead_id DESC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $followups[] = $row;
        }
        $stmt->close();
    }
    $mysqli->close();
} catch (RuntimeException $e) {
    $followups = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM — Today's follow-ups" />
    <title>Today's Follow-ups | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css?v=2" />
    <link rel="stylesheet" href="css/navbar.css?v=2" />
    <link rel="stylesheet" href="css/sidebar.css?v=2" />
    <link rel="stylesheet" href="css/view-leads.css?v=2" />
</head>

<body>
    <nav class="navbar" id="navbar">
        <div class="navbar-brand">
            <button class="navbar-hamburger show-mobile" id="hamburgerBtn" aria-label="Toggle menu">?</button>
            <div class="navbar-logo">IC</div>
            <div class="navbar-brand-text hide-mobile">
                <span class="navbar-brand-name">ICSS CRM</span>
                <span class="navbar-brand-sub">Management Suite</span>
            </div>
        </div>
        <div class="navbar-center hide-mobile">
            <div class="search-wrapper">
                <span class="search-icon">&#x1F50D;</span>
                <input type="text" class="search-input" id="globalSearch" placeholder="Search leads, contacts, courses..."
                    autocomplete="off" />
                <span class="search-shortcut">Ctrl+K</span>
            </div>
        </div>
        <div class="navbar-actions">
            <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">&#x1F514;<span
                    class="notification-dot"></span></button>
            <div class="profile-wrapper">
                <div class="profile-trigger">
                    <div class="profile-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="profile-info hide-mobile">
                        <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                    </div>
                    <span class="profile-chevron hide-mobile">&#9662;</span>
                </div>
                <div class="profile-dropdown">
                    <div class="dropdown-header">
                        <div class="profile-name"><?php echo htmlspecialchars($fullName); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x1F464;</span> My
                            Profile</a>
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">&#x2699;&#xFE0F;</span> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item danger" href="logout.php"><span
                                class="dropdown-icon">&#x1F6AA;</span> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-shell">
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
            <a href="view-leads.php" class="sidebar-link" data-tooltip="View Leads">
              <span class="sidebar-icon">&#x1F4CB;</span>
              <span class="sidebar-link-text">View Leads</span>
              <span class="sidebar-badge"><?php echo (int)$leadCount; ?></span>
            </a>
            <a href="followups.php" class="sidebar-link active" data-tooltip="Today''s Follow-up">
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

        <main class="main-content">
            <div class="page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                <div>
                    <h1>Today's Follow-ups</h1>
                    <div class="breadcrumb">
                        <a href="index.php">Dashboard</a>
                        <span class="separator">/</span>
                        <span>Today's Follow-ups</span>
                    </div>
                </div>
                <div class="btn btn-secondary">Date: <?php echo date('d-m-Y'); ?></div>
            </div>

            <div class="leads-table-container">
                <div class="leads-table-header">
                    <div class="leads-count">Showing <strong><?php echo count($followups); ?></strong> follow-ups</div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Lead Name</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Counselor</th>
                                <th>Follow-up Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($followups)) : ?>
                                <tr>
                                    <td colspan="6" style="text-align:center; color: var(--clr-text-secondary);">No follow-ups scheduled for today.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($followups as $r) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['phone_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['course_applied'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['counselor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['follow_up_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['counseling_status'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="js/app.js?v=4"></script>
</body>

</html>

