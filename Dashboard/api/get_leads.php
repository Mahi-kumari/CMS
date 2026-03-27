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

$sql = "SELECT
            lead_id,
            full_name,
            phone_number,
            email_address,
            course_applied,
            source_of_lead,
            counselor_name,
            counseling_status AS lead_status,
            follow_up_date,
            created_at
        FROM leads
        ORDER BY created_at DESC, lead_id DESC";

$result = $mysqli->query($sql);
if (!$result) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Failed to fetch leads."]);
    $mysqli->close();
    exit;
}

$leads = [];
while ($row = $result->fetch_assoc()) {
    $status = trim((string)($row["lead_status"] ?? ""));
    if ($status === "") $status = "-";
    $leads[] = [
        "id" => (int)($row["lead_id"] ?? 0),
        "name" => $row["full_name"] ?? "",
        "phone" => $row["phone_number"] ?? "",
        "email" => $row["email_address"] ?? "",
        "course" => $row["course_applied"] ?? "",
        "source" => $row["source_of_lead"] ?? "",
        "assignedUser" => $row["counselor_name"] ?? "",
        "status" => $status,
        "nextFollowup" => $row["follow_up_date"] ?? "",
        "createdDate" => $row["created_at"] ?? ""
    ];
}

$result->free();
$mysqli->close();

header("Content-Type: application/json");
echo json_encode(["leads" => $leads]);
exit;
