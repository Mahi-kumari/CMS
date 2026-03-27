<?php
declare(strict_types=1);

require __DIR__ . "/../config/crm.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $code = trim($_POST["code"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "A valid email is required.";
    } elseif ($code === "" || !preg_match('/^[0-9]{6}$/', $code)) {
        $error = "A valid 6-digit code is required.";
    } elseif ($password === "" || strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $mysqli = null;
        try {
            $mysqli = db_connect();
        } catch (RuntimeException $e) {
            $error = "Database connection failed.";
        }
        if ($mysqli) {
            $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $error = "Invalid email or code.";
            } else {
                $userId = (int)$row["user_id"];
                $codeHash = hash("sha256", $code);
                $stmt = $mysqli->prepare(
                    "SELECT reset_id FROM password_resets
                     WHERE user_id = ? AND token_hash = ? AND expires_at > NOW() AND used_at IS NULL
                     LIMIT 1"
                );
                $stmt->bind_param("is", $userId, $codeHash);
                $stmt->execute();
                $res2 = $stmt->get_result();
                $row2 = $res2->fetch_assoc();
                $stmt->close();

                if (!$row2) {
                    $error = "Invalid email or code.";
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed, $userId);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $mysqli->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Password reset successful. You can now login.";
                }
            }
            $mysqli->close();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password</title>
  <link rel="stylesheet" href="register.css?v=2" />
</head>
<body>
  <div class="auth-shell">
    <section class="brand-panel">
      <div class="brand-content">
        <div class="brand-mark">C.R.M</div>
        <div class="brand-subtitle">Customer Relationship Management</div>
        <p class="brand-copy">Reset your account password.</p>
      </div>
    </section>
    <section class="form-panel">
      <h2>Reset Password</h2>
      <p class="form-note">This page is no longer used. Please use Forgot Password.</p>
      <a class="primary-btn" href="forgot_password.php">Go to Forgot Password</a>
    </section>
  </div>
</body>
</html>
