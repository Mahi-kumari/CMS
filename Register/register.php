<?php
declare(strict_types=1);

$host = "localhost";
$user = "root";
$pass = "csmpl@12";
$db   = "crm_project";
$port = 3306;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm_password"] ?? "";

$errors = [];
if ($fullName === "") {
    $errors[] = "Full name is required.";
}
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "A valid email is required.";
}
if ($password === "") {
    $errors[] = "Password is required.";
}
if ($password !== $confirm) {
    $errors[] = "Passwords do not match.";
}

if (!empty($errors)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => implode(" ", $errors)]);
    exit;
}

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$mysqli->set_charset("utf8mb4");

$check = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
if ($check) {
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        http_response_code(409);
        header("Content-Type: application/json");
        echo json_encode(["message" => "You are already registered. Please login."]);
        $mysqli->close();
        exit;
    }
    $check->close();
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $mysqli->prepare(
    "INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)"
);
if (!$stmt) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database error."]);
    exit;
}

$stmt->bind_param("ssss", $fullName, $email, $phone, $hashed);
$ok = $stmt->execute();

if (!$ok) {
    header("Content-Type: application/json");
    if ($stmt->errno === 1062) {
        http_response_code(409);
        echo json_encode(["message" => "You are already registered. Please login."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Registration failed. Please try again."]);
    }
    $stmt->close();
    $mysqli->close();
    exit;
}

$affected = $stmt->affected_rows;
$insertId = $stmt->insert_id;

$dbNameResult = $mysqli->query("SELECT DATABASE() AS db_name");
$dbNameRow = $dbNameResult ? $dbNameResult->fetch_assoc() : null;

$stmt->close();
$mysqli->close();

if ($affected !== 1) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Registration failed to persist."]);
    exit;
}

header("Content-Type: application/json");
echo json_encode([
    "message" => "Registration successful.",
    "insert_id" => $insertId,
    "db_name" => $dbNameRow["db_name"] ?? null,
    "email" => $email
]);
exit;
?>
