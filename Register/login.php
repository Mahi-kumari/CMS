<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/session.php";
ini_set('session.use_strict_mode', '1');
start_secure_session('CRM_USERSESSID');

require __DIR__ . "/../config/crm.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Validation failed.",
        "errors" => [
            "email" => $email === "" ? "Email is required." : "",
            "password" => $password === "" ? "Password is required." : ""
        ]
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Validation failed.",
        "errors" => ["email" => "Email must contain @ and a valid domain."]
    ]);
    exit;
}
if (strlen($email) > 100) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Validation failed.",
        "errors" => ["email" => "Email must be 100 characters or less."]
    ]);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Validation failed.",
        "errors" => ["password" => "Password must be at least 6 characters."]
    ]);
    exit;
}

$mysqli = null;
try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$stmt = $mysqli->prepare("SELECT user_id, full_name, email, password FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database error."]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$userRow = $result->fetch_assoc();

if (!$userRow) {
    http_response_code(404);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "You are not registered. Please sign up.",
        "errors" => ["email" => "You are not registered. Please sign up."]
    ]);
    $stmt->close();
    $mysqli->close();
    exit;
}

if (!password_verify($password, $userRow["password"])) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Incorrect password.",
        "errors" => ["password" => "Incorrect password."]
    ]);
    $stmt->close();
    $mysqli->close();
    exit;
}

session_regenerate_id(true);
$_SESSION["user_id"] = $userRow["user_id"];
$_SESSION["full_name"] = $userRow["full_name"];
$_SESSION["email"] = $userRow["email"];
$_SESSION["is_admin"] = false;
$_SESSION["active_role"] = "user";
unset($_SESSION["admin_id"], $_SESSION["admin_username"]);

$stmt->close();
// update last_login if column exists (or add it)
try {
    $cols = [];
    if ($res = $mysqli->query("SHOW COLUMNS FROM users")) {
        while ($row = $res->fetch_assoc()) {
            $cols[] = $row["Field"] ?? "";
        }
        $res->free();
    }
    if (!in_array("last_login", $cols, true)) {
        $mysqli->query("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
    }
    if ($stmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ? LIMIT 1")) {
        $uid = (int)$userRow["user_id"];
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    }
} catch (Throwable $e) {
    // ignore
}
$mysqli->close();

header("Content-Type: application/json");
echo json_encode(["message" => "Login successful."]);
exit;
?>

