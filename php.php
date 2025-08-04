<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_score') {
    require_once 'config.php';

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($_SESSION['username']) || !$data) {
        http_response_code(403);
        echo "Unauthorized or invalid data.";
        exit;
    }

    $username = $_SESSION['username'];
    $score = intval($data['score']);
    $total = intval($data['total']);

    $stmt = $conn->prepare("INSERT INTO php_scores (username, score, total_questions) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $username, $score, $total);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Score saved successfully!";
    exit;
}
// Handle leaderboard fetch
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_GET['action'])
    && $_GET['action'] === 'top10') {

    require_once 'config.php';

    $sql = "
        SELECT username,
               SUM(score) AS total_score,
               SUM(total_questions) AS total_questions
        FROM php_scores
        GROUP BY username
        ORDER BY total_score DESC
        LIMIT 10
    ";
    $result = $conn->query($sql);

    $top = [];
    while ($row = $result->fetch_assoc()) {
        $top[] = [
            'username'        => $row['username'],
            'total_score'     => (int) $row['total_score'],
            'total_questions' => (int) $row['total_questions']
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($top);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Php Quiz</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- Header -->
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

  <!-- Navigation Menu -->
  <nav class="menu" id="main-menu">
    <a href="index.php">HOME</a>
    <a href="html.php">HTML</a>
    <a href="java.php">JAVA</a>
    <a href="python.php">PYTHON</a>
    <a href="#" class="active">PHP</a>
    <a href="c++.php">C++</a>
  </nav>

  <!-- Quiz Entry Section -->
  <div class="quiz-container">
    <h2>Enter Quiz Number</h2>
    <input type="number" id="quizNumber" min="1" max="20" />
    <button id="startBtn">Start Quiz</button>
    <p id="errorMsg" style="color: red;"></p>
  </div>

  <!-- Quiz Section -->
  <div class="app">
    <div class="heading-row">
      <h1>Quiz Php</h1>
      <span id="timer">Time left: 15s</span>
    </div>
    <div class="quiz" style="display: none;">
      <h2 id="question">Question</h2>
      <div id="answer-buttons">
        <!-- Buttons will be dynamically inserted -->
      </div>
      <button id="next-btn" style="display:none;">Next</button>
    </div>
  </div>

  <!-- Result Section (Score + Leaderboard) -->
  <div class="leaderboard" style="display: none;">
    <h2>🎉 Your Score: <span id="finalScore"></span></h2>
    <h3>🏆 Top 10 Users</h3>

    <?php if (!isset($_SESSION['username'])): ?>
      <p style="color: #f44336; font-weight: bold; margin-bottom: 10px;">
        🔒 To see your name in the leaderboard, please 
        <a href="register.php" style="color: #03dac6;">Register</a> or 
        <a href="login.php" style="color: #03dac6;">Login</a>.
      </p>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>Rank</th>
          <th>Username</th>
          <th>Total Score</th>
          <th>Total Question</th>
        </tr>
      </thead>
      <tbody id="leaderboard-body">
        <tr><td colspan="4">Loading...</td></tr>
      </tbody>
    </table>
    <button onclick="location.reload()" class="btn">Play Again</button>
  </div>

  <!-- Footer -->
  <footer class="site-footer">
    <p>&copy; 2025 QuizWeb. All rights reserved.</p>
  </footer>
<script>
    // Load questions function
function loadQuestionsForQuiz() {
    return [
        {
            type: "mcq",
            question: "What does PHP stand for?",
            answers: [
                { text: "Private Home Page", correct: false },
                { text: "Personal Hypertext Processor", correct: false },
                { text: "PHP: Hypertext Preprocessor", correct: true },
                { text: "Preprocessed Hypertext Program", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which symbol is used to declare a variable in PHP?",
            answers: [
                { text: "#", correct: false },
                { text: "$", correct: true },
                { text: "@", correct: false },
                { text: "&", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of the following is the correct way to start a PHP block?",
            answers: [
                { text: "<script>", correct: false },
                { text: "< ?php", correct: true },
                { text: "< ?php>", correct: false },
                { text: "<php>", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which function is used to output text in PHP?",
            answers: [
                { text: "write()", correct: false },
                { text: "echo", correct: true },
                { text: "printText()", correct: false },
                { text: "display()", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What will `strlen('Hello')` return?",
            answers: [
                { text: "4", correct: false },
                { text: "5", correct: true },
                { text: "6", correct: false },
                { text: "Error", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which operator is used to concatenate strings in PHP?",
            answers: [
                { text: "+", correct: false },
                { text: "&", correct: false },
                { text: ".", correct: true },
                { text: "concat", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which global variable contains form data sent with the POST method?",
            answers: [
                { text: "$_POST", correct: true },
                { text: "$POST", correct: false },
                { text: "$_FORM", correct: false },
                { text: "$_REQUEST", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which superglobal holds session data?",
            answers: [
                { text: "$_COOKIE", correct: false },
                { text: "$_SESSION", correct: true },
                { text: "$_POST", correct: false },
                { text: "$GLOBALS", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to create a function in PHP?",
            answers: [
                { text: "def", correct: false },
                { text: "function", correct: true },
                { text: "func", correct: false },
                { text: "lambda", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "How do you end a PHP statement?",
            answers: [
                { text: ":", correct: false },
                { text: ".", correct: false },
                { text: ";", correct: true },
                { text: "/", correct: false }
            ]
        },
        {
            type: "truefalse",
            question: "PHP is a server-side scripting language.",
            correct: true
        },
        {
            type: "truefalse",
            question: "PHP code must be compiled before execution.",
            correct: false
        },
        {
            type: "truefalse",
            question: "Variables in PHP are case-sensitive.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The echo statement can be used to display output in PHP.",
            correct: true
        },
        {
            type: "truefalse",
            question: "PHP can be embedded inside HTML.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The isset() function is used to check if a variable is null.",
            correct: false // it checks if a variable is set and not null
        },
        {
            type: "truefalse",
            question: "PHP supports object-oriented programming.",
            correct: true
        },
        {
            type: "truefalse",
            question: "All PHP variables must start with a hash (#) symbol.",
            correct: false
        },
        {
            type: "truefalse",
            question: "Sessions are used to store data across multiple pages.",
            correct: true
        },
        {
            type: "truefalse",
            question: "PHP files must always be named with a .php extension.",
            correct: false
        },
        {
            type: "fillblank",
            question: "PHP files usually have the extension _____ .",
            correctAnswer: ".php"
        },
        {
            type: "fillblank",
            question: "To start a PHP script, use the tag _____ .",
            correctAnswer: "<?php"
        },
        {
            type: "fillblank",
            question: "To print text in PHP, use the _____ statement.",
            correctAnswer: "echo"
        },
        {
            type: "fillblank",
            question: "The _____ function checks if a variable is set and not null.",
            correctAnswer: "isset"
        },
        {
            type: "fillblank",
            question: "To define a constant in PHP, use the _____ function.",
            correctAnswer: "define"
        },
        {
            type: "fillblank",
            question: "The _____ function is used to get the length of a string.",
            correctAnswer: "strlen"
        },
        {
            type: "fillblank",
            question: "The _____ array contains values sent via the URL.",
            correctAnswer: "$_GET"
        },
        {
            type: "fillblank",
            question: "The _____ keyword is used to declare a function in PHP.",
            correctAnswer: "function"
        },
        {
            type: "fillblank",
            question: "The _____ function is used to start a session in PHP.",
            correctAnswer: "session_start"
        },
        {
            type: "fillblank",
            question: "The operator _____ is used to concatenate strings in PHP.",
            correctAnswer: "."
        }
    ];
}

// DOM elements
const quizInput = document.getElementById("quizNumber");
const startBtn = document.getElementById("startBtn");
const errorMsg = document.getElementById("errorMsg");
const quizContainer = document.querySelector(".quiz-container");
const app = document.querySelector(".app");
const quizDiv = document.querySelector(".quiz");
const questionEl = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextBtn = document.getElementById("next-btn");
const timerElement = document.getElementById("timer");

let questions = [];
let selectedQuestions = [];
let currentQuestionIndex = 0;
let score = 0;
let timer;
let timeLeft = 15;

// Limit input max value to 50
quizInput.addEventListener("input", () => {
    let value = parseInt(quizInput.value, 10);
    if (value > 20) {
        quizInput.value = 20;
    } else if (value < 1) {
        quizInput.value = "";
    }
});

startBtn.addEventListener("click", () => {
    const quizNum = parseInt(quizInput.value, 10);

    if (!quizNum || quizNum < 1 || quizNum > 20) {
        errorMsg.textContent = "Please enter a valid quiz number between 1 and 20.";
        return;
    }

    errorMsg.textContent = "";
    questions = loadQuestionsForQuiz();

    // Select quizNum random questions (or all if less)
    selectedQuestions = getRandomQuestions(questions, quizNum);

    currentQuestionIndex = 0;
    score = 0;

    // Hide start UI, show quiz UI
    quizContainer.style.display = "none";
    app.style.display = "block";
    quizDiv.style.display = "block";

    nextBtn.style.display = "none";
    showQuestion();
});

function getRandomQuestions(arr, n) {
    const shuffled = arr.slice().sort(() => 0.5 - Math.random());
    return shuffled.slice(0, n);
}

function showQuestion() {
    resetState();
    resetTimer();

    if (currentQuestionIndex >= selectedQuestions.length) {
        showScore();
        return;
    }

    const current = selectedQuestions[currentQuestionIndex];
    questionEl.textContent = `${currentQuestionIndex + 1}. ${current.question}`;

    if (current.type === "mcq") {
        current.answers.forEach(ans => {
            const btn = document.createElement("button");
            btn.classList.add("btn");
            btn.textContent = ans.text;
            btn.dataset.correct = ans.correct;
            btn.onclick = selectAnswer;
            answerButtons.appendChild(btn);
        });
    } else if (current.type === "truefalse") {
        ["True", "False"].forEach(val => {
            const btn = document.createElement("button");
            btn.classList.add("btn");
            btn.textContent = val;
            btn.dataset.correct = (val.toLowerCase() === String(current.correct).toLowerCase());
            btn.onclick = selectAnswer;
            answerButtons.appendChild(btn);
        });
    } else if (current.type === "fillblank") {
        const input = document.createElement("input");
        input.type = "text";
        input.id = "fillInput";
        input.placeholder = "Type your answer here";
        input.classList.add("btn");
        answerButtons.appendChild(input);

        const submitBtn = document.createElement("button");
        submitBtn.textContent = "Submit";
        submitBtn.classList.add("btn");

        submitBtn.onclick = () => {
            clearInterval(timer); // ✅ Stop timer when submitted

            const userAnswer = input.value.trim().toLowerCase();
            const correct = current.correctAnswer.toLowerCase();

            if (userAnswer === correct) {
                score++;
                input.classList.add("correct");
            } else {
                input.classList.add("incorrect");
                input.value = `${userAnswer}  (Correct: ${current.correctAnswer})`;
            }

            input.disabled = true;
            submitBtn.disabled = true;
            nextBtn.style.display = "block";
        };

        answerButtons.appendChild(submitBtn);
    }

    nextBtn.style.display = "none";
    startTimer();
}


function resetState() {
    nextBtn.style.display = "none";
    while (answerButtons.firstChild) {
        answerButtons.removeChild(answerButtons.firstChild);
    }
}

function startTimer() {
    timeLeft = 15;
    timerElement.textContent = `Time left: ${timeLeft}s`;
    timerElement.classList.remove("warning");
    timer = setInterval(() => {
        timeLeft--;
        timerElement.textContent = `Time left: ${timeLeft}s`;

        if (timeLeft <= 5) {
            timerElement.classList.add("warning");
        }

        if (timeLeft <= 0) {
            clearInterval(timer);
            showCorrectAnswerOnTimeout();
        }
    }, 1000);
}

function resetTimer() {
    clearInterval(timer);
    timerElement.textContent = "";
    timerElement.classList.remove("warning");
}

function showCorrectAnswerOnTimeout() {
    const current = selectedQuestions[currentQuestionIndex];

    if (current.type === "fillblank") {
        const input = document.getElementById("fillInput");
        if (input && !input.disabled) {
            input.classList.add("incorrect");
            input.value = ` (Correct: ${current.correctAnswer})`;
            input.disabled = true;
        }

        const submitBtn = answerButtons.querySelector("button");
        if (submitBtn) {
            submitBtn.disabled = true;
        }

    } else {
        Array.from(answerButtons.children).forEach(button => {
            if (button.dataset.correct === "true") {
                button.classList.add("correct");
            }
            button.disabled = true;
        });
    }

    nextBtn.style.display = "block";
}


function selectAnswer(e) {
    clearInterval(timer);

    const selectedBtn = e.target;
    const isCorrect = selectedBtn.dataset.correct === "true";

    if (isCorrect) {
        selectedBtn.classList.add("correct");
        score++;
    } else {
        selectedBtn.classList.add("incorrect");
    }

    Array.from(answerButtons.children).forEach(button => {
        if (button.dataset.correct === "true") {
            button.classList.add("correct");
        }
        button.disabled = true;
    });

    nextBtn.style.display = "block";
    resetTimer();
}

function showScore() {
    resetState();
    resetTimer();

    document.getElementById("finalScore").innerText = `${score} / ${selectedQuestions.length}`;

    // Hide quiz UI
    document.querySelector(".app").style.display = "none";

    // Show leaderboard section
    document.querySelector(".leaderboard").style.display = "block";

    // Save score to server using php.php
    fetch("php.php?action=save_score", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: score,
            total: selectedQuestions.length
        })
    }).then(() => {
        // Fetch top 10 after saving
        fetch("php.php?action=top10")
  .then(response => {
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    return response.json();
  })
  .then(data => {
    const tbody = document.getElementById("leaderboard-body");
    tbody.innerHTML = "";

    data.forEach((user, index) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${index + 1}</td>
        <td>${user.username}</td>
        <td>${user.total_score}</td>
        <td>${user.total_questions}</td>
      `;
      tbody.appendChild(row);
    });
  })
  .catch(error => {
    console.error("Leaderboard fetch error:", error);
    document.getElementById("leaderboard-body").innerHTML = `
      <tr><td colspan="4">Error loading leaderboard.</td></tr>
    `;
  });
    }).catch(error => {
        console.error("Score saving failed:", error);
    });
}


nextBtn.addEventListener("click", () => {
    if (nextBtn.textContent === "Play Again") {
        // Reset UI to start screen
        quizContainer.style.display = "block";
        app.style.display = "none";
        nextBtn.textContent = "Next";
        quizInput.value = "";
        errorMsg.textContent = "";
        return;
    }

    currentQuestionIndex++;
    if (currentQuestionIndex < selectedQuestions.length) {
        showQuestion();
    } else {
        showScore();
    }
});
document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Prevent the default form submission

    const form = e.target;
    const formData = new FormData(form);

    fetch("login.php", {
        method: "POST",
        body: formData,
    })
        .then((response) => response.text())
        .then((data) => {
            const msgDiv = document.getElementById("message");
            msgDiv.innerHTML = data;
            msgDiv.style.color = data.includes("successful") ? "green" : "red";
        })
        .catch((error) => {
            document.getElementById("message").textContent = "An error occurred.";
        });
});
// memu
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("menu-toggle");
    const menu = document.getElementById("main-menu");

    if (toggleBtn && menu) {
        toggleBtn.addEventListener("click", () => {
            menu.classList.toggle("show");
        });

        // Optional: Hide menu when link is clicked on small screens
        const links = menu.querySelectorAll("a");
        links.forEach(link => {
            link.addEventListener("click", () => {
                menu.classList.remove("show");
            });
        });
    }
});
</script>
</body>
</html>     
