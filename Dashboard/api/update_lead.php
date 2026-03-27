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
    echo json_encode(["success" => false, "message" => "Unauthorized. Please login."]);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Invalid payload."]);
    exit;
}

$leadId = (int)($data["id"] ?? 0);
$updates = $data["updates"] ?? [];
if ($leadId <= 0 || !is_array($updates)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Invalid lead id or updates."]);
    exit;
}

$map = [
    "full_name" => "full_name",
    "phone_number" => "phone_number",
    "email_address" => "email_address",
    "course_applied" => "course_applied",
    "source_of_lead" => "source_of_lead",
    "counselor_name" => "counselor_name",
    "counseling_status" => "counseling_status",
    "follow_up_date" => "follow_up_date"
];

$set = [];
$params = [];
$types = "";

foreach ($updates as $key => $value) {
    if (!isset($map[$key])) continue;
    $dbCol = $map[$key];
    if ($key === "follow_up_date") {
        $val = trim((string)$value);
        if ($val === "" || $val === "-") {
            $set[] = "$dbCol = NULL";
            continue;
        }
        $set[] = "$dbCol = ?";
        $params[] = $val;
        $types .= "s";
        continue;
    }

    $val = trim((string)$value);
    if ($key === "counseling_status" && ($val === "" || $val === "-")) {
        $set[] = "$dbCol = NULL";
        continue;
    }
    $set[] = "$dbCol = ?";
    $params[] = $val;
    $types .= "s";
}

if (empty($set)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "No valid fields to update."]);
    exit;
}

try {
    $mysqli = db_connect();
    $sql = "UPDATE leads SET " . implode(", ", $set) . " WHERE lead_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Prepare failed");
    }

    if (!empty($params)) {
        $types .= "i";
        $params[] = $leadId;
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("i", $leadId);
    }

    $stmt->execute();
    $stmt->close();
    $mysqli->close();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Failed to update lead."]);
    exit;
}

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Lead updated successfully."]);
exit;
