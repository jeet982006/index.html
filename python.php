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

    $stmt = $conn->prepare("INSERT INTO python_scores (username, score, total_questions) VALUES (?, ?, ?)");
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
        FROM python_scores
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
  <title>Python Quiz</title>
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
    <a href="#" class="active">PYTHON</a>
    <a href="php.php">PHP</a>
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
      <h1>Quiz Python</h1>
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
          <th>Total Questions</th>
        </tr>
      </thead>
      <tbody id="leaderboard-body">
        <tr><td colspan="4">Loading...</td></tr>
      </tbody>
    </table>
    <div>
        <button class="btn" onclick="location.reload()">Play Again</button>
        <button id="viewAttemptsBtn" class="btn">View Attempted Questions</button>
    </div>
</div>

<!-- Attempted Questions Popup -->
<div id="attemptedPopup" style="display:none;">
  <div class="popup-content">
    <span class="close-btn" id="closeAttempted">&times;</span>
    <h3 class="attempt-title">📋 Your Attempted Questions</h3>
    <ol id="attemptList"></ol>
  </div>
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
            question: "Who developed Python?",
            answers: [
                { text: "Guido van Rossum", correct: true },
                { text: "Dennis Ritchie", correct: false },
                { text: "Bjarne Stroustrup", correct: false },
                { text: "James Gosling", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What is the extension of a Python file?",
            answers: [
                { text: ".py", correct: true },
                { text: ".python", correct: false },
                { text: ".pyt", correct: false },
                { text: ".pt", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to define a function in Python?",
            answers: [
                { text: "function", correct: false },
                { text: "def", correct: true },
                { text: "fun", correct: false },
                { text: "define", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of the following is used to insert comments in Python?",
            answers: [
                { text: "//", correct: false },
                { text: "#", correct: true },
                { text: "/* */", correct: false },
                { text: "--", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What is the output of: print(type([]))",
            answers: [
                { text: "<class 'list'>", correct: true },
                { text: "<class 'array'>", correct: false },
                { text: "<list>", correct: false },
                { text: "<array>", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of these data types is immutable in Python?",
            answers: [
                { text: "list", correct: false },
                { text: "set", correct: false },
                { text: "tuple", correct: true },
                { text: "dict", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which function is used to get user input in Python 3?",
            answers: [
                { text: "scan()", correct: false },
                { text: "input()", correct: true },
                { text: "get()", correct: false },
                { text: "read()", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used for loop in Python?",
            answers: [
                { text: "loop", correct: false },
                { text: "repeat", correct: false },
                { text: "for", correct: true },
                { text: "iterate", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What is the result of: 2 ** 3 in Python?",
            answers: [
                { text: "6", correct: false },
                { text: "8", correct: true },
                { text: "9", correct: false },
                { text: "5", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to handle exceptions?",
            answers: [
                { text: "catch", correct: false },
                { text: "except", correct: true },
                { text: "error", correct: false },
                { text: "throw", correct: false }
            ]
        },
        {
            type: "truefalse",
            question: "Python is a statically typed language.",
            correct: false
        },
        {
            type: "truefalse",
            question: "Indentation is important in Python syntax.",
            correct: true
        },
        {
            type: "truefalse",
            question: "In Python, '==' is used for assignment.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The 'elif' keyword stands for 'else if' in Python.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Python supports both procedural and object-oriented programming.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Lists in Python are mutable.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The 'lambda' keyword is used to create anonymous functions.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Python does not support recursion.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The 'global' keyword is used to access global variables.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Python was released after Java.",
            correct: true
        },
        {
            type: "fillblank",
            question: "The keyword used to define a function in Python is _____ .",
            correctAnswer: "def"
        },
        {
            type: "fillblank",
            question: "To include comments in Python, we use the _____ symbol.",
            correctAnswer: "#"
        },
        {
            type: "fillblank",
            question: "To raise an exception in Python, use the _____ keyword.",
            correctAnswer: "raise"
        },
        {
            type: "fillblank",
            question: "The _____ function is used to display output in Python.",
            correctAnswer: "print"
        },
        {
            type: "fillblank",
            question: "The _____ function is used to read user input in Python.",
            correctAnswer: "input"
        },
        {
            type: "fillblank",
            question: "A collection of key-value pairs is stored in a _____ in Python.",
            correctAnswer: "dictionary"
        },
        {
            type: "fillblank",
            question: "The keyword _____ is used to define a class in Python.",
            correctAnswer: "class"
        },
        {
            type: "fillblank",
            question: "Python files are saved with the _____ extension.",
            correctAnswer: ".py"
        },
        {
            type: "fillblank",
            question: "The _____ keyword is used to handle exceptions.",
            correctAnswer: "except"
        },
        {
            type: "fillblank",
            question: "To create a loop that runs a set number of times, use the _____ loop.",
            correctAnswer: "for"
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

function escapeTags(str) {
  return String(str).replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

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

    // ✅ Validate quiz number input
    if (isNaN(quizNum) || quizNum < 1 || quizNum > 20) {
        errorMsg.textContent = "⚠ Please enter a valid quiz number between 1 and 20.";
        quizInput.focus();
        return;
    }

    errorMsg.textContent = "";

    // ✅ Load all questions
    questions = loadQuestionsForQuiz();

    // ✅ Select quizNum random questions
    selectedQuestions = getRandomQuestions(questions, quizNum);

    // ✅ Reset quiz state
    currentQuestionIndex = 0;
    score = 0;
    attemptedQuestions = []; // reset attempted questions for review

    // ✅ Switch UI to quiz view
    quizContainer.style.display = "none";
    app.style.display = "block";
    quizDiv.style.display = "block";
    nextBtn.style.display = "none";

    // ✅ Start first question
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
        question: escapeTags(current.question),
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
            question: escapeTags(current.question),
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
            question: escapeTags(current.question),
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

    // ✅ Store attempt for review popup
    const current = selectedQuestions[currentQuestionIndex];
    let correctAnswerText = "";
    if (current.type === "mcq") {
        correctAnswerText = current.answers.find(a => a.correct)?.text || "";
    } else if (current.type === "truefalse") {
        correctAnswerText = current.correct ? "True" : "False";
    }

    attemptedQuestions.push({
        question: current.question,
        userAnswer: selectedBtn.textContent,
        correctAnswer: correctAnswerText
    });

    nextBtn.style.display = "block";
    resetTimer();
}

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
        const userAnsDisplay = item.userAnswer && item.userAnswer.trim() !== ""
            ? escapeTags(item.userAnswer)
            : "No answer";

        const correctAnsDisplay = escapeTags(item.correctAnswer || "");
        const isCorrect = userAnsDisplay.toLowerCase() === correctAnsDisplay.toLowerCase();

        const li = document.createElement("li");
        li.innerHTML = `
            <div class="attempt-question">
                <strong>${index + 1}.</strong> ${escapeTags(item.question)}
            </div>
            <div class="attempt-answer ${isCorrect ? 'correct' : 'incorrect'}">
                Your Answer: ${userAnsDisplay}
            </div>
            <div class="correct-answer">
                Correct Answer: ${correctAnsDisplay}
            </div>
        `;
        attemptList.appendChild(li);
    });

    // Save score to server using python.php
    fetch("python.php?action=save_score", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: score,
            total: selectedQuestions.length
        })
    }).then(() => {
        // Fetch top 10 after saving
        fetch("python.php?action=top10")
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
