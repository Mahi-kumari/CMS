<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/session.php";
ini_set('session.use_strict_mode', '1');
start_secure_session('CRM_ADMINSESSID');

require __DIR__ . "/../config/crm.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter username and password.";
    } else {
        try {
            $mysqli = db_connect();
            $stmt = $mysqli->prepare("SELECT admin_id, username, password FROM admins WHERE username = ? LIMIT 1");
            if (!$stmt) {
                throw new RuntimeException("DB error");
            }
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $adminRow = $result->fetch_assoc();
            $stmt->close();
            $mysqli->close();

            if (!$adminRow) {
                $error = "Incorrect username or password.";
            } else {
                $stored = (string)($adminRow["password"] ?? "");
                $passwordOk = false;
                if ($stored !== "") {
                    if (password_get_info($stored)["algo"] !== 0) {
                        $passwordOk = password_verify($password, $stored);
                    } else {
                        $passwordOk = hash_equals($stored, $password);
                    }
                }

                if (!$passwordOk) {
                    $error = "Incorrect username or password.";
                } else {
                    session_regenerate_id(true);
                    unset($_SESSION["user_id"], $_SESSION["full_name"], $_SESSION["email"]);
                    $_SESSION["admin_id"] = $adminRow["admin_id"];
                    $_SESSION["admin_username"] = $adminRow["username"];
                    $_SESSION["is_admin"] = true;
                    $_SESSION["active_role"] = "admin";
                    header("Location: index.php");
                    exit;
                }
            }
        } catch (RuntimeException $e) {
            $error = "Server error. Please try again.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login | CRM</title>
    <link rel="stylesheet" href="./login.css" />
  </head>

  <body>
    <div class="auth-shell">
      <section class="brand-panel">
        <div class="brand-content">
          <img class="brand-logo" src="../Register/assests/logo.png" alt="ICSS CRM Logo" />
          <div class="brand-subtitle">Administrator Portal</div>
          <p class="brand-copy">
            Access the management console to monitor performance, manage users,
            and configure system settings.
          </p>
          <div class="badge-admin">Secure Access Only</div>
          <div style="margin-top: 30px">
            <a class="brand-login" href="../Register/login.html">User Login</a>
            <div class="brand-note">Not an admin?</div>
          </div>
        </div>
      </section>

      <section class="form-panel">
        <h2>Admin Sign In</h2>
        <p class="form-note">Enter your administrative credentials.</p>

        <form class="register-form" action="login.php" method="post">
          <label class="field">
            <span>Username</span>
            <input type="text" name="username" placeholder="Enter your username" required />
            <small class="error-text"></small>
          </label>

          <label class="field">
            <span>Password</span>
            <input type="password" name="password" placeholder="Enter your password" required />
            <small class="error-text"></small>
          </label>

          <a href="forgot_password.php" class="forgot-link">Forgot password?</a>

          <?php if ($error !== "") : ?>
            <p class="form-note" style="color:#c0392b; margin-top:4px;"><?php echo htmlspecialchars($error); ?></p>
          <?php endif; ?>

          <button class="primary-btn">Login</button>
        </form>
      </section>
    </div>

  </body>
</html>


