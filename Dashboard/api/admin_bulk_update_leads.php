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

$idsRaw = $_POST['lead_ids'] ?? '';
$leadIds = [];
foreach (explode(',', (string)$idsRaw) as $id) {
  $id = trim($id);
  if ($id !== '' && ctype_digit($id)) {
    $leadIds[] = (int)$id;
  }
}
if (empty($leadIds)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'No leads selected.']);
  exit;
}

$status = trim((string)($_POST['counseling_status'] ?? ''));
$counselor = trim((string)($_POST['counselor_name'] ?? ''));
$followup = trim((string)($_POST['follow_up_date'] ?? ''));

$updates = [];
$params = [];
$types = '';

if ($status !== '') {
  $updates[] = 'counseling_status = ?';
  $params[] = $status;
  $types .= 's';
}
if ($counselor !== '') {
  $updates[] = 'counselor_name = ?';
  $params[] = $counselor;
  $types .= 's';
}
if ($followup !== '') {
  $updates[] = 'follow_up_date = ?';
  $params[] = $followup;
  $types .= 's';
}

if (empty($updates)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'No changes provided.']);
  exit;
}

try {
  $mysqli = db_connect();

  // fetch current values for audit
  $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
  $sel = $mysqli->prepare("SELECT lead_id, counseling_status, counselor_name, follow_up_date FROM leads WHERE lead_id IN ($placeholders)");
  if ($sel) {
    $sel->bind_param(str_repeat('i', count($leadIds)), ...$leadIds);
    $sel->execute();
    $sel->bind_result($leadIdDb, $statusDb, $counselorDb, $followupDb);
    $current = [];
    while ($sel->fetch()) {
      $current[(int)$leadIdDb] = [
        'lead_id' => $leadIdDb,
        'counseling_status' => $statusDb,
        'counselor_name' => $counselorDb,
        'follow_up_date' => $followupDb
      ];
    }
    $sel->close();
  } else {
    $current = [];
  }

  $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE lead_id IN ($placeholders)";
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException('Prepare failed');
  }
  $bindParams = array_merge($params, $leadIds);
  $stmt->bind_param($types . str_repeat('i', count($leadIds)), ...$bindParams);
  $stmt->execute();
  $stmt->close();

  $adminId = (int)$_SESSION['admin_id'];
  $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'update', 'lead', ?, ?, ?, ?)");
  if ($logStmt) {
    foreach ($leadIds as $lid) {
      $old = $current[$lid] ?? [];
      if ($status !== '' && ($old['counseling_status'] ?? '') !== $status) {
        $field = 'counseling_status';
        $oldVal = (string)($old['counseling_status'] ?? '');
        $newVal = $status;
        $logStmt->bind_param('iisss', $adminId, $lid, $field, $oldVal, $newVal);
        $logStmt->execute();
      }
      if ($counselor !== '' && ($old['counselor_name'] ?? '') !== $counselor) {
        $field = 'counselor_name';
        $oldVal = (string)($old['counselor_name'] ?? '');
        $newVal = $counselor;
        $logStmt->bind_param('iisss', $adminId, $lid, $field, $oldVal, $newVal);
        $logStmt->execute();
      }
      if ($followup !== '' && ($old['follow_up_date'] ?? '') !== $followup) {
        $field = 'follow_up_date';
        $oldVal = (string)($old['follow_up_date'] ?? '');
        $newVal = $followup;
        $logStmt->bind_param('iisss', $adminId, $lid, $field, $oldVal, $newVal);
        $logStmt->execute();
      }
    }
    $logStmt->close();
  }

  $mysqli->close();
  echo json_encode(['success' => true, 'message' => 'Bulk update successful.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Server error.']);
}

