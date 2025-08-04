<?php
session_start();
require_once 'config.php'; // This must define $conn (MySQLi connection)

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Subject to table mapping
$subjects = [
    'HTML' => 'html_scores',
    'Java' => 'java_scores',
    'Python' => 'python_scores',
    'PHP' => 'php_scores',
    'C++' => 'cpp_scores'
];

// Fetch user scores from each subject table
$userScores = [];

foreach ($subjects as $subject => $table) {
    $stmt = $conn->prepare("SELECT score, total_questions, submitted_at FROM $table WHERE username = ? ORDER BY submitted_at DESC");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $userScores[$subject] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($username) ?>'s Quiz History</title>
    <link rel="stylesheet" href="style2.css">
    
</head>
<body>
    
<!-- Navigation Buttons -->
<div class="nav-buttons">
    <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
    <div class="nav-links">
        <a href="index.php" class="nav-button">🏠 Home</a>
        <a href="logout.php" class="nav-button logout-button">🔒 Logout</a>
    </div>
</div>
<h2>Your Quiz History</h2>

<?php
$grandTotalScore = 0;
$grandTotalQuestions = 0;
?>
<?php foreach ($userScores as $subject => $scores): ?>
    <h3><?= $subject ?> Quiz</h3>
    <?php if (count($scores) > 0): ?>
        <table>
            <tr>
                <th>Score</th>
                <th>Total Questions</th>
                <th>Submitted At</th>
            </tr>
            <?php
            $totalScore = 0;
            $totalQuestions = 0;
            foreach ($scores as $row):
                $totalScore += $row['score'];
                $totalQuestions += $row['total_questions'];
            ?>
                <tr>
                    <td><?= $row['score'] ?></td>
                    <td><?= $row['total_questions'] ?></td>
                    <td><?= $row['submitted_at'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background-color: #050000ff;">
                <td>Total=<?= $totalScore ?></td>
                <td>Total=<?= $totalQuestions ?></td>
                <td>-</td>
            </tr>
        </table>
    <?php else: ?>
        <p>No records for <?= $subject ?> quiz.</p>
    <?php endif; ?>
<?php endforeach; ?>


</body>
</html>
