<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/session.php";
$role = $_GET["role"] ?? $_POST["role"] ?? "";
start_secure_session_from_role($role);

require __DIR__ . "/../../config/crm.php";

if ($role === "admin") {
    if (empty($_SESSION["admin_id"])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["message" => "Unauthorized. Please login."]);
        exit;
    }
} else {
    if (empty($_SESSION["user_id"])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["message" => "Unauthorized. Please login."]);
        exit;
    }
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Invalid payload."]);
    exit;
}

$key = $data["key"] ?? "";
$value = $data["value"] ?? null;
$allowed = ["email_notifications", "sound_alerts"];

if (!in_array($key, $allowed, true)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Invalid setting key."]);
    exit;
}

$value = (int)((string)$value === "1" || $value === 1 || $value === true);

try {
    $mysqli = db_connect();
    if ($role === "admin") {
        $adminId = (int)$_SESSION["admin_id"];
        $mysqli->query("CREATE TABLE IF NOT EXISTS admin_settings (
            admin_id INT PRIMARY KEY,
            email_notifications TINYINT(1) DEFAULT 0,
            sound_alerts TINYINT(1) DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $stmt = $mysqli->prepare("INSERT INTO admin_settings (admin_id, {$key}) VALUES (?, ?) ON DUPLICATE KEY UPDATE {$key} = VALUES({$key})");
        if (!$stmt) {
            throw new RuntimeException("Prepare failed");
        }
        $stmt->bind_param("ii", $adminId, $value);
    } else {
        $userId = (int)$_SESSION["user_id"];
        $stmt = $mysqli->prepare("UPDATE users SET {$key} = ? WHERE user_id = ? LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException("Prepare failed");
        }
        $stmt->bind_param("ii", $value, $userId);
    }
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Failed to update setting."]);
    exit;
}

header("Content-Type: application/json");
echo json_encode(["success" => true, "key" => $key, "value" => $value]);
exit;
