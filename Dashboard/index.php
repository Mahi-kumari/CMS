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

$metrics = [
  "totalLeads" => 0,
  "newLeadsToday" => 0,
  "admissionsToday" => 0,
  "pendingFollowups" => 0,
  "totalRevenue" => 0,
  "pendingPayments" => 0
];

$recentLeads = [];

try {
  $mysqli = db_connect();

  $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads");
  if ($res) { $metrics["totalLeads"] = (int)$res->fetch_assoc()["c"]; $res->free(); }

  $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads WHERE DATE(created_at) = CURDATE()");
  if ($res) { $metrics["newLeadsToday"] = (int)$res->fetch_assoc()["c"]; $res->free(); }

  $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads WHERE class_allotted = 'Yes' AND batch_assigned_date = CURDATE()");
  if ($res) { $metrics["admissionsToday"] = (int)$res->fetch_assoc()["c"]; $res->free(); }

  $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads WHERE follow_up_date = CURDATE()");
  if ($res) { $metrics["pendingFollowups"] = (int)$res->fetch_assoc()["c"]; $res->free(); }

  $res = $mysqli->query("SELECT COALESCE(SUM(COALESCE(final_fee, course_fee, 0)), 0) AS s FROM leads");
  if ($res) { $metrics["totalRevenue"] = (float)$res->fetch_assoc()["s"]; $res->free(); }

  $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads WHERE total_due_amount > 0");
  if ($res) { $metrics["pendingPayments"] = (int)$res->fetch_assoc()["c"]; $res->free(); }

  $res = $mysqli->query("SELECT full_name, phone_number, course_applied, counselor_name, counseling_status, created_at FROM leads ORDER BY created_at DESC LIMIT 5");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $recentLeads[] = $row;
    }
    $res->free();
  }

  $mysqli->close();
} catch (RuntimeException $e) {
  // Keep default zero metrics if DB fails
}

$leadCount = (int)($metrics["totalLeads"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM Dashboard — Monitor leads, admissions, revenue and followups at a glance." />
    <title>Dashboard | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css?v=2" />
    <link rel="stylesheet" href="css/navbar.css?v=2" />
    <link rel="stylesheet" href="css/sidebar.css?v=2" />
    <link rel="stylesheet" href="css/dashboard.css?v=3" />
  </head>

  <body>
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
        <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">
          🔔
          <span class="notification-dot"></span>
        </button>
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
              <a class="dropdown-item" href="settings.php">
                <span class="dropdown-icon">👤</span> My Profile
              </a>
              <a class="dropdown-item" href="settings.php">
                <span class="dropdown-icon">⚙️</span> Settings
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item danger" href="logout.php">
                <span class="dropdown-icon">🚪</span> Logout
              </a>
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
            <a href="index.php" class="sidebar-link active" data-tooltip="Dashboard">
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
        <div class="welcome-banner">
          <h2>Welcome back, <?php echo htmlspecialchars($fullName); ?> 👋</h2>
          <p>Here's what's happening with your leads today.</p>
          <div class="welcome-date hide-mobile">
            <div class="date-day"><?php echo date('j'); ?></div>
            <div class="date-month"><?php echo date('F Y'); ?></div>
          </div>
        </div>

        <div class="metrics-grid">
          <div class="metric-card accent-primary" data-metric="totalLeads">
            <div class="metric-icon icon-primary">📈</div>
            <div class="metric-content">
              <div class="metric-value">0</div>
              <div class="metric-label">Total Leads</div>
              </div>
          </div>

          <div class="metric-card accent-info" data-metric="newLeadsToday">
            <div class="metric-icon icon-info">🆕</div>
            <div class="metric-content">
              <div class="metric-value">0</div>
              <div class="metric-label">New Leads Today</div>
              </div>
          </div>

          <div class="metric-card accent-success" data-metric="admissionsToday">
            <div class="metric-icon icon-success">🎓</div>
            <div class="metric-content">
              <div class="metric-value">0</div>
              <div class="metric-label">Admissions Today</div>
              </div>
          </div>

          <div class="metric-card accent-warning" data-metric="pendingFollowups">
            <div class="metric-icon icon-warning">📞</div>
            <div class="metric-content">
              <div class="metric-value">0</div>
              <div class="metric-label">Pending Followups</div>
              </div>
          </div>

          <div class="metric-card accent-primary" data-metric="totalRevenue">
            <div class="metric-icon icon-primary">💰</div>
            <div class="metric-content">
              <div class="metric-value">₹0</div>
              <div class="metric-label">Total Revenue</div>
              </div>
          </div>

          <div class="metric-card accent-danger" data-metric="pendingPayments">
            <div class="metric-icon icon-danger">⏳</div>
            <div class="metric-content">
              <div class="metric-value">0</div>
              <div class="metric-label">Pending Payments</div>
              </div>
          </div>
        </div>

                <div class="tables-grid">
          <div class="table-card wide">
            <div class="card-header">
              <h3><span class="header-icon">#</span> Recent Leads</h3>
              <a href="view-leads.php" class="view-all-link">View All</a>
            </div>
            <div class="table-wrapper">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Lead Name</th>
                    <th>Phone</th>
                    <th>Course</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentLeads)) : ?>
                    <tr>
                      <td colspan="6" style="text-align:center; color: var(--clr-text-secondary);">No leads yet.</td>
                    </tr>
                  <?php else : ?>
                    <?php foreach ($recentLeads as $lead) : ?>
                      <tr>
                        <td>
                          <div class="user-cell">
                            <div class="user-cell-avatar">
                              <?php
                                $name = trim((string)($lead['full_name'] ?? ''));
                                $initials = '';
                                foreach (preg_split('/\s+/', $name) as $p) {
                                  if ($p !== '') { $initials .= strtoupper($p[0]); }
                                  if (strlen($initials) >= 2) break;
                                }
                                echo htmlspecialchars($initials ?: 'U');
                              ?>
                            </div>
                            <span><?php echo htmlspecialchars($lead['full_name'] ?? ''); ?></span>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($lead['phone_number'] ?? ''); ?></td>
                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($lead['course_applied'] ?? ''); ?></span></td>
                        <td><?php echo htmlspecialchars($lead['counselor_name'] ?? ''); ?></td>
                        <td>
                          <span class="status-cell">
                            <span class="status-dot blue"></span>
                            <?php echo htmlspecialchars($lead['counseling_status'] ?? ''); ?>
                          </span>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars($lead['created_at'] ?? ''); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script>
      window.dashboardMetrics = <?php echo json_encode($metrics); ?>;
    </script>
    <script src="js/app.js?v=4?v=3"></script>
    <script src="js/dashboard.js?v=4"></script>
  </body>
</html>
</html>

