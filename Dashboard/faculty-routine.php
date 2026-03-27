<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/session.php";
start_secure_session('CRM_USERSESSID');
require __DIR__ . "/../config/crm.php";

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

$selectedCourse = trim($_GET["course"] ?? "");
$trainerQuery = trim($_GET["trainer"] ?? "");
$studentQuery = trim($_GET["student"] ?? "");
$courses = [];
$rows = [];
$leadCount = 0;
try {
    $mysqli = db_connect();

    if ($res = $mysqli->query("SELECT COUNT(*) AS c FROM leads")) {
        $leadCount = (int)($res->fetch_assoc()["c"] ?? 0);
        $res->free();
    }

    $courseSql = "SELECT DISTINCT course_applied FROM leads WHERE course_applied IS NOT NULL AND course_applied <> '' ORDER BY course_applied";
    if ($res = $mysqli->query($courseSql)) {
        while ($row = $res->fetch_assoc()) {
            $courses[] = $row["course_applied"];
        }
        $res->free();
    }

    $sql = "SELECT
                course_applied,
                center_location,
                training_mode,
                trainer_assigned,
                batch_start_date,
                batch_end_date,
                preferred_time_slot,
                batch_assigned_time,
                full_name,
                phone_number,
                email_address,
                total_due_amount,
                payment_date,
                payment_mode,
                receipt_amount_paid,
                token_amount,
                final_fee,
                course_fee
            FROM leads";

    $filters = [];
    $params = [];
    $types = "";

    if ($selectedCourse !== "") {
        $filters[] = "course_applied = ?";
        $params[] = $selectedCourse;
        $types .= "s";
    }

    if ($trainerQuery !== "") {
        $filters[] = "trainer_assigned LIKE ?";
        $params[] = "%" . $trainerQuery . "%";
        $types .= "s";
    }

    if ($studentQuery !== "") {
        $filters[] = "full_name LIKE ?";
        $params[] = "%" . $studentQuery . "%";
        $types .= "s";
    }

    if (!empty($filters)) {
        $sql .= " WHERE " . implode(" AND ", $filters);
    }

    $sql .= " ORDER BY batch_start_date DESC, course_applied ASC, full_name ASC";

    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
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
    $courses = [];
}

function fmt_date(?string $value): string {
    if (!$value) return '-';
    try {
        $date = new DateTime($value);
        return $date->format('d M Y');
    } catch (Throwable $e) {
        return $value;
    }
}

function payment_status(array $row): string {
    $due = $row['total_due_amount'] ?? null;
    if ($due !== null && is_numeric($due) && (float)$due > 0) {
        return 'Due';
    }
    $checks = [
        $row['payment_date'] ?? null,
        $row['payment_mode'] ?? null,
        $row['receipt_amount_paid'] ?? null,
        $row['token_amount'] ?? null,
        $row['final_fee'] ?? null,
        $row['course_fee'] ?? null,
    ];
    foreach ($checks as $val) {
        if ($val !== null && $val !== '') {
            return 'Paid';
        }
    }
    return 'Pending';
}

