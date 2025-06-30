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

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!$username || !$email || !$password) {
        $_SESSION['error_message'] = "Please fill in all fields.";
        header("Location: register.php");
        exit;
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = "Username or email already taken.";
        $stmt->close();
        $conn->close();
        header("Location: register.php");
        exit;
    }
    $stmt->close();

    // Hash password and insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Registration successful! You can now login.";
        $stmt->close();
        $conn->close();
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Error occurred during registration.";
        $stmt->close();
        $conn->close();
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="style1.css">
</head>
<body>
  <div class="container">
    <form action="register.php" method="POST" class="register-form">
      <h2>Register</h2>

      <input type="text" name="username" placeholder="Username" required><br>
      <input type="email" name="email" placeholder="Email" required><br>
      <input type="password" name="password" placeholder="Password" required><br>

      <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <button type="submit">Register</button>
      <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
</body>
</html>
