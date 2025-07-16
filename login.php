<?php
session_start();

$error = '';
$success = '';

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $db = 'register';
    $user = 'root';
    $pass = '';

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_identifier = trim($_POST['user_identifier']);
    $password = trim($_POST['password']);

    if (!$user_identifier || !$password) {
        $_SESSION['error_message'] = "Please fill in all fields.";
        header("Location: login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $user_identifier, $user_identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['success_message'] = "Login successful!";
            header("Location: login.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Incorrect password.";
            header("Location: login.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "User not found.";
        header("Location: login.php");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link rel="stylesheet" href="style1.css" />
</head>
<body>

<div class="container">
  <?php if (isset($_SESSION['username'])): ?>
    <div class="welcome-box">
      <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>

      <form action="logout.php" method="POST" style="display:inline-block; margin-right: 10px;">
        <button type="submit">Logout</button>
      </form>

      <a href="index.php" class="quiz-btn">Go to Quiz</a>
    </div>
  <?php else: ?>
    <form action="login.php" method="POST" class="login-form">
      <h2>Login</h2>

      <input type="text" name="user_identifier" placeholder="Username or Email" required><br>
      <input type="password" name="password" placeholder="Password" required><br>

      <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <button type="submit">Login</button>
      <p>Don't have an account? <a href="register.php">Register</a></p>
    </form>
  <?php endif; ?>
</div>

</body>
</html>
