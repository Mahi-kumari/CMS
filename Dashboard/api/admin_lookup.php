<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/session.php';
start_secure_session('CRM_ADMINSESSID');

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['is_admin']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . "/../../config/crm.php";

$type = trim($_GET['type'] ?? '');
$q = trim($_GET['q'] ?? '');

if ($type === '' || $q === '') {
    echo json_encode(['success' => true, 'items' => []]);
    exit;
}

try {
    $mysqli = db_connect();
    if ($type === 'admin') {
        $stmt = $mysqli->prepare("SELECT username FROM admins WHERE username LIKE ? ORDER BY username ASC LIMIT 20");
        $like = '%' . $q . '%';
        $stmt->bind_param('s', $like);
    } else {
        $stmt = $mysqli->prepare("SELECT full_name, email FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY full_name ASC LIMIT 20");
        $like = '%' . $q . '%';
        $stmt->bind_param('ss', $like, $like);
    }
    $items = [];
    if ($stmt && $stmt->execute()) {
        if ($type === 'admin') {
            $stmt->bind_result($username);
            while ($stmt->fetch()) {
                $items[] = ['label' => (string)$username];
            }
        } else {
            $stmt->bind_result($fullName, $email);
            while ($stmt->fetch()) {
                $label = trim((string)$fullName);
                if ($email) $label .= " (" . $email . ")";
                $items[] = ['label' => $label];
            }
        }
        $stmt->close();
    }
    $mysqli->close();
    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

