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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

$emailNotifications = (int)((string)($_POST["email_notifications"] ?? "0") === "1");
$soundAlerts = (int)((string)($_POST["sound_alerts"] ?? "0") === "1");

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
        $stmt = $mysqli->prepare("INSERT INTO admin_settings (admin_id, email_notifications, sound_alerts) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE email_notifications = VALUES(email_notifications), sound_alerts = VALUES(sound_alerts)");
        if (!$stmt) {
            throw new RuntimeException("Prepare failed");
        }
        $stmt->bind_param("iii", $adminId, $emailNotifications, $soundAlerts);
    } else {
        $userId = (int)$_SESSION["user_id"];
        $stmt = $mysqli->prepare("UPDATE users SET email_notifications = ?, sound_alerts = ? WHERE user_id = ? LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException("Prepare failed");
        }
        $stmt->bind_param("iii", $emailNotifications, $soundAlerts, $userId);
    }
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Failed to update notifications."]);
    exit;
}

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Notification settings updated."]);
exit;
