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

    $adminId = (int)$_SESSION["admin_id"];
    $settings = [
        "email_notifications" => 0,
        "sound_alerts" => 0
    ];

    try {
        $mysqli = db_connect();
        $mysqli->query("CREATE TABLE IF NOT EXISTS admin_settings (
            admin_id INT PRIMARY KEY,
            email_notifications TINYINT(1) DEFAULT 0,
            sound_alerts TINYINT(1) DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        $stmt = $mysqli->prepare("SELECT email_notifications, sound_alerts FROM admin_settings WHERE admin_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $settings["email_notifications"] = (int)($row["email_notifications"] ?? 0);
                $settings["sound_alerts"] = (int)($row["sound_alerts"] ?? 0);
            }
            $stmt->close();
        }
        $mysqli->close();
    } catch (RuntimeException $e) {
        // keep defaults
    }
} else {
    if (empty($_SESSION["user_id"])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(["message" => "Unauthorized. Please login."]);
        exit;
    }

    $userId = (int)$_SESSION["user_id"];
    $settings = [
        "email_notifications" => 0,
        "sound_alerts" => 0
    ];

    try {
        $mysqli = db_connect();
        $stmt = $mysqli->prepare("SELECT email_notifications, sound_alerts FROM users WHERE user_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $settings["email_notifications"] = (int)($row["email_notifications"] ?? 0);
                $settings["sound_alerts"] = (int)($row["sound_alerts"] ?? 0);
            }
            $stmt->close();
        }
        $mysqli->close();
    } catch (RuntimeException $e) {
        // keep defaults
    }
}

header("Content-Type: application/json");
echo json_encode($settings);
exit;
