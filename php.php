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
    <a href="index.php">HOME</a>
    <a href="html.php">HTML</a>
    <a href="java.php">JAVA</a>
    <a href="python.php">PYTHON</a>
    <a href="#" class="active">PHP</a>
    <a href="c++.php">C++</a>
  </nav>

  <div class="quiz-container">
    <h2>Enter Quiz Number</h2>
    <input type="number" id="quizNumber" min="1" max="20" />
    <button id="startBtn">Start Quiz</button>
    <p id="errorMsg" style="color: red;"></p>
  </div>

  <div class="app">
    <div class="heading-row">
      <h1>Quiz Php</h1>
      <span id="timer">Time left: 15s</span>
    </div>
    <div class="quiz" style="display: none;">
      <h2 id="question">Question</h2>
      <div id="answer-buttons">
        </div>
      <button id="next-btn" style="display:none;">Next</button>
    </div>
  </div>

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
    <div>
      <button onclick="location.reload()" class="btn">Play Again</button>
      <button id="viewAttemptsBtn" class="btn">View Attempted Questions</button>
    </div>
  </div>
 
   <div id="attemptedPopup">
    <div class="popup-content">
      <span class="close-btn" id="closeAttempted">&times;</span>
      <h3 class="attempt-title">📋 Your Attempted Questions</h3>
      <ol id="attemptList"></ol>
    </div>
  </div>

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
            correctAnswer: "< ?php"
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
let attemptedQuestions = [];

// Escape helper
function escapeHTML(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;');
}

// Limit input max value to 20
quizInput.addEventListener("input", () => {
    let value = parseInt(quizInput.value, 10);
    if (value > 20) {
        quizInput.value = 20;
    } else if (value < 1) {
        quizInput.value = "";
    }
});

