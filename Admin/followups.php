<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
start_secure_session('CRM_ADMINSESSID');

$hasAdmin = !empty($_SESSION["is_admin"]) && !empty($_SESSION["admin_id"]);
if (!$hasAdmin) {
  header("Location: login.php");
  exit;
}

$fullName = $_SESSION["admin_username"] ?? "Admin";
$email = $_SESSION["admin_username"] ?? "admin";

$parts = preg_split("/\\s+/", trim($fullName));
$initials = "";
foreach ($parts as $p) {
  if ($p !== "") {
    $initials .= strtoupper($p[0]);
  }
  if (strlen($initials) >= 2) break;
}
if ($initials === "") $initials = "A";

require __DIR__ . "/../config/crm.php";
$rows = [];
$leadCount = 0;
try {
    $mysqli = db_connect();
    $res = $mysqli->query("SELECT COUNT(*) AS c FROM leads");
    if ($res) {
        $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
        $res->free();
    }
    $stmt = $mysqli->prepare("SELECT lead_id, full_name, phone_number, course_applied, counselor_name, follow_up_date, counseling_status FROM leads WHERE follow_up_date = CURDATE() ORDER BY follow_up_date DESC, lead_id DESC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
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
    <meta name="description" content="ICSS CRM — Today's follow-ups" />
    <title>Today's Follow-ups | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../Dashboard/css/global.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/navbar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/sidebar.css?v=2" />
    <link rel="stylesheet" href="../Dashboard/css/view-leads.css?v=2" />
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
                    <div class="leads-count">Showing <strong><?php echo count($rows); ?></strong> follow-ups</div>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)) : ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color: var(--clr-text-secondary);">No follow-ups scheduled for today.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($rows as $r) : ?>
                                    <tr
                                        data-id="<?php echo (int)($r['lead_id'] ?? 0); ?>"
                                        data-name="<?php echo htmlspecialchars($r['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-phone="<?php echo htmlspecialchars($r['phone_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-course="<?php echo htmlspecialchars($r['course_applied'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-counselor="<?php echo htmlspecialchars($r['counselor_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-followup="<?php echo htmlspecialchars($r['follow_up_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-status="<?php echo htmlspecialchars($r['counseling_status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                        <td><?php echo htmlspecialchars($r['full_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['phone_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['course_applied'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['counselor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['follow_up_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['counseling_status'] ?? ''); ?></td>
                                        <td>
                                            <div class="row-actions">
                                                <button class="row-action-btn view followup-view-btn" data-tooltip="View">👁</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <div class="table-footer-info" id="followupsCount"></div>
                    <div class="pagination" id="followupsPagination"></div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="followupModal">
        <div class="modal" style="max-width: 640px;">
            <div class="modal-header">
                <h3>Follow-up Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="lead-detail-grid" id="followupDetailGrid"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-sm" id="editFollowupBtn"><span class="btn-text">Edit Lead</span></button>
            </div>
        </div>
    </div>

    <script>window.CRM_ROLE = 'admin';</script>
    <script src="../Dashboard/js/app.js?v=4"></script>
    <script>
        const withRole = (url) => `${url}${url.includes('?') ? '&' : '?'}role=admin`;

        document.querySelectorAll('.followup-view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                if (!row) return;
                const detailGrid = document.getElementById('followupDetailGrid');
                if (!detailGrid) return;
                window.__activeLeadId = Number(row.dataset.id || 0);
                detailGrid.dataset.leadName = row.dataset.name || '';
                detailGrid.dataset.leadPhone = row.dataset.phone || '';
                detailGrid.dataset.leadCourse = row.dataset.course || '';
                detailGrid.dataset.leadCounselor = row.dataset.counselor || '';
                detailGrid.dataset.leadFollowup = row.dataset.followup || '';
                detailGrid.dataset.leadStatus = row.dataset.status || '';
                detailGrid.dataset.editing = 'false';
                const fields = [
                    { label: 'Name', value: row.dataset.name || '-', field: 'full_name', editable: true },
                    { label: 'Phone', value: row.dataset.phone || '-', field: 'phone_number', editable: true },
                    { label: 'Course', value: row.dataset.course || '-', field: 'course_applied', editable: true },
                    { label: 'Counselor', value: row.dataset.counselor || '-', field: 'counselor_name', editable: true },
                    { label: 'Follow-up Date', value: row.dataset.followup || '-', field: 'follow_up_date', editable: true },
                    { label: 'Status', value: row.dataset.status || '-', field: 'counseling_status', editable: true }
                ];
                detailGrid.innerHTML = fields.map(f => `
                    <div class="detail-item" data-field="${f.field}" data-editable="${f.editable ? '1' : '0'}">
                        <div class="detail-label">${f.label}</div>
                        <div class="detail-value">${f.value}</div>
                    </div>
                `).join('');
                if (window.App && typeof window.App.openModal === 'function') {
                    window.App.openModal('followupModal');
                } else {
                    document.getElementById('followupModal')?.classList.add('active');
                }
            });
        });

        async function saveFollowupEdits(updates) {
            const crmBase = window.CRM_BASE || '/CRM';
            const res = await fetch(withRole(crmBase + '/Dashboard/api/update_lead.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: window.__activeLeadId, updates })
            });
            const data = await res.json().catch(() => ({}));
            return res.ok && data.success;
        }

        const editBtn = document.getElementById('editFollowupBtn');
        if (editBtn) {
            editBtn.addEventListener('click', async () => {
                const grid = document.getElementById('followupDetailGrid');
                if (!grid) return;
                const isEditing = grid.dataset.editing === 'true';
                if (!isEditing) {
                    grid.dataset.editing = 'true';
                    grid.querySelectorAll('.detail-item').forEach(item => {
                        const editable = item.dataset.editable === '1';
                        if (!editable) return;
                        const field = item.dataset.field || '';
                        const valueEl = item.querySelector('.detail-value');
                        if (!valueEl) return;
                        const currentValue = valueEl.textContent.trim();
                        const input = document.createElement('input');
                        input.className = 'form-control';
                        if (field === 'follow_up_date') {
                            input.type = 'date';
                            input.value = currentValue === '-' ? '' : currentValue;
                        } else {
                            input.type = 'text';
                            input.value = currentValue === '-' ? '' : currentValue;
                        }
                        input.dataset.fieldKey = field;
                        valueEl.replaceWith(input);
                    });
                    editBtn.textContent = 'Save';
                } else {
                    const updates = {};
                    grid.querySelectorAll('input.form-control').forEach(input => {
                        const key = input.dataset.fieldKey || '';
                        if (key) updates[key] = input.value.trim() || '-';
                    });
                    const ok = await saveFollowupEdits(updates);
                    if (!ok) {
                        if (window.App?.showToast) {
                            App.showToast('error', 'Update Failed', 'Could not save lead. Please try again.');
                        }
                        return;
                    }
                    // update table row values
                    const row = document.querySelector(`tr[data-id="${window.__activeLeadId}"]`);
                    if (row) {
                        const map = {
                            full_name: 0,
                            phone_number: 1,
                            course_applied: 2,
                            counselor_name: 3,
                            follow_up_date: 4,
                            counseling_status: 5
                        };
                        Object.keys(updates).forEach(key => {
                            const val = updates[key];
                            if (row.dataset && row.dataset.hasOwnProperty(key)) {
                                row.dataset[key] = val;
                            }
                            if (key === 'full_name') row.dataset.name = val;
                            if (key === 'phone_number') row.dataset.phone = val;
                            if (key === 'course_applied') row.dataset.course = val;
                            if (key === 'counselor_name') row.dataset.counselor = val;
                            if (key === 'follow_up_date') row.dataset.followup = val;
                            if (key === 'counseling_status') row.dataset.status = val;
                            const idx = map[key];
                            if (row.children && idx !== undefined && row.children[idx]) {
                                row.children[idx].textContent = val;
                            }
                        });
                    }
                    grid.dataset.editing = 'false';
                    grid.querySelectorAll('input.form-control').forEach(input => {
                        let displayValue = input.value.trim() || '-';
                        const div = document.createElement('div');
                        div.className = 'detail-value';
                        div.textContent = displayValue;
                        input.replaceWith(div);
                    });
                    editBtn.textContent = 'Edit Lead';
                }
            });
        }
    </script>
    <script>
        (function () {
            const ITEMS_PER_PAGE = 5;
            const tbody = document.querySelector('.data-table tbody');
            if (!tbody) return;
            const allRows = Array.from(tbody.querySelectorAll('tr'));
            const emptyRow = allRows.find(r => r.querySelector('td[colspan]'));
            const dataRows = emptyRow ? [] : allRows;
            const countEl = document.getElementById('followupsCount');
            const paginationEl = document.getElementById('followupsPagination');
            let currentPage = 1;

            function render() {
                if (!dataRows.length) {
                    if (countEl) countEl.textContent = '';
                    if (paginationEl) paginationEl.innerHTML = '';
                    return;
                }
                const totalPages = Math.ceil(dataRows.length / ITEMS_PER_PAGE);
                if (currentPage < 1) currentPage = 1;
                if (currentPage > totalPages) currentPage = totalPages;

                const start = (currentPage - 1) * ITEMS_PER_PAGE;
                const end = start + ITEMS_PER_PAGE;
                dataRows.forEach((row, idx) => {
                    row.style.display = idx >= start && idx < end ? '' : 'none';
                });
                if (countEl) {
                    const shownStart = start + 1;
                    const shownEnd = Math.min(end, dataRows.length);
                    countEl.innerHTML = `Showing <strong>${shownStart}-${shownEnd}</strong> of <strong>${dataRows.length}</strong> follow-ups`;
                }
                if (!paginationEl) return;
                if (totalPages <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }
                let html = `<button class="pagination-btn" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">‹</button>`;
                for (let i = 1; i <= totalPages; i++) {
                    if (totalPages > 7) {
                        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                            html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                        } else if (i === currentPage - 2 || i === currentPage + 2) {
                            html += `<span class="pagination-btn" style="pointer-events:none;">…</span>`;
                        }
                    } else {
                        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
                    }
                }
                html += `<button class="pagination-btn" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">›</button>`;
                paginationEl.innerHTML = html;
            }

            if (paginationEl) {
                paginationEl.addEventListener('click', (e) => {
                    const btn = e.target.closest('button[data-page]');
                    if (!btn) return;
                    const p = parseInt(btn.getAttribute('data-page'), 10);
                    if (!Number.isNaN(p)) {
                        currentPage = p;
                        render();
                    }
                });
            }

            render();
        })();
    </script>
</body>

</html>






