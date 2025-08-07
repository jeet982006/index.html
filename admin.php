<?php
session_start();

// Restrict access to admin only
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// DB connection
$host = 'localhost';
$db = 'register';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users
$sql = "SELECT username, email, password FROM users ORDER BY id DESC";
$result = $conn->query($sql);

// Subject tables
$subjects = [
    'HTML' => 'html_scores',
    'Java' => 'java_scores',
    'Python' => 'python_scores',
    'PHP' => 'php_scores',
    'C++' => 'cpp_scores'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style2.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .nav-buttons {
            margin: 20px auto;
            text-align: center;
        }
        .nav-button, .logout-button {
            padding: 10px 20px;
            background-color: #444;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .nav-button:hover, .logout-button:hover {
            background-color: #666;
        }
        table {
            border-collapse: collapse;
            width: 95%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #888;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #444;
            color: #fff;
        }
        .secure {
            color: green;
            font-weight: bold;
        }
        .not-secure {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="nav-buttons">
    <h1>Admin Dashboard</h1>
    <a href="logout.php" class="logout-button">🔒 Logout</a>
</div>

<h2 style="text-align:center;">Registered Users and Quiz Scores</h2>

<table>
    <tr>
        <th>#</th>
        <th>Username</th>
        <th>Email</th>
        <th>Password Security</th>
        <?php foreach ($subjects as $subject => $table): ?>
            <th><?= $subject ?> Score</th>
        <?php endforeach; ?>
    </tr>

<?php if ($result && $result->num_rows > 0): ?>
    <?php $serial = 1; ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $serial++ ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td>
                <?php
                if (preg_match('/^\$2[ayb]\$/', $row['password'])) {
                    echo "<span class='secure'>Secure</span>";
                } else {
                    echo "<span class='not-secure'>Not Secure</span>";
                }
                ?>
            </td>

            <?php
            foreach ($subjects as $subject => $table) {
                $stmt = $conn->prepare("SELECT score, total_questions FROM $table WHERE username = ? ORDER BY submitted_at DESC LIMIT 1");
                $stmt->bind_param("s", $row['username']);
                $stmt->execute();
                $scoreResult = $stmt->get_result();

                if ($scoreResult && $score = $scoreResult->fetch_assoc()) {
                    echo "<td>{$score['score']} / {$score['total_questions']}</td>";
                } else {
                    echo "<td>--</td>";
                }

                $stmt->close();
            }
            ?>
        </tr>
    <?php endwhile; ?>

    <!-- Total users row -->
    <tr style="font-weight: bold; background-color: #444; color: #fff;">
        <td colspan="<?= 4 + count($subjects) ?>">Total Users: <?= $serial - 1 ?></td>
    </tr>

<?php else: ?>
    <tr><td colspan="<?= 4 + count($subjects) ?>">No users found.</td></tr>
<?php endif; ?>
</table>

<?php $conn->close(); ?>

</body>
</html>
