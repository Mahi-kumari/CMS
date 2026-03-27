<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
start_secure_session('CRM_ADMINSESSID');

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['is_admin']) || empty($_SESSION['admin_id'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'message' => 'Admin access required.']);
  exit;
}

require __DIR__ . '/../../config/crm.php';

try {
  $mysqli = db_connect();

  // Ensure columns exist: status, last_login
  $cols = [];
  if ($res = $mysqli->query("SHOW COLUMNS FROM users")) {
    while ($row = $res->fetch_assoc()) {
      $cols[] = $row['Field'] ?? '';
    }
    $res->free();
  }

  if (!in_array('status', $cols, true)) {
    $mysqli->query("ALTER TABLE users ADD COLUMN status VARCHAR(10) DEFAULT 'active'");
    $cols[] = 'status';
  }
  if (!in_array('last_login', $cols, true)) {
    $mysqli->query("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
    $cols[] = 'last_login';
  }

  $sql = "SELECT u.user_id, u.full_name, u.email, u.phone,
                 " . (in_array('status', $cols, true) ? "u.status" : "'active' AS status") . ",
                 " . (in_array('last_login', $cols, true) ? "COALESCE(u.last_login, u.created_at) AS last_login" : "u.created_at AS last_login") . ",
                 COUNT(l.lead_id) AS total_leads
          FROM users u
          LEFT JOIN leads l ON l.user_id = u.user_id
          GROUP BY u.user_id
          ORDER BY u.created_at DESC, u.user_id DESC";

  $rows = [];
  if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
      $rows[] = $row;
    }
    $res->free();
  }

  $mysqli->close();
  echo json_encode(['success' => true, 'users' => $rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}