startBtn.addEventListener('click', () => {
  const quizNum = parseInt(quizInput.value, 10);

  if (isNaN(quizNum) || quizNum < 1 || quizNum > 20) {
    errorMsg.textContent = '⚠ Please enter a valid quiz number between 1 and 20.';
    quizInput.focus();
    return;
  }

  errorMsg.textContent = '';

  // Load questions and pick N random
  questions = loadQuestionsForQuiz();
  selectedQuestions = getRandomQuestions(questions, quizNum);

  // Reset state
  currentQuestionIndex = 0;
  score = 0;
  attemptedQuestions = [];

  // Switch UI
  quizContainer.style.display = 'none';
  app.style.display = 'block';
  quizDiv.style.display = 'block';
  nextBtn.style.display = 'none';

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

    const userAnswerRaw = input.value.trim();
    const userAnswer = userAnswerRaw.toLowerCase();
    const correct = current.correctAnswer.toLowerCase();

    // Store attempt for review popup
    attemptedQuestions.push({
        question: escapeHTML(current.question),
        userAnswer: userAnswerRaw || "No answer",
        correctAnswer: current.correctAnswer
    });

    if (userAnswer === correct) {
        score++;
        input.classList.add("correct");
    } else {
        input.classList.add("incorrect");
        input.value = `${userAnswerRaw}  (Correct: ${current.correctAnswer})`;
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
        let userAns = "";

        if (input && !input.disabled) {
            userAns = input.value.trim();
            input.classList.add("incorrect");
            input.value = `(Correct: ${current.correctAnswer})`;
            input.disabled = true;
        }

        // Save attempt
        attemptedQuestions.push({
            question: escapeHTML(current.question),
            userAnswer: userAns || "No answer",
            correctAnswer: current.correctAnswer
        });

        const submitBtn = answerButtons.querySelector("button");
        if (submitBtn) submitBtn.disabled = true;

    } else {
        let correctText = "";

        if (current.type === "mcq" && current.answers) {
            const correctAns = current.answers.find(a => a.correct);
            correctText = correctAns ? correctAns.text : "";
        } else if (current.type === "truefalse") {
            correctText = current.correct ? "True" : "False";
        }

        // Mark correct answer
        Array.from(answerButtons.children).forEach(button => {
            if (button.dataset.correct === "true") {
                button.classList.add("correct");
            }
            button.disabled = true;
        });

        // Save attempt
        attemptedQuestions.push({
            question: escapeHTML(current.question),
            userAnswer: "No answer",
            correctAnswer: correctText
        });
    }

    nextBtn.style.display = "block";
}

function selectAnswer(e) {
    clearInterval(timer);

    const selectedBtn = e.target;
    const isCorrect = selectedBtn.dataset.correct === "true";
    const current = selectedQuestions[currentQuestionIndex];

    attemptedQuestions.push({
        question: escapeHTML(current.question),
        userAnswer: selectedBtn.textContent,
        correctAnswer: current.type === "mcq" || current.type === "truefalse"
            ? (current.answers
                ? current.answers.find(ans => ans.correct)?.text
                : (current.correct ? "True" : "False"))
            : current.correctAnswer
    });


    // Mark selected answer
    if (isCorrect) {
        selectedBtn.classList.add("correct");
        score++;
    } else {
        selectedBtn.classList.add("incorrect");
    }

    // Highlight correct answer
    Array.from(answerButtons.children).forEach(button => {
        if (button.dataset.correct === "true") {
            button.classList.add("correct");
        }
        button.disabled = true;
    });


    nextBtn.style.display = "block";
    resetTimer();
}

// Function to escape HTML tags for display
function escapeTags(str) {
    return String(str)
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}

function showScore() {
    resetState();
    resetTimer();

    document.getElementById("finalScore").innerText = `${score} / ${selectedQuestions.length}`;

    // Hide quiz UI
    document.querySelector(".app").style.display = "none";

    // Show leaderboard section
    document.querySelector(".leaderboard").style.display = "block";

    // ✅ Populate attempted questions list
    const attemptList = document.getElementById("attemptList");
    attemptList.innerHTML = "";

    attemptedQuestions.forEach((item, index) => {
        // Use raw for checking, escape for display
        const rawUserAns = item.userAnswer && item.userAnswer.trim() !== "" ? item.userAnswer : "No answer";
        const rawCorrectAns = item.correctAnswer || "";

        const isCorrect = rawUserAns.trim().toLowerCase() === rawCorrectAns.trim().toLowerCase();

        const li = document.createElement("li");
        li.innerHTML = `
            <div class="attempt-question">
                <strong>${index + 1}.</strong> ${item.question} 
            </div>
            <div class="attempt-answer ${isCorrect ? 'correct' : 'incorrect'}">
                Your Answer: ${escapeTags(rawUserAns)}
            </div>
            <div class="correct-answer">
                Correct Answer: ${escapeTags(rawCorrectAns)}
            </div>
        `;
        attemptList.appendChild(li);
    });

    // Save score to server
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
/* ===========================
   Login form (if present)
   =========================== */
   const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(loginForm);
    fetch("login.php", { method: "POST", body: formData })
      .then(r => r.text())
      .then(data => {
        const msgDiv = document.getElementById("message");
        if (msgDiv) {
          msgDiv.innerHTML = data;
          msgDiv.style.color = data.includes("successful") ? "green" : "red";
        }
      })
      .catch(() => {
        const msgDiv = document.getElementById("message");
        if (msgDiv) msgDiv.textContent = "An error occurred.";
      });
  });
}

// =======================
// ATTEMPTED QUESTIONS POPUP
// =======================
document.addEventListener("DOMContentLoaded", function () {
  const attemptBtn = document.getElementById("viewAttemptsBtn");
  const popup = document.getElementById("attemptedPopup");
  const closeBtn = document.getElementById("closeAttempted");

  if (attemptBtn) {
    attemptBtn.addEventListener("click", () => {
      popup.style.display = "flex"; // open popup
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      popup.style.display = "none"; // close popup
    });
  }

  // Close popup if clicked outside content
  popup.addEventListener("click", (e) => {
    if (e.target === popup) {
      popup.style.display = "none";
    }
  });
});
</script>
</body>
</html>
