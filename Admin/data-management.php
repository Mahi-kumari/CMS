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
$phone = "";
$usersList = [];
$leadsList = [];
$auditLogs = [];
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
  if ($res = $mysqli->query("SELECT user_id, full_name, email, phone, email_notifications, sound_alerts FROM users ORDER BY full_name ASC")) {
    while ($row = $res->fetch_assoc()) {
      $usersList[] = $row;
    }
    $res->free();
  }
  if ($res = $mysqli->query("SELECT lead_id, full_name, phone_number FROM leads ORDER BY created_at DESC, lead_id DESC LIMIT 200")) {
    while ($row = $res->fetch_assoc()) {
      $leadsList[] = $row;
    }
    $res->free();
  }
  if ($res = $mysqli->query("SELECT l.log_id, l.admin_id, a.username, l.action_type, l.target_type, l.target_id, l.field_name, l.old_value, l.new_value, l.created_at FROM admin_audit_log l LEFT JOIN admins a ON a.admin_id = l.admin_id ORDER BY l.created_at DESC, l.log_id DESC LIMIT 200")) {
    while ($row = $res->fetch_assoc()) {
      $auditLogs[] = $row;
    }
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
    <meta name="description" content="ICSS CRM — Admin data management." />
    <title>Data Management | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Dashboard/css/global.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/navbar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/sidebar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/view-leads.css?v=5" />
    <link rel="stylesheet" href="../Dashboard/css/settings.css?v=2" />
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
              <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">👤</span> My Profile</a>
              <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">⚙️</span> Settings</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item danger" href="logout.php"><span class="dropdown-icon">🚪</span> Logout</a>
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
            <a href="data-management.php" class="sidebar-link active" data-tooltip="Data Management">
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
          <h1>Data Management</h1>
          <div class="breadcrumb">
            <a href="index.php">Dashboard</a>
            <span class="separator">/</span>
            <span>Data Management</span>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-card-header">
            <h3>Global Profile Editing</h3>
            <p>Modify any user profile across the CRM.</p>
          </div>
          <div class="settings-card-body">
            <div class="form-grid" style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 16px 24px;">
              <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="adminUserSelect">Select User</label>
                <select class="form-control" id="adminUserSelect">
                  <option value="">Choose a user</option>
                  <?php foreach ($usersList as $u) : ?>
                    <option value="<?php echo (int)$u['user_id']; ?>"
                      data-full-name="<?php echo htmlspecialchars($u['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-email="<?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-phone="<?php echo htmlspecialchars($u['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                      data-email-notif="<?php echo (int)($u['email_notifications'] ?? 0); ?>"
                      data-sound-alert="<?php echo (int)($u['sound_alerts'] ?? 0); ?>">
                      <?php echo htmlspecialchars($u['full_name'] ?? 'User'); ?> (<?php echo htmlspecialchars($u['email'] ?? ''); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="adminUserName">Full Name</label>
                <input type="text" class="form-control" id="adminUserName" placeholder="Enter full name" />
              </div>
              <div class="form-group">
                <label class="form-label" for="adminUserEmail">Email</label>
                <input type="email" class="form-control" id="adminUserEmail" placeholder="Enter email" />
              </div>
              <div class="form-group">
                <label class="form-label" for="adminUserPhone">Phone</label>
                <input type="text" class="form-control" id="adminUserPhone" placeholder="Enter phone" />
              </div>
              <div class="form-group">
                <label class="form-label" for="adminUserPassword">Reset Password (optional)</label>
                <input type="password" class="form-control" id="adminUserPassword" placeholder="Set a new password" />
              </div>
            </div>

            <div class="settings-footer" style="display:flex; justify-content:flex-end; margin-top:16px;">
              <button class="btn btn-primary" id="adminUserSaveBtn">Save User Changes</button>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-card-header">
            <h3>Profile Referral / Reassignment</h3>
            <p>Reassign a lead to a different user.</p>
          </div>
          <div class="settings-card-body">
            <div class="form-grid" style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 16px 24px;">
              <div class="form-group">
                <label class="form-label" for="adminLeadSelect">Select Lead</label>
                <select class="form-control" id="adminLeadSelect">
                  <option value="">Choose a lead</option>
                  <?php foreach ($leadsList as $l) : ?>
                    <option value="<?php echo (int)$l['lead_id']; ?>">
                      #<?php echo (int)$l['lead_id']; ?> - <?php echo htmlspecialchars($l['full_name'] ?? 'Lead'); ?> (<?php echo htmlspecialchars($l['phone_number'] ?? ''); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="adminAssigneeSelect">Reassign To</label>
                <select class="form-control" id="adminAssigneeSelect">
                  <option value="">Choose a user</option>
                  <?php foreach ($usersList as $u) : ?>
                    <option value="<?php echo (int)$u['user_id']; ?>">
                      <?php echo htmlspecialchars($u['full_name'] ?? 'User'); ?> (<?php echo htmlspecialchars($u['email'] ?? ''); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="settings-footer" style="display:flex; justify-content:flex-end; margin-top:16px;">
              <button class="btn btn-primary" id="adminReassignBtn">Reassign Lead</button>
            </div>
          </div>
        </div>

        <div class="settings-card">
          <div class="settings-card-header">
            <h3>Data Export (.csv)</h3>
            <p>Download complete data sets for reporting.</p>
          </div>
          <div class="settings-card-body" style="display:flex; gap:12px; flex-wrap:wrap;">
            <button class="btn btn-secondary" id="exportUsersBtn">Export Users CSV</button>
            <button class="btn btn-secondary" id="exportLeadsBtn">Export Leads CSV</button>
          </div>
        </div>

        <div class="settings-card" style="width: 100%; max-width: 1200px;">
          <div class="settings-card-header">
            <h3>Audit Log</h3>
            <p>All admin changes are recorded here.</p>
          </div>
          <div class="settings-card-body">
            <div class="leads-toolbar" style="margin-bottom: 12px;">
              <div class="toolbar-left">
                <div class="toolbar-search">
                  <span class="search-icon">🔍</span>
                  <input type="text" class="search-input" id="auditSearch" placeholder="Search admin, target, field, value…">
                </div>
              </div>
            </div>
            <div class="leads-table-container">
              <div class="leads-table-header">
                <div class="leads-count" id="auditCount">Showing <strong><?php echo count($auditLogs); ?></strong> entries</div>
              </div>
              <div class="table-wrapper" style="overflow:auto;">
                <table class="data-table" id="auditLogTable">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Admin</th>
                      <th>Action</th>
                      <th>Target</th>
                      <th>Field</th>
                      <th>Old Value</th>
                      <th>New Value</th>
                      <th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($auditLogs)) : ?>
                      <tr>
                        <td colspan="8" style="text-align:center; color: var(--clr-text-secondary);">No audit entries yet.</td>
                      </tr>
                    <?php else : ?>
                      <?php $displayId = 1; ?>
                      <?php foreach ($auditLogs as $log) : ?>
                        <tr
                          data-admin="<?php echo htmlspecialchars($log['username'] ?? ('Admin #' . (int)($log['admin_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"
                          data-action="<?php echo htmlspecialchars($log['action_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                          data-target="<?php echo htmlspecialchars($log['target_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                          data-field="<?php echo htmlspecialchars($log['field_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                          data-old="<?php echo htmlspecialchars((string)($log['old_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                          data-new="<?php echo htmlspecialchars((string)($log['new_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                          <td><?php echo (int)$displayId; ?></td>
                          <td><?php echo htmlspecialchars($log['username'] ?? ('Admin #' . (int)($log['admin_id'] ?? 0))); ?></td>
                          <td><?php echo htmlspecialchars($log['action_type'] ?? ''); ?></td>
                          <td><?php echo htmlspecialchars(($log['target_type'] ?? '') . ' #' . ($log['target_id'] ?? '')); ?></td>
                          <td><?php echo htmlspecialchars($log['field_name'] ?? '-'); ?></td>
                          <td><?php echo htmlspecialchars((string)($log['old_value'] ?? '-')); ?></td>
                          <td><?php echo htmlspecialchars((string)($log['new_value'] ?? '-')); ?></td>
                          <td><?php echo htmlspecialchars($log['created_at'] ?? ''); ?></td>
                        </tr>
                        <?php $displayId++; ?>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              <div class="table-footer-info" id="auditPagination"></div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <script>window.CRM_ROLE = 'admin';</script>
    <script src="../Dashboard/js/app.js?v=4"></script>
    <script src="../Dashboard/js/admin-data.js?v=1"></script>
  </body>
</html>




