<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/session.php";
$role = $_GET["role"] ?? $_POST["role"] ?? "";
start_secure_session_from_role($role);

require __DIR__ . "/../../config/crm.php";

if (empty($_SESSION["user_id"])) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Unauthorized. Please login."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$current = trim((string)($_POST["current_password"] ?? ""));
$new = trim((string)($_POST["new_password"] ?? ""));
$confirm = trim((string)($_POST["confirm_password"] ?? ""));

if ($current === "" || $new === "" || $confirm === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "All password fields are required."]);
    exit;
}

if ($new !== $confirm) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "New password and confirmation do not match."]);
    exit;
}

$userId = (int)$_SESSION["user_id"];

try {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed");
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $stored = (string)($row["password"] ?? "");
    $ok = false;
    if ($stored !== "") {
        if (password_get_info($stored)["algo"] !== 0) {
            $ok = password_verify($current, $stored);
        } else {
            $ok = hash_equals($stored, $current);
        }
    }

    if (!$ok) {
        $mysqli->close();
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "message" => "Current password is incorrect."]);
        exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed");
    }
    $stmt->bind_param("si", $newHash, $userId);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["success" => false, "message" => "Failed to update password."]);
    exit;
}

header("Content-Type: application/json");
echo json_encode(["success" => true, "message" => "Password updated successfully."]);
exit;
