<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/session.php";
$role = $_GET["role"] ?? $_POST["role"] ?? "";
start_secure_session_from_role($role);

require __DIR__ . "/../../config/crm.php";

$hasUser = !empty($_SESSION["user_id"]);
$hasAdmin = !empty($_SESSION["is_admin"]);
if (!$hasUser && !$hasAdmin) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Unauthorized. Please login."]);
    exit;
}

try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$items = [];

// Follow-up due today
$sql = "SELECT full_name, follow_up_date, batch_assigned_time
        FROM leads
        WHERE follow_up_date = CURDATE()
        ORDER BY follow_up_date DESC, lead_id DESC
        LIMIT 5";
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $name = $row["full_name"] ?: "Lead";
        $time = $row["batch_assigned_time"] ? date("g:i A", strtotime($row["batch_assigned_time"])) : "today";
        $items[] = [
            "title" => "Follow-up Due Today",
            "text" => "Follow-up due: {$name} at {$time}"
        ];
    }
    $res->free();
}

// Batch starting soon (tomorrow)
$sql = "SELECT course_applied, batch_start_date
        FROM leads
        WHERE batch_start_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ORDER BY batch_start_date DESC, lead_id DESC
        LIMIT 5";
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $course = $row["course_applied"] ?: "Course";
        $items[] = [
            "title" => "Batch Starting Soon",
            "text" => "Batch starts tomorrow: {$course}"
        ];
    }
    $res->free();
}

// Class allotted today
$sql = "SELECT lead_id, preferred_time_slot, batch_type
        FROM leads
        WHERE class_allotted = 'Yes' AND batch_assigned_date = CURDATE()
        ORDER BY batch_assigned_date DESC, lead_id DESC
        LIMIT 5";
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $leadId = $row["lead_id"] ?? "";
        $batch = $row["preferred_time_slot"] ?: ($row["batch_type"] ?: "Batch");
        $items[] = [
            "title" => "Class Allotted",
            "text" => "Class allotted for Lead #{$leadId} (Batch: {$batch})"
        ];
    }
    $res->free();
}

$mysqli->close();

header("Content-Type: application/json");
echo json_encode(["items" => $items]);
exit;
