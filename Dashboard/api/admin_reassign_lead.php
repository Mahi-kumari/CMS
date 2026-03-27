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

$leadId = (int)($_POST['lead_id'] ?? 0);
$newUserId = (int)($_POST['new_user_id'] ?? 0);
if ($leadId <= 0 || $newUserId <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid input.']);
  exit;
}

try {
  $mysqli = db_connect();

  $leadStmt = $mysqli->prepare("SELECT lead_id, user_id, counselor_name FROM leads WHERE lead_id = ? LIMIT 1");
  if (!$leadStmt) {
    throw new RuntimeException('Prepare failed');
  }
  $leadStmt->bind_param('i', $leadId);
  $leadStmt->execute();
  $leadStmt->bind_result($leadIdDb, $leadUserId, $leadCounselor);
  $hasLead = $leadStmt->fetch();
  $leadStmt->close();
  $lead = $hasLead ? ['lead_id' => $leadIdDb, 'user_id' => $leadUserId, 'counselor_name' => $leadCounselor] : null;

  if (!$lead) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Lead not found.']);
    exit;
  }

  $userStmt = $mysqli->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
  if (!$userStmt) {
    throw new RuntimeException('Prepare failed');
  }
  $userStmt->bind_param('i', $newUserId);
  $userStmt->execute();
  $userStmt->bind_result($userFullName);
  $hasUser = $userStmt->fetch();
  $userStmt->close();
  $user = $hasUser ? ['full_name' => $userFullName] : null;

  if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Target user not found.']);
    exit;
  }

  $newName = (string)($user['full_name'] ?? '');
  $upd = $mysqli->prepare("UPDATE leads SET user_id = ?, counselor_name = ? WHERE lead_id = ? LIMIT 1");
  if (!$upd) {
    throw new RuntimeException('Prepare failed');
  }
  $upd->bind_param('isi', $newUserId, $newName, $leadId);
  $upd->execute();
  $upd->close();

  $adminId = (int)$_SESSION['admin_id'];
  // Lead reassignment log
  $mysqli->query("CREATE TABLE IF NOT EXISTS lead_reassign_log (
      log_id INT AUTO_INCREMENT PRIMARY KEY,
      lead_id INT NOT NULL,
      old_user_id INT NULL,
      new_user_id INT NOT NULL,
      admin_id INT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )");
  if ($log = $mysqli->prepare("INSERT INTO lead_reassign_log (lead_id, old_user_id, new_user_id, admin_id) VALUES (?, ?, ?, ?)")) {
    $oldUid = isset($lead['user_id']) ? (int)$lead['user_id'] : null;
    $log->bind_param('iiii', $leadId, $oldUid, $newUserId, $adminId);
    $log->execute();
    $log->close();
  }

  $oldUserId = (string)($lead['user_id'] ?? '');
  $oldCounselor = (string)($lead['counselor_name'] ?? '');

  $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'lead', ?, ?, ?, ?)");
  if ($logStmt) {
    $field1 = 'user_id';
    $newUserIdStr = (string)$newUserId;
    $logStmt->bind_param('iisss', $adminId, $leadId, $field1, $oldUserId, $newUserIdStr);
    $logStmt->execute();
    $logStmt->close();
  }

  $logStmt2 = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'lead', ?, ?, ?, ?)");
  if ($logStmt2) {
    $field2 = 'counselor_name';
    $logStmt2->bind_param('iisss', $adminId, $leadId, $field2, $oldCounselor, $newName);
    $logStmt2->execute();
    $logStmt2->close();
  }

  echo json_encode(['success' => true, 'message' => 'Lead reassigned successfully.']);
} catch (Throwable $e) {
  http_response_code(500);
  $msg = 'Server error.';
  if (!empty($_SESSION['is_admin'])) {
    $msg = 'Server error: ' . $e->getMessage();
  }
  echo json_encode(['success' => false, 'message' => $msg]);
}

