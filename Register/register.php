<?php
require __DIR__ . "/brevo_mailer.php";
require __DIR__ . "/../config/crm.php";

$mailConfig = require __DIR__ . "/mail_config.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Method not allowed."]);
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phoneRaw = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm_password"] ?? "";

$errors = [];
if ($fullName === "") {
    $errors["full_name"] = "Full name is required.";
}
if (strlen($fullName) < 3 || strlen($fullName) > 100) {
    $errors["full_name"] = "Full name must be 3 to 100 characters.";
}
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors["email"] = "A valid email is required.";
}
if (strlen($email) > 100) {
    $errors["email"] = "Email must be 100 characters or less.";
}
if ($phoneRaw === "") {
    $errors["phone"] = "Phone number is required.";
} elseif (!preg_match('/^[0-9]{10}$/', $phoneRaw)) {
    $errors["phone"] = "Phone number must be 10 digits.";
}
if ($password === "") {
    $errors["password"] = "Password is required.";
}
if (strlen($password) < 6) {
    $errors["password"] = "Password must be at least 6 characters.";
}
if ($password !== $confirm) {
    $errors["confirm_password"] = "Passwords do not match.";
}

if (!empty($errors)) {
    http_response_code(400);
    header("Content-Type: application/json");
    echo json_encode([
        "message" => "Validation failed.",
        "errors" => $errors
    ]);
    exit;
}

$phone = $phoneRaw === "" ? null : $phoneRaw;

$mysqli = null;
try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["message" => "Database connection failed."]);
    exit;
}

$check = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
if ($check) {
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        http_response_code(409);
        header("Content-Type: application/json");
        echo json_encode([
            "message" => "You are already registered. Please login.",
            "errors" => ["email" => "This email is already registered."]
        ]);
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

$mailError = null;
$safeName = htmlspecialchars($fullName, ENT_QUOTES, "UTF-8");
$safeEmail = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
$html = "
    <div style=\"font-family: Arial, sans-serif; color:#1c2b2a; line-height:1.6;\">
      <h2 style=\"color:#040074;\">Welcome to ICSS CRM, {$safeName}!</h2>
      <p>Thanks for registering. Your account has been created successfully.</p>
      <p><strong>Account Email:</strong> {$safeEmail}</p>
      <p>You can now log in and start managing your leads.</p>
      <hr style=\"border:none;border-top:1px solid #e6e6e6; margin:16px 0;\">
      <p style=\"font-size:12px;color:#6b7b7a;\">If you didn’t create this account, please ignore this email.</p>
      <p style=\"font-size:12px;color:#6b7b7a;\">— ICSS CRM Team</p>
    </div>
";
$textBody = "Welcome to ICSS CRM, {$fullName}! Your account is ready. Account Email: {$email}. If you didn't create this account, please ignore this email.";

$send = brevo_send_email($mailConfig, $email, $fullName, "Welcome to ICSS CRM — Your Account Is Ready", $html, $textBody);
if (!$send["ok"]) {
    $mailError = "Email could not be sent. " . ($send["error"] ?? "Brevo API error");
}

header("Content-Type: application/json");
echo json_encode([
    "message" => $mailError ? "Registration successful, but email could not be sent." : "Registration successful.",
    "mail_error" => $mailError,
    "insert_id" => $insertId,
    "db_name" => $dbNameRow["db_name"] ?? null,
    "email" => $email
]);
exit;
?>

