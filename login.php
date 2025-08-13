<?php
require_once 'auth.php';
require_once 'database_functions_enhanced.php';

if (isLoggedIn()) {
  header("Location: dashboard.php");
  exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $card_no = trim($_POST['card_no']);
  $password = trim($_POST['password']);
  $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
  $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

  if (empty($card_no)) {
    $error_message = "Please enter your Employee ID.";
  } elseif (empty($password)) {
    $error_message = "Please enter your password.";
  } else {
    $auth_result = authenticateUser($card_no, $password);

    if ($auth_result['status'] == 'success') {
      $_SESSION['user_id'] = $auth_result['user']['id'];
      $_SESSION['user'] = $auth_result['user'];
      header("Location: dashboard.php");
      exit();
    } elseif ($auth_result['status'] == 'first_login') {
      if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
          $error_message = "Passwords do not match.";
        } elseif (strlen($new_password) < 6) {
          $error_message = "Password must be at least 6 characters long.";
        } else {
          if (setFirstTimePassword($card_no, $new_password)) {
            $success_message = "Password set successfully. Please login with your new password.";
          } else {
            $error_message = "Error setting password. Please try again.";
          }
        }
      } else {
        $first_login = true;
        $user_data = $auth_result['user'];
      }
    } elseif ($auth_result['status'] == 'invalid_password') {
      $error_message = "Invalid password.";
    } else {
      $error_message = "Employee ID not found.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Performance Evaluation</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #ECDCBF, #ECDCBF, #D2665A, #ECDCBF, #ECDCBF);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-size: 400% 400%;
      animation: gradientBG 20s ease infinite;
    }

    @keyframes gradientBG {
      0% {
        background-position: 0% 50%;
      }

      50% {
        background-position: 100% 50%;
      }

      100% {
        background-position: 0% 50%;
      }
    }

    .login-container {
      background: rgba(255, 255, 255, 0.25);
      backdrop-filter: blur(15px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border-radius: 20px;
      padding: 40px;
      max-width: 400px;
      width: 100%;
      animation: fadeIn 1s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .login-logo {
      width: 70px;
      animation: dropIn 1s ease;
    }

    @keyframes dropIn {
      0% {
        opacity: 0;
        transform: translateY(-20px);
      }

      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-title {
      font-size: 22px;
      margin-top: 10px;
      color: #2c3e50;
    }

    .login-subtitle {
      font-size: 14px;
      color: #555;
    }

    .alert {
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 6px;
      font-size: 14px;
      animation: fadeIn 0.5s ease;
    }

    .alert-error {
      background-color: rgba(231, 76, 60, 0.1);
      border-left: 4px solid #e74c3c;
      color: #c0392b;
    }

    .alert-success {
      background-color: rgba(46, 204, 113, 0.1);
      border-left: 4px solid #2ecc71;
      color: #27ae60;
    }

    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-control {
      width: 100%;
      padding: 12px 40px 12px 40px;
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      background-color: rgba(255, 255, 255, 0.9);
      font-size: 14px;
      transition: 0.3s;
    }

    .form-control:focus {
      outline: none;
      box-shadow: 0 0 5px rgba(231, 76, 60, 0.3);
      border-color: #e74c3c;
    }

    .form-label {
      position: absolute;
      top: 50%;
      left: 40px;
      transform: translateY(-50%);
      pointer-events: none;
      transition: 0.2s;
      color: #999;
      background: #fff;
      padding: 0 4px;
      font-size: 14px;
    }

    .form-control:focus+.form-label,
    .form-control:not(:placeholder-shown)+.form-label {
      top: -10px;
      left: 12px;
      font-size: 12px;
      color: #e74c3c;
    }

    .form-icon {
      position: absolute;
      top: 50%;
      left: 12px;
      transform: translateY(-50%);
      color: #bbb;
      user-select: none;
    }

    .toggle-password {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #888;
      user-select: none;
      width: 20px;
      height: 20px;
      fill: #888;
      transition: fill 0.3s ease;
    }

    .toggle-password:hover {
      fill: #e74c3c;
      color: #e74c3c;
    }

    .btn-primary {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      background: linear-gradient(to right, #e74c3c, #c0392b);
      color: white;
      font-weight: bold;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
    }

    .info-text {
      text-align: center;
      font-size: 12px;
      color: #555;
      margin-top: 20px;
    }

    @media screen and (max-width: 480px) {
      .login-container {
        margin: 0 15px;
        padding: 30px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <div class="login-header">
      <img src="assets/seiwa.logo.png" alt="Seiwa Logo" class="login-logo" />
      <h1 class="login-title">Performance Evaluation System</h1>
      <p class="login-subtitle">Seiwa Kaiun Philippines Inc.</p>
    </div>

    <?php if (!empty($error_message)): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (isset($first_login) && $first_login): ?>
      <form method="POST" action="">
        <input type="hidden" name="card_no" value="<?php echo htmlspecialchars($card_no); ?>" />
        <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>" />

        <div class="form-group">
          <span class="form-icon">🔒</span>
          <input
            type="password"
            id="new_password"
            name="new_password"
            class="form-control"
            required
            placeholder=" "
            minlength="6" />
          <label for="new_password" class="form-label">New Password</label>
          <svg
            class="toggle-password"
            onclick="togglePassword('new_password', this)"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24">
            <path
              d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" />
          </svg>
        </div>

        <div class="form-group">
          <span class="form-icon">🔒</span>
          <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            class="form-control"
            required
            placeholder=" "
            minlength="6" />
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <svg
            class="toggle-password"
            onclick="togglePassword('confirm_password', this)"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24">
            <path
              d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" />
          </svg>
        </div>

        <button type="submit" class="btn-primary">Set Password</button>
      </form>

      <div class="alert alert-info" style="margin-top: 15px; font-size: 14px;">
        Welcome, <?php echo htmlspecialchars($user_data['fullname']); ?>! This is your first login. Please set up your password.
      </div>
    <?php else: ?>
      <form method="POST" action="">
        <div class="form-group">
          <span class="form-icon">👤</span>
          <input
            type="text"
            id="card_no"
            name="card_no"
            class="form-control"
            required
            placeholder=" "
            value="<?php echo isset($_POST['card_no']) ? htmlspecialchars($_POST['card_no']) : ''; ?>" />
          <label for="card_no" class="form-label">Employee ID</label>
        </div>

        <div class="form-group">
          <span class="form-icon">🔒</span>
          <input
            type="password"
            id="password"
            name="password"
            class="form-control"
            required
            placeholder=" " />
          <label for="password" class="form-label">Password</label>
          <svg
            class="toggle-password"
            onclick="togglePassword('password', this)"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24">
            <path
              d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7zm0 12a5 5 0 110-10 5 5 0 010 10zm0-8a3 3 0 100 6 3 3 0 000-6z" />
          </svg>
        </div>

        <button type="submit" class="btn-primary">Login</button>
      </form>
    <?php endif; ?>

    <div class="info-text">
      <p>For first-time users, use your Employee ID as both username and password.</p>
      <p>Contact HR for assistance: hr@seiwakaiun.com.ph</p>
    </div>
  </div>

  <script>
    function togglePassword(fieldId, svgElement) {
      const input = document.getElementById(fieldId);
      if (input.type === "password") {
        input.type = "text";
        svgElement.style.fill = "#e74c3c";
      } else {
        input.type = "password";
        svgElement.style.fill = "#888";
      }
    }

    document.getElementById('confirm_password')?.addEventListener('input', function() {
      const password = document.getElementById('new_password').value;
      this.setCustomValidity(this.value !== password ? 'Passwords do not match' : '');
    });
  </script>
</body>

</html>