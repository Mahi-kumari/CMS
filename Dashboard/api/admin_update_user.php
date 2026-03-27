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

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid user.']);
  exit;
}

$fields = [
  'full_name' => isset($_POST['full_name']) ? trim((string)$_POST['full_name']) : null,
  'email' => isset($_POST['email']) ? trim((string)$_POST['email']) : null,
  'phone' => isset($_POST['phone']) ? trim((string)$_POST['phone']) : null,
  'email_notifications' => isset($_POST['email_notifications']) ? (int)$_POST['email_notifications'] : null,
  'sound_alerts' => isset($_POST['sound_alerts']) ? (int)$_POST['sound_alerts'] : null,
];

$newPassword = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

try {
  $mysqli = db_connect();

  $stmt = $mysqli->prepare("SELECT full_name, email, phone, email_notifications, sound_alerts FROM users WHERE user_id = ? LIMIT 1");
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $current = $res->fetch_assoc();
  $stmt->close();

  if (!$current) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
  }

  $updates = [];
  $params = [];
  $types = '';
  $changed = [];

  foreach ($fields as $key => $val) {
    if ($val === null) continue;
    $old = $current[$key] ?? null;
    if ((string)$old !== (string)$val) {
      $updates[] = "{$key} = ?";
      $params[] = $val;
      $types .= is_int($val) ? 'i' : 's';
      $changed[$key] = [$old, $val];
    }
  }

  if ($newPassword !== '') {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updates[] = "password = ?";
    $params[] = $hash;
    $types .= 's';
    $changed['password'] = ['[hidden]', '[updated]'];
  }

  if (!$updates) {
    echo json_encode(['success' => true, 'message' => 'No changes detected.']);
    exit;
  }

  $types .= 'i';
  $params[] = $userId;
  $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ? LIMIT 1";
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $stmt->close();

  $adminId = (int)$_SESSION['admin_id'];
  $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'user', ?, ?, ?, ?)");
  if ($logStmt) {
    foreach ($changed as $field => $pair) {
      $oldVal = (string)($pair[0] ?? '');
      $newVal = (string)($pair[1] ?? '');
      $logStmt->bind_param('iisss', $adminId, $userId, $field, $oldVal, $newVal);
      $logStmt->execute();
    }
    $logStmt->close();
  }

  echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}