function safe($value): string {
    return htmlspecialchars((string)($value ?? '-'), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ICSS CRM â€” Faculty routine and student list." />
    <title>Faculty Routine | ICSS CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css" />
    <link rel="stylesheet" href="css/navbar.css" />
    <link rel="stylesheet" href="css/sidebar.css" />
    <link rel="stylesheet" href="css/view-leads.css?v=5">
</head>

<body>
    <nav class="navbar" id="navbar">
        <div class="navbar-brand">
            <button class="navbar-hamburger show-mobile" id="hamburgerBtn" aria-label="Toggle menu">â˜°</button>
            <div class="navbar-logo">IC</div>
            <div class="navbar-brand-text hide-mobile">
                <span class="navbar-brand-name">ICSS CRM</span>
                <span class="navbar-brand-sub">Management Suite</span>
            </div>
        </div>
        <div class="navbar-actions">
            <button class="navbar-action-btn" data-tooltip="Notifications" id="notificationBtn">&#x1F514;<span class="notification-dot"></span></button>
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
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">ðŸ‘¤</span> My Profile</a>
                        <a class="dropdown-item" href="settings.php"><span class="dropdown-icon">âš™ï¸</span> Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item danger" href="logout.php"><span class="dropdown-icon">ðŸšª</span> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
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
                    </a>
                    <a href="followups.php" class="sidebar-link" data-tooltip="Today's Follow-up">
                        <span class="sidebar-icon">&#x1F4C5;</span>
                        <span class="sidebar-link-text">Today's Follow-up</span>
                    </a>
                    <a href="faculty-routine.php" class="sidebar-link active" data-tooltip="Faculty Routine">
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
            <div class="page-header">
                <h1>Faculty Routine</h1>
                <div class="breadcrumb">
                    <a href="index.php">Dashboard</a>
                    <span class="separator">/</span>
                    <span>Faculty Routine</span>
                </div>
            </div>

            <form class="leads-toolbar" method="get" action="faculty-routine.php">
                <div class="toolbar-left">
                    <div class="toolbar-search">
                        <span class="search-icon">&#x1F50D;</span>
                        <input type="text" id="trainerSearch" name="trainer" placeholder="Search trainer name..." value="<?php echo safe($trainerQuery); ?>" />
                    </div>
                </div>
                <div class="toolbar-right">
                    <div class="filter-group">
                        <input type="text" class="filter-input" id="studentSearch" name="student" placeholder="Search student name..." value="<?php echo safe($studentQuery); ?>" />
                        <select class="filter-select" id="filterCourse" name="course">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $opt) : ?>
                                <option value="<?php echo safe($opt); ?>" <?php echo $selectedCourse === $opt ? "selected" : ""; ?>>
                                    <?php echo safe($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <div class="leads-table-container">
                <div class="leads-table-header">
                    <div class="leads-count">Showing <strong><?php echo count($rows); ?></strong> entries</div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Location</th>
                                <th>Training Mode</th>
                                <th>Trainer Details</th>
                                <th>Batch Start Date</th>
                                <th>Estimated End</th>
                                <th>Days</th>
                                <th>Batch Time</th>
                                <th>Student Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rows)) : ?>
                                <tr>
                                    <td colspan="10" style="text-align:center;">No data found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($rows as $r) : ?>
                                    <tr
                                        data-course="<?php echo safe($r['course_applied']); ?>"
                                        data-location="<?php echo safe($r['center_location']); ?>"
                                        data-training-mode="<?php echo safe($r['training_mode']); ?>"
                                        data-trainer-name="<?php echo safe($r['trainer_assigned']); ?>"
                                        data-trainer-email="-"
                                        data-batch-start="<?php echo fmt_date($r['batch_start_date']); ?>"
                                        data-batch-end="<?php echo fmt_date($r['batch_end_date']); ?>"
                                        data-batch-time="<?php echo safe($r['batch_assigned_time'] ?: $r['preferred_time_slot']); ?>"
                                        data-student-name="<?php echo safe($r['full_name']); ?>"
                                        data-student-phone="<?php echo safe($r['phone_number']); ?>"
                                        data-student-email="<?php echo safe($r['email_address']); ?>"
                                        data-student-mode="<?php echo safe($r['training_mode']); ?>"
                                        data-student-status="<?php echo payment_status($r); ?>"
                                    >
                                        <td><?php echo safe($r['course_applied']); ?></td>
                                        <td><?php echo safe($r['center_location']); ?></td>
                                        <td><?php echo safe($r['training_mode']); ?></td>
                                        <td>
                                            <div><strong>Name:</strong> <?php echo safe($r['trainer_assigned']); ?></div>
                                            <div><strong>Email:</strong> -</div>
                                        </td>
                                        <td><?php echo fmt_date($r['batch_start_date']); ?></td>
                                        <td><?php echo fmt_date($r['batch_end_date']); ?></td>
                                        <td>-</td>
                                        <td><?php echo safe($r['batch_assigned_time'] ?: $r['preferred_time_slot']); ?></td>
                                        <td class="student-col">
                                            <div><strong>Name:</strong> <?php echo safe($r['full_name']); ?></div>
                                            <div><strong>Phone:</strong> <?php echo safe($r['phone_number']); ?></div>
                                            <div><strong>Email:</strong> <?php echo safe($r['email_address']); ?></div>
                                            <div><strong>Mode:</strong> <?php echo safe($r['training_mode']); ?></div>
                                            <div><strong>Status:</strong> <?php echo payment_status($r); ?></div>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <button class="row-action-btn view" data-tooltip="View">👁</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <div class="table-footer-info" id="routineCount"></div>
                    <div class="pagination" id="routinePagination"></div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="routineViewModal">
        <div class="modal" style="max-width: 640px;">
            <div class="modal-header">
                <h3>Routine Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="lead-detail-grid" id="routineDetailGrid"></div>
            </div>
        </div>
    </div>

    <script src="js/app.js?v=4"></script>
    <script>
    (function () {
        const ITEMS_PER_PAGE = 5;
        const tbody = document.querySelector('.data-table tbody');
        const allRows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
        const countEl = document.getElementById('routineCount');
        const paginationEl = document.getElementById('routinePagination');
        const courseSelect = document.getElementById('filterCourse');
        const searchInput = document.getElementById('trainerSearch');
        let filteredRows = [...allRows];

        function renderPage(page) {
            const total = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
            const current = Math.min(Math.max(1, page), totalPages);

            allRows.forEach(row => {
                row.style.display = 'none';
            });

            const startIdx = (current - 1) * ITEMS_PER_PAGE;
            const endIdx = startIdx + ITEMS_PER_PAGE;
            filteredRows.forEach((row, idx) => {
                row.style.display = idx >= startIdx && idx < endIdx ? '' : 'none';
            });

            if (countEl) {
                const startNum = total === 0 ? 0 : startIdx + 1;
                const endNum = Math.min(endIdx, total);
                countEl.textContent = `Showing ${startNum}-${endNum} of ${total} entries`;
            }

            if (paginationEl) {
                if (totalPages <= 1) {
                    paginationEl.innerHTML = '';
                    return;
                }
                let html = '';
                html += `<button class="pagination-btn" ${current === 1 ? 'disabled' : ''} data-page="${current - 1}">‹</button>`;
                for (let i = 1; i <= totalPages; i++) {
                    if (totalPages > 7) {
                        if (i === 1 || i === totalPages || (i >= current - 1 && i <= current + 1)) {
                            html += `<button class="pagination-btn ${i === current ? 'active' : ''}" data-page="${i}">${i}</button>`;
                        } else if (i === current - 2 || i === current + 2) {
                            html += `<span class="pagination-btn" style="pointer-events:none;">&hellip;</span>`;
                        }
                    } else {
                        html += `<button class="pagination-btn ${i === current ? 'active' : ''}" data-page="${i}">${i}</button>`;
                    }
                }
                html += `<button class="pagination-btn" ${current === totalPages ? 'disabled' : ''} data-page="${current + 1}">›</button>`;
                paginationEl.innerHTML = html;

                paginationEl.querySelectorAll('button[data-page]').forEach(btn => {
                    btn.addEventListener('click', () => renderPage(parseInt(btn.dataset.page, 10)));
                });
            }
        }

        function applyFilters() {
            filteredRows = [...allRows];
            renderPage(1);
        }

        if (courseSelect) {
            courseSelect.addEventListener('change', () => {
                courseSelect.form?.submit();
            });
        }

        const studentInput = document.getElementById('studentSearch');

        if (searchInput) {
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchInput.form?.submit();
                }
            });
        }

        if (studentInput) {
            studentInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    studentInput.form?.submit();
                }
            });
        }

        function openRoutineModal(row) {
            if (!row) return;
            const grid = document.getElementById('routineDetailGrid');
            if (!grid) return;
            const get = (key) => row.dataset[key] || '-';
            const fields = [
                { label: 'Course', value: get('course') },
                { label: 'Location', value: get('location') },
                { label: 'Training Mode', value: get('trainingMode') },
                { label: 'Trainer Name', value: get('trainerName') },
                { label: 'Trainer Email', value: get('trainerEmail') },
                { label: 'Batch Start', value: get('batchStart') },
                { label: 'Batch End', value: get('batchEnd') },
                { label: 'Batch Time', value: get('batchTime') },
                { label: 'Student Name', value: get('studentName') },
                { label: 'Student Phone', value: get('studentPhone') },
                { label: 'Student Email', value: get('studentEmail') },
                { label: 'Preferred Mode', value: get('studentMode') },
                { label: 'Payment Status', value: get('studentStatus') }
            ];
            grid.innerHTML = fields.map(f => `
                <div class="detail-item">
                    <div class="detail-label">${f.label}</div>
                    <div class="detail-value">${f.value || '-'}</div>
                </div>
            `).join('');
            const title = document.querySelector('#routineViewModal .modal-header h3');
            if (title) title.textContent = `Routine: ${get('course')}`;
            if (window.App && typeof window.App.openModal === 'function') {
                window.App.openModal('routineViewModal');
            }
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.row-action-btn.view');
            if (!btn) return;
            const row = btn.closest('tr');
            openRoutineModal(row);
        });

        applyFilters();
    })();
    </script>
</body>

</html>
