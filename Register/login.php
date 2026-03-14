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

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Email and password are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Invalid email format."]);
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

$stmt = $mysqli->prepare("SELECT user_id, password FROM users WHERE email = ?");
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

if (!$userRow || !password_verify($password, $userRow["password"])) {
    http_response_code(401);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Invalid email or password."]);
    $stmt->close();
    $mysqli->close();
    exit;
}

$stmt->close();
$mysqli->close();

header("Content-Type: application/json");
echo json_encode(["message" => "Login successful."]);
exit;
?>
