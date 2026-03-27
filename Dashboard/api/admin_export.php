<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
start_secure_session('CRM_ADMINSESSID');

if (empty($_SESSION['is_admin']) || empty($_SESSION['admin_id'])) {
  http_response_code(403);
  echo 'Admin access required.';
  exit;
}

require __DIR__ . '/../../config/crm.php';

$type = isset($_GET['type']) ? strtolower((string)$_GET['type']) : '';
if (!in_array($type, ['users', 'leads'], true)) {
  http_response_code(400);
  echo 'Invalid export type.';
  exit;
}

try {
  $mysqli = db_connect();

  if ($type === 'users') {
    $sql = "SELECT user_id, full_name, email, phone, created_at, email_notifications, sound_alerts FROM users ORDER BY user_id DESC";
    $filename = 'users_export_' . date('Ymd_His') . '.csv';
  } else {
    $allowed = [
      'lead_id', 'user_id', 'full_name', 'phone_number', 'whatsapp_number', 'email_address',
      'course_applied', 'source_of_lead', 'counselor_name', 'counseling_status',
      'follow_up_date', 'created_at', 'training_mode', 'center_location', 'batch_start_date',
      'batch_end_date', 'trainer_assigned'
    ];
    $columnsParam = isset($_GET['columns']) ? (string)$_GET['columns'] : '';
    $cols = [];
    if ($columnsParam !== '') {
      foreach (explode(',', $columnsParam) as $col) {
        $col = trim($col);
        if (in_array($col, $allowed, true)) {
          $cols[] = $col;
        }
      }
    }
    if (empty($cols)) {
      $cols = $allowed;
    }
    $select = implode(', ', array_map(static fn($c) => "`{$c}`", $cols));
    $leadIdsParam = isset($_GET['lead_ids']) ? (string)$_GET['lead_ids'] : '';
    $leadIds = [];
    if ($leadIdsParam !== '') {
      foreach (explode(',', $leadIdsParam) as $id) {
        $id = trim($id);
        if ($id !== '' && ctype_digit($id)) {
          $leadIds[] = (int)$id;
        }
      }
    }
    $where = '';
    if (!empty($leadIds)) {
      $where = ' WHERE lead_id IN (' . implode(',', $leadIds) . ')';
    }
    $sql = "SELECT {$select} FROM leads{$where} ORDER BY lead_id DESC";
    $filename = 'leads_export_' . date('Ymd_His') . '.csv';
  }

  $res = $mysqli->query($sql);
  if (!$res) {
    http_response_code(500);
    echo 'Export failed.';
    exit;
  }

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');

  $out = fopen('php://output', 'w');
  // UTF-8 BOM for Excel compatibility
  echo "\xEF\xBB\xBF";
  $first = $res->fetch_assoc();
  if ($first) {
    $formatRow = static function (array $row): array {
      foreach (['phone_number', 'whatsapp_number', 'phone'] as $k) {
        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
          // Force Excel to keep as text (avoid scientific notation / truncation)
          $row[$k] = '="' . $row[$k] . '"';
        }
      }
      return $row;
    };

    fputcsv($out, array_keys($first));
    fputcsv($out, $formatRow($first));
    while ($row = $res->fetch_assoc()) {
      fputcsv($out, $formatRow($row));
    }
  } else {
    fputcsv($out, ['No data']);
  }
  fclose($out);
  $res->free();

  $adminId = (int)$_SESSION['admin_id'];
  $logStmt = $mysqli->prepare("INSERT INTO admin_audit_log (admin_id, action_type, target_type, target_id, field_name, old_value, new_value) VALUES (?, 'export', ?, 0, NULL, NULL, NULL)");
  if ($logStmt) {
    $target = $type;
    $logStmt->bind_param('is', $adminId, $target);
    $logStmt->execute();
    $logStmt->close();
  }

  $mysqli->close();
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Server error.';
  exit;
}

