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

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0 || ($action !== 'toggle_status' && $action !== 'reset_password')) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid request.']);
  exit;
}

try {
  $mysqli = db_connect();

  // Ensure status column exists for toggle
  if ($action === 'toggle_status') {
    $cols = [];
    if ($res = $mysqli->query("SHOW COLUMNS FROM users")) {
      while ($row = $res->fetch_assoc()) {
        $cols[] = $row['Field'] ?? '';
      }
      $res->free();
    }
    if (!in_array('status', $cols, true)) {
      $mysqli->query("ALTER TABLE users ADD COLUMN status VARCHAR(10) DEFAULT 'active'");
    }
  }

  if ($action === 'toggle_status') {
    $current = 'active';
    if ($stmt = $mysqli->prepare("SELECT status FROM users WHERE user_id = ? LIMIT 1")) {
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $stmt->bind_result($status);
      if ($stmt->fetch()) {
        $current = $status ?: 'active';
      }
      $stmt->close();
    }
    $newStatus = ($current === 'active') ? 'inactive' : 'active';
    $stmt = $mysqli->prepare("UPDATE users SET status = ? WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('si', $newStatus, $userId);
    $stmt->execute();
    $stmt->close();

    $adminId = (int)$_SESSION['admin_id'];
    $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'user', ?, 'status', ?, ?)");
    if ($logStmt) {
      $logStmt->bind_param('iiss', $adminId, $userId, $current, $newStatus);
      $logStmt->execute();
      $logStmt->close();
    }

    echo json_encode(['success' => true, 'status' => $newStatus]);
    exit;
  }

  // reset_password
  $tempPassword = $_POST['new_password'] ?? '';
  if ($tempPassword === '') {
    $tempPassword = substr(bin2hex(random_bytes(4)), 0, 8);
  }
  $hash = password_hash($tempPassword, PASSWORD_DEFAULT);
  $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ? LIMIT 1");
  $stmt->bind_param('si', $hash, $userId);
  $stmt->execute();
  $stmt->close();

  $adminId = (int)$_SESSION['admin_id'];
  $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'user', ?, 'password', '[hidden]', '[reset]')");
  if ($logStmt) {
    $logStmt->bind_param('ii', $adminId, $userId);
    $logStmt->execute();
    $logStmt->close();
  }

  echo json_encode(['success' => true, 'password' => $tempPassword]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}

