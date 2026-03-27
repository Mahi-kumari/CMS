<?php
declare(strict_types=1);

require __DIR__ . "/brevo_mailer.php";
require __DIR__ . "/../config/crm.php";

$mailConfig = require __DIR__ . "/mail_config.php";

$step = "request";
$message = "";
$error = "";
$email = trim($_POST["email"] ?? "");

function h($v): string {
    return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");
}

try {
    $mysqli = db_connect();
} catch (RuntimeException $e) {
    $mysqli = null;
    $error = "Server error. Please try again.";
}

if ($mysqli && $_SERVER["REQUEST_METHOD"] === "POST") {
    $code = trim($_POST["code"] ?? "");
    $new = $_POST["new_password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    // Ensure tables exist
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS password_resets (
            reset_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (token_hash),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    // Ensure used_at exists for older tables
    $colRes = $mysqli->query("SHOW COLUMNS FROM password_resets LIKE 'used_at'");
    if ($colRes && $colRes->num_rows === 0) {
        $mysqli->query("ALTER TABLE password_resets ADD COLUMN used_at DATETIME NULL");
    }
    if ($colRes) {
        $colRes->free();
    }
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS password_reset_requests (
            req_id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (email),
            INDEX (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if ($code === "" && $new === "" && $confirm === "") {
        // Step 1: send code
        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "A valid email is required.";
        } else {
            $ip = $_SERVER["REMOTE_ADDR"] ?? "unknown";
            $stmt = $mysqli->prepare(
                "SELECT COUNT(*) AS cnt FROM password_reset_requests
                 WHERE (email = ? OR ip_address = ?) AND created_at > (NOW() - INTERVAL 15 MINUTE)"
            );
            $stmt->bind_param("ss", $email, $ip);
            $stmt->execute();
            $countRow = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (($countRow["cnt"] ?? 0) >= 3) {
                $error = "Too many reset attempts. Please try again later.";
            } else {
                $stmt = $mysqli->prepare("INSERT INTO password_reset_requests (email, ip_address) VALUES (?, ?)");
                $stmt->bind_param("ss", $email, $ip);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare("SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $userRow = $result->fetch_assoc();
                $stmt->close();

                if ($userRow) {
                    $userId = (int)$userRow["user_id"];
                    $fullName = $userRow["full_name"] ?? "User";
                    $codePlain = (string)random_int(100000, 999999);
                    $codeHash = hash("sha256", $codePlain);
                    $expiresAt = (new DateTime("+15 minutes"))->format("Y-m-d H:i:s");

                    $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $mysqli->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $userId, $codeHash, $expiresAt);
                    $stmt->execute();
                    $stmt->close();

                    $safeName = htmlspecialchars($fullName, ENT_QUOTES, "UTF-8");
                    $safeCode = htmlspecialchars($codePlain, ENT_QUOTES, "UTF-8");
                    $html = "
                        <div style=\"font-family: Arial, sans-serif; color:#1c2b2a; line-height:1.6;\">
                          <h2 style=\"color:#040074;\">Password Reset</h2>
                          <p>Hello {$safeName},</p>
                          <p>Use the verification code below to reset your password. This code will expire in 15 minutes.</p>
                          <div style=\"font-size:20px; font-weight:700; letter-spacing:2px; margin:12px 0;\">{$safeCode}</div>
                          <p>Open the reset page and enter this code:</p>
                          <p>http://localhost:8080/CRM/Register/forgot_password.php</p>
                        </div>
                    ";
                    $textBody = "Your reset code is: {$codePlain}. Use it at: http://localhost:8080/CRM/Register/forgot_password.php";
                    $send = brevo_send_email($mailConfig, $email, $fullName, "Your ICSS CRM reset code", $html, $textBody);
                    if (!$send["ok"]) {
                        $error = "Email could not be sent. " . ($send["error"] ?? "Brevo API error");
                    }
                }
                if ($error === "") {
                    $message = "If this email is registered, a reset code has been sent.";
                    $step = "reset";
                }
            }
        }
    } else {
        // Step 2: verify code and update password
        $step = "reset";
        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "A valid email is required.";
        } else {
            $code = preg_replace('/\\D+/', '', $code);
            if ($code === "" || !preg_match('/^[0-9]{6}$/', $code)) {
                $error = "A valid 6-digit code is required.";
            }
        }
        if ($error === "" && ($new === "" || strlen($new) < 6)) {
            $error = "Password must be at least 6 characters.";
        } elseif ($error === "" && $new !== $confirm) {
            $error = "New password and confirmation do not match.";
        }
        if ($error === "") {
            $codeHash = hash("sha256", $code);
            $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $userRow = $res->fetch_assoc();
            $stmt->close();

            if (!$userRow) {
                $error = "Email not found.";
            } else {
                $userId = (int)$userRow["user_id"];
                $stmt = $mysqli->prepare(
                    "SELECT reset_id, expires_at, used_at
                     FROM password_resets
                     WHERE user_id = ? AND token_hash = ?
                     ORDER BY reset_id DESC
                     LIMIT 1"
                );
                $stmt->bind_param("is", $userId, $codeHash);
                $stmt->execute();
                $res2 = $stmt->get_result();
                $row2 = $res2->fetch_assoc();
                $stmt->close();

                if (!$row2) {
                    $error = "Invalid code.";
                } else {
                    if (!empty($row2["used_at"])) {
                        $error = "This code was already used.";
                    } else {
                        $expiresAt = new DateTime($row2["expires_at"]);
                        if (new DateTime() > $expiresAt) {
                            $error = "This code has expired. Request a new one.";
                        }
                    }
                }
            }

            if ($error === "") {
                $hashed = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $hashed, $userId);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();

                $step = "done";
                $message = "Password updated successfully. You can now log in.";
            }
        }
    }
}

if ($mysqli) {
    $mysqli->close();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password | CRM</title>
    <link rel="stylesheet" href="login.css?v=4" />
  </head>
  <body>
    <div class="auth-shell">
      <section class="brand-panel">
        <div class="brand-content">
          <div class="brand-mark">C.R.M</div>
          <div class="brand-subtitle">Customer Relationship Management</div>
          <p class="brand-copy">Reset your account password using an email code.</p>
          <div style="margin-top: 30px">
            <a class="brand-login" href="login.html">Back to Login</a>
            <div class="brand-note">Remembered your password?</div>
          </div>
        </div>
      </section>

      <section class="form-panel">
        <h2>Reset Password</h2>
        <p class="form-note">Request a code, then set a new password.</p>

        <?php if ($message !== "") : ?>
          <p class="form-note" style="color:#1aa88b;"><?php echo h($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== "") : ?>
          <p class="form-note" style="color:#c0392b;"><?php echo h($error); ?></p>
        <?php endif; ?>

        <?php if ($step === "request") : ?>
          <form class="register-form" action="forgot_password.php" method="post" autocomplete="off" novalidate>
            <label class="field">
              <span>Email</span>
              <input type="email" name="email" placeholder="name@company.com" required />
            </label>
            <button class="primary-btn" type="submit">Send Code</button>
          </form>
        <?php elseif ($step === "reset") : ?>
          <form class="register-form" action="forgot_password.php" method="post" autocomplete="off" novalidate>
            <label class="field">
              <span>Email</span>
              <input type="email" name="email" value="<?php echo h($email); ?>" required />
            </label>
            <label class="field">
              <span>Verification Code</span>
              <input type="text" name="code" placeholder="6-digit code" required />
            </label>
            <label class="field">
              <span>New Password</span>
              <input type="password" name="new_password" required />
            </label>
            <label class="field">
              <span>Confirm Password</span>
              <input type="password" name="confirm_password" required />
            </label>
            <button class="primary-btn" type="submit">Update Password</button>
          </form>
        <?php else : ?>
          <a class="brand-login" href="login.html">Go to Login</a>
        <?php endif; ?>
      </section>
    </div>
  </body>
</html>
