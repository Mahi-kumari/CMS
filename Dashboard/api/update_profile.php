<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/session.php";
$role = $_GET["role"] ?? $_POST["role"] ?? "";
start_secure_session_from_role($role);

require __DIR__ . "/../../config/crm.php";

if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Unauthorized. Please login."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

function val(string $key): ?string {
    if (!isset($_POST[$key])) return null;
    $v = trim((string)$_POST[$key]);
    return $v === "" ? null : $v;
}

$fullName = val("full_name");
$email = val("email");
$phone = val("phone");

if ($fullName === null) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Full name is required."]);
    exit;
}

if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Invalid email address."]);
    exit;
}

$userId = (int)$_SESSION["user_id"];

try {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed");
    }
    $stmt->bind_param("sssi", $fullName, $email, $phone, $userId);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Failed to update profile."]);
    exit;
}

$_SESSION["full_name"] = $fullName;
if ($email !== null) {
    $_SESSION["email"] = $email;
}

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Profile updated successfully."]);
exit;
