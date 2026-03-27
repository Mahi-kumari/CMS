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

function safe($v): string {
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$filters = [
  'admin_id' => trim($_GET['admin_id'] ?? ''),
  'user_id' => trim($_GET['user_id'] ?? ''),
  'lead_id' => trim($_GET['lead_id'] ?? ''),
  'date_from' => trim($_GET['date_from'] ?? ''),
  'date_to' => trim($_GET['date_to'] ?? '')
];

$rows = [];
$admins = [];
$users = [];

try {
  $mysqli = db_connect();
  if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM leads")) {
    $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
    $res->free();
  }

  if ($res = $mysqli->query("SELECT admin_id, username FROM admins ORDER BY username ASC")) {
    while ($r = $res->fetch_assoc()) $admins[] = $r;
    $res->free();
  }
  if ($res = $mysqli->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC")) {
    while ($r = $res->fetch_assoc()) $users[] = $r;
    $res->free();
  }

  $where = [];
  $params = [];
  $types = '';

  if ($filters['admin_id'] !== '') {
    if (ctype_digit($filters['admin_id'])) {
      $where[] = "l.admin_id = ?";
      $types .= 'i';
      $params[] = (int)$filters['admin_id'];
    } else {
      $where[] = "a.username LIKE ?";
      $types .= 's';
      $params[] = '%' . $filters['admin_id'] . '%';
    }
  }
  if ($filters['user_id'] !== '') {
    if (ctype_digit($filters['user_id'])) {
      $where[] = "(l.old_user_id = ? OR l.new_user_id = ?)";
      $types .= 'ii';
      $params[] = (int)$filters['user_id'];
      $params[] = (int)$filters['user_id'];
    } else {
      $where[] = "(u1.full_name LIKE ? OR u2.full_name LIKE ?)";
      $types .= 'ss';
      $params[] = '%' . $filters['user_id'] . '%';
      $params[] = '%' . $filters['user_id'] . '%';
    }
  }
  if ($filters['lead_id'] !== '') {
    $where[] = "l.lead_id = ?";
    $types .= 'i';
    $params[] = (int)$filters['lead_id'];
  }
  if ($filters['date_from'] !== '') {
    $where[] = "DATE(l.created_at) >= ?";
    $types .= 's';
    $params[] = $filters['date_from'];
  }
  if ($filters['date_to'] !== '') {
    $where[] = "DATE(l.created_at) <= ?";
    $types .= 's';
    $params[] = $filters['date_to'];
  }

  $sql = "SELECT l.log_id, l.lead_id, l.old_user_id, l.new_user_id, l.admin_id, l.created_at,
                 a.username AS admin_name,
                 u1.full_name AS old_user_name,
                 u2.full_name AS new_user_name
          FROM lead_reassign_log l
          LEFT JOIN admins a ON a.admin_id = l.admin_id
          LEFT JOIN users u1 ON u1.user_id = l.old_user_id
          LEFT JOIN users u2 ON u2.user_id = l.new_user_id";
  if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
  }
  $sql .= " ORDER BY l.created_at DESC, l.log_id DESC";

  $stmt = $mysqli->prepare($sql);
  if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
  }
  if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($logId, $leadId, $oldUserId, $newUserId, $adminId, $createdAt, $adminName, $oldUserName, $newUserName);
    while ($stmt->fetch()) {
      $rows[] = [
        'log_id' => $logId,
        'lead_id' => $leadId,
        'old_user_id' => $oldUserId,
        'new_user_id' => $newUserId,
        'admin_id' => $adminId,
        'created_at' => $createdAt,
        'admin_name' => $adminName,
        'old_user_name' => $oldUserName,
        'new_user_name' => $newUserName
      ];
    }
    $stmt->close();
  } else {
    if ($res = $mysqli->query($sql)) {
      while ($r = $res->fetch_assoc()) $rows[] = $r;
      $res->free();
    }
  }
  $mysqli->close();
} catch (Throwable $e) {
  $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM — Lead ownership audit." />
    <title>Lead Ownership Audit | ICSS CRM</title>
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
              <span class="sidebar-link-text">Today''s Follow-up</span>
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
            <a href="lead-ownership.php" class="sidebar-link active" data-tooltip="Lead Ownership Audit">
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
          <h1>Lead Ownership Audit</h1>
          <div class="breadcrumb">
            <a href="index.php">Dashboard</a>
            <span class="separator">/</span>
            <span>Lead Ownership</span>
          </div>
        </div>

        <form class="leads-toolbar" method="get" action="lead-ownership.php">
          <div class="toolbar-left">
            <div class="filter-group">
              <select class="filter-select" name="admin_id">
                <option value="">All Admins</option>
                <?php foreach ($admins as $a) : ?>
                  <option value="<?php echo (int)$a['admin_id']; ?>" <?php echo ($filters['admin_id'] == $a['admin_id']) ? 'selected' : ''; ?>>
                    <?php echo safe($a['username']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select class="filter-select" name="user_id">
                <option value="">All Users</option>
                <?php foreach ($users as $u) : ?>
                  <option value="<?php echo (int)$u['user_id']; ?>" <?php echo ($filters['user_id'] == $u['user_id']) ? 'selected' : ''; ?>>
                    <?php echo safe($u['full_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input class="form-control" type="number" name="lead_id" placeholder="Lead ID" value="<?php echo safe($filters['lead_id']); ?>" />
              <input class="form-control" type="date" name="date_from" value="<?php echo safe($filters['date_from']); ?>" />
              <input class="form-control" type="date" name="date_to" value="<?php echo safe($filters['date_to']); ?>" />
            </div>
          </div>
          <div class="toolbar-right">
            <button class="btn btn-secondary" type="submit">Apply</button>
            <a class="btn btn-secondary" href="lead-ownership.php">Clear</a>
          </div>
        </form>

        <div class="leads-table-container">
          <div class="leads-table-header">
            <div class="leads-count">Showing <strong><?php echo count($rows); ?></strong> entries</div>
          </div>
          <div class="table-wrapper">
            <table class="data-table" id="leadOwnershipTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Lead</th>
                  <th>Old Owner</th>
                  <th>New Owner</th>
                  <th>Admin</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)) : ?>
                  <tr>
                    <td colspan="6" style="text-align:center; color: var(--clr-text-secondary);">No reassignments found.</td>
                  </tr>
                <?php else : ?>
                  <?php $rowNum = 1; ?>
                  <?php foreach ($rows as $r) : ?>
                    <tr>
                      <td><?php echo $rowNum++; ?></td>
                      <td>#<?php echo (int)$r['lead_id']; ?></td>
                      <td><?php echo safe($r['old_user_name'] ?? '—'); ?></td>
                      <td><?php echo safe($r['new_user_name'] ?? '—'); ?></td>
                      <td><?php echo safe($r['admin_name'] ?? 'Admin'); ?></td>
                      <td><?php echo safe($r['created_at']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="table-footer">
            <div class="table-footer-info" id="leadOwnershipCount2"></div>
            <div class="pagination" id="leadOwnershipPagination"></div>
          </div>
        </div>
      </main>
    </div>

    <script>window.CRM_ROLE = 'admin';</script>
    <script src="../Dashboard/js/app.js?v=4"></script>
    <script src="../Dashboard/js/admin-data.js?v=1"></script>
  </body>
</html>




