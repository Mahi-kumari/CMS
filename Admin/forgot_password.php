<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
start_secure_session('CRM_ADMINSESSID');

require __DIR__ . "/../config/crm.php";

$step = "request";
$message = "";
$error = "";
$shownCode = "";
$username = trim($_POST["username"] ?? "");

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8");
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

    if ($code === "" && $new === "" && $confirm === "") {
        // Step 1: generate reset code
        if ($username === "") {
            $error = "Please enter username.";
        } else {
            $stmt = $mysqli->prepare("SELECT admin_id FROM admins WHERE username = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $admin = $res->fetch_assoc();
                $stmt->close();
                if (!$admin) {
                    $error = "Username not found.";
                } else {
                    $mysqli->query("CREATE TABLE IF NOT EXISTS admin_password_resets (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(150) NOT NULL,
                        code_hash VARCHAR(255) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    $del = $mysqli->prepare("DELETE FROM admin_password_resets WHERE username = ?");
                    if ($del) {
                        $del->bind_param("s", $username);
                        $del->execute();
                        $del->close();
                    }

                    $shownCode = (string)random_int(100000, 999999);
                    $hash = password_hash($shownCode, PASSWORD_BCRYPT);
                    $expires = (new DateTime("+15 minutes"))->format("Y-m-d H:i:s");

                    $ins = $mysqli->prepare("INSERT INTO admin_password_resets (username, code_hash, expires_at) VALUES (?, ?, ?)");
                    if ($ins) {
                        $ins->bind_param("sss", $username, $hash, $expires);
                        $ins->execute();
                        $ins->close();
                        $step = "reset";
                        $message = "Reset code generated. Use it below.";
                    } else {
                        $error = "Could not generate reset code.";
                    }
                }
            } else {
                $error = "Server error. Please try again.";
            }
        }
    } else {
        // Step 2: verify code and update password
        $step = "reset";
        if ($username === "" || $code === "" || $new === "" || $confirm === "") {
            $error = "Please fill in all fields.";
        } elseif ($new !== $confirm) {
            $error = "New password and confirmation do not match.";
        } elseif (strlen($new) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            $stmt = $mysqli->prepare("SELECT code_hash, expires_at FROM admin_password_resets WHERE username = ? ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();

                if (!$row) {
                    $error = "Reset code not found. Request a new one.";
                } else {
                    $expiresAt = new DateTime($row["expires_at"]);
                    if (new DateTime() > $expiresAt) {
                        $error = "Reset code expired. Request a new one.";
                    } elseif (!password_verify($code, $row["code_hash"])) {
                        $error = "Invalid reset code.";
                    } else {
                        $hashed = password_hash($new, PASSWORD_BCRYPT);
                        $upd = $mysqli->prepare("UPDATE admins SET password = ? WHERE username = ? LIMIT 1");
                        if ($upd) {
                            $upd->bind_param("ss", $hashed, $username);
                            $upd->execute();
                            $upd->close();
                            $del = $mysqli->prepare("DELETE FROM admin_password_resets WHERE username = ?");
                            if ($del) {
                                $del->bind_param("s", $username);
                                $del->execute();
                                $del->close();
                            }
                            $step = "done";
                            $message = "Password updated successfully. You can now log in.";
                        } else {
                            $error = "Could not update password.";
                        }
                    }
                }
            } else {
                $error = "Server error. Please try again.";
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
    <title>Admin Reset Password | CRM</title>
    <link rel="stylesheet" href="./login.css" />
  </head>
  <body>
    <div class="auth-shell">
      <section class="brand-panel">
        <div class="brand-content">
          <div class="brand-mark">C.R.M</div>
          <div class="brand-subtitle">Administrator Portal</div>
          <p class="brand-copy">Reset admin password using a secure on-screen code.</p>
          <div class="badge-admin">Secure Access Only</div>
          <div style="margin-top: 30px">
            <a class="brand-login" href="login.php">Back to Login</a>
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
          <form class="register-form" action="forgot_password.php" method="post">
            <label class="field">
              <span>Username</span>
              <input type="text" name="username" placeholder="admin" required />
            </label>
            <button class="primary-btn" type="submit">Get Code</button>
          </form>
        <?php elseif ($step === "reset") : ?>
          <?php if ($shownCode !== "") : ?>
            <div class="form-note" style="border:1px dashed #040074; padding:8px 10px; border-radius:8px; color:#040074;">
              Your reset code: <strong><?php echo h($shownCode); ?></strong>
            </div>
          <?php endif; ?>
          <form class="register-form" action="forgot_password.php" method="post">
            <label class="field">
              <span>Username</span>
              <input type="text" name="username" value="<?php echo h($username); ?>" required />
            </label>
            <label class="field">
              <span>Reset Code</span>
              <input type="text" name="code" placeholder="Enter 6-digit code" required />
            </label>
            <label class="field">
              <span>New Password</span>
              <input type="password" name="new_password" placeholder="New password" required />
            </label>
            <label class="field">
              <span>Confirm Password</span>
              <input type="password" name="confirm_password" placeholder="Confirm password" required />
            </label>
            <button class="primary-btn" type="submit">Update Password</button>
          </form>
        <?php else : ?>
          <a class="brand-login" href="login.php">Go to Login</a>
        <?php endif; ?>
      </section>
    </div>
  </body>
</html>



