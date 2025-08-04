<?php
session_start();
require_once 'config.php';

// Function to fetch top 10 users by total score and total questions
function getTop10TotalScore($conn, $table) {
    $stmt = $conn->prepare("
        SELECT username, 
               SUM(score) AS total_score, 
               SUM(total_questions) AS total_questions
        FROM $table
        GROUP BY username
        ORDER BY total_score DESC
        LIMIT 10
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $top = [];
    while ($row = $result->fetch_assoc()) {
        $top[] = $row;
    }
    $stmt->close();
    return $top;
}

// Subject list and DB tables
$subjects = [
    'HTML'   => 'html_scores',
    'Java'   => 'java_scores',
    'Python' => 'python_scores',
    'PHP'    => 'php_scores',
    'C++'    => 'cpp_scores'
];

// Load leaderboards
$leaderboards = [];
foreach ($subjects as $name => $table) {
    $leaderboards[$name] = getTop10TotalScore($conn, $table);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Quiz Leaderboards</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<header class="site-header">
  <div class="header-container">
    <h1>Quiz For Computer Languages</h1>
    <button id="menu-toggle" aria-label="Toggle Menu">&#9776;</button>
    <nav id="nav-links">
      <?php if (isset($_SESSION['username'])): ?>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<nav class="menu" id="main-menu">
  <a href="#" class="active">HOME</a>
  <a href="html.php">HTML</a>
  <a href="java.php">JAVA</a>
  <a href="python.php">PYTHON</a>
  <a href="php.php">PHP</a>
  <a href="c++.php">C++</a>
</nav>

<div class="container">
  <h2>🏆 Top 10 Leaderboards</h2>

  <!-- First row -->
  <div class="row">
    <?php foreach (['HTML', 'Java', 'Python', 'PHP', 'C++'] as $subject): ?>
      <div class="table-box">
        <h3><?= $subject ?> Leaderboard</h3>
        <table>
          <thead>
            <tr>
              <th>Rank</th>
              <th>Username</th>
              <th>Total Score</th>
              <th>Total Questions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($leaderboards[$subject])): ?>
              <?php foreach ($leaderboards[$subject] as $i => $user): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= htmlspecialchars($user['username']) ?></td>
                  <td><?= $user['total_score'] ?></td>
                  <td><?= $user['total_questions'] ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4">No records</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<footer class="site-footer">
  <p>&copy; 2025 QuizWeb. All rights reserved.</p>
</footer>

<script>
document.getElementById('menu-toggle').addEventListener('click', function() {
  const nav = document.getElementById('nav-links');
  nav.classList.toggle('show');
});
</script>

</body>
</html>
