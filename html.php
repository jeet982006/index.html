<?php
session_start();

// Save score if action=save_score
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

    $stmt = $conn->prepare("INSERT INTO html_scores (username, score, total_questions) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $username, $score, $total);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Score saved successfully!";
    exit;
}

// Fetch top 10 leaderboard if action=top10
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'top10') {
    require_once 'config.php';

    $sql = "SELECT username, MAX(score) AS high_score FROM html_scores GROUP BY username ORDER BY high_score DESC LIMIT 10";
    $result = $conn->query($sql);

    $top = [];
    while ($row = $result->fetch_assoc()) {
        $top[] = $row;
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
  <title>Html Quiz</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- Header -->
  <header class="site-header">
    <div class="header-container">
      <h1>Quiz For Computer Languages</h1>
      <button id="menu-toggle" aria-label="Toggle Menu">&#9776;</button>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    </div>
  </header>

  <!-- Navigation Menu -->
  <nav class="menu" id="main-menu">
    <a href="index.php">HOME</a>
    <a href="#" class="active">HTML</a>
    <a href="java.php">JAVA</a>
    <a href="python.php">PYTHON</a>
    <a href="php.php">PHP</a>
    <a href="c++.php">C++</a>
  </nav>

  <!-- Quiz Entry Section -->
  <div class="quiz-container">
    <h2>Enter Quiz Number</h2>
    <input type="number" id="quizNumber" min="1" max="50" />
    <button id="startBtn">Start Quiz</button>
    <p id="errorMsg" style="color: red;"></p>
  </div>

  <!-- Quiz Section -->
  <div class="app">
    <div class="heading-row">
      <h1>Quiz Html</h1>
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
          <th>Score</th>
        </tr>
      </thead>
      <tbody id="leaderboard-body">
        <tr><td colspan="3">Loading...</td></tr>
      </tbody>
    </table>
    <button onclick="location.reload()" class="btn">Play Again</button>
  </div>

  <!-- Footer -->
  <footer class="site-footer">
    <p>&copy; 2025 QuizWeb. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script>
    // Load questions function
function loadQuestionsForQuiz() {
    return [
        {
            type: "mcq",
            question: "What does HTML stand for?",
            answers: [
                { text: "Home Tool Markup Language", correct: false },
                { text: "Hyperlinks and Text Markup Language", correct: false },
                { text: "Hyper Text Markup Language", correct: true },
                { text: "Hyper Tool Modern Language", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "Who is the inventor of HTML?",
            answers: [
                { text: "Brendan Eich", correct: false },
                { text: "Tim Berners-Lee", correct: true },
                { text: "Bill Gates", correct: false },
                { text: "Larry Page", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "What is the correct HTML element for the largest heading?",
            answers: [
                { text: "< heading >", correct: false },
                { text: "< h6 >", correct: false },
                { text: "< h1 >", correct: true },
                { text: "< head >", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "Which tag is used to create a hyperlink in HTML?",
            answers: [
                { text: "< hyperlink >", correct: false },
                { text: "< a >", correct: true },
                { text: "< link >", correct: false },
                { text: "< href >", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "What is the correct HTML element for inserting a line break?",
            answers: [
                { text: "< br >", correct: true },
                { text: "< lb >", correct: false },
                { text: "< break >", correct: false },
                { text: "< hr >", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "Which HTML attribute specifies an alternate text for an image?",
            answers: [
                { text: "title", correct: false },
                { text: "alt", correct: true },
                { text: "src", correct: false },
                { text: "longdesc", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "What does the < title > tag do in HTML?",
            answers: [
                { text: "Sets the page background", correct: false },
                { text: "Displays text in the body", correct: false },
                { text: "Defines the page title in the browser tab", correct: true },
                { text: "Creates a heading", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "Which tag is used to define a table row?",
            answers: [
                { text: "< td >", correct: false },
                { text: "< th >", correct: false },
                { text: "< table >", correct: false },
                { text: "< tr >", correct: true },
            ]
        },
        {
            type: "mcq",
            question: "Which tag is used to create an unordered list in HTML?",
            answers: [
                { text: "< ul >", correct: true },
                { text: "< ol >", correct: false },
                { text: "< li >", correct: false },
                { text: "< list >", correct: false },
            ]
        },
        {
            type: "mcq",
            question: "Which HTML tag is used to display an image on a webpage?",
            answers: [
                { text: "< img >", correct: true },
                { text: "< image >", correct: false },
                { text: "< pic >", correct: false },
                { text: "< src >", correct: false },
            ]
        },
        {
            type: "truefalse",
            question: "HTML can be used to style web pages.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The <iframe> tag is used to embed another webpage within the current page.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The 'placeholder' attribute is used to define default text in a text field.",
            correct: true
        },
        {
            type: "truefalse",
            question: "HTML5 supports native audio and video elements.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The <marquee> tag is used in modern HTML5 for animations.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The 'disabled' attribute can be applied to input fields.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The <main> element represents the dominant content of a document.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The <caption> tag defines a title or explanation for a table.",
            correct: true
        },
        {
            type: "truefalse",
            question: "HTML is case-sensitive when writing tags.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The <span> tag is used to group block-level elements.",
            correct: false
        },
        {
            type: "fillblank",
            question: "The tag used to insert a line break in HTML is _____ .",
            correctAnswer: "<br>"
        },
        {
            type: "fillblank",
            question: "The tag used to embed a video in HTML5 is _____ .",
            correctAnswer: "<video>"
        },
        {
            type: "fillblank",
            question: "The tag used to embed an audio file is _____ .",
            correctAnswer: "<audio>"
        },
        {
            type: "fillblank",
            question: "To define a navigation menu, we use the _____ tag.",
            correctAnswer: "<nav>"
        },
        {
            type: "fillblank",
            question: "The _____ attribute specifies where to send the form data.",
            correctAnswer: "action"
        },
        {
            type: "fillblank",
            question: "To create a comment in HTML, we use _____ .",
            correctAnswer: "<!-- -->"
        },
        {
            type: "fillblank",
            question: "The _____ tag is used to define emphasized text.",
            correctAnswer: "<em>"
        },
        {
            type: "fillblank",
            question: "The _____ tag is used to define important text in HTML.",
            correctAnswer: "<strong>"
        },
        {
            type: "fillblank",
            question: "The _____ element defines a section in a document.",
            correctAnswer: "<section>"
        },
        {
            type: "fillblank",
            question: "The _____ attribute specifies a short hint in an input field.",
            correctAnswer: "placeholder"
        },
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

    // Save score to server using html.php
    fetch("html.php?action=save_score", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: score,
            total: selectedQuestions.length
        })
    }).then(() => {
        // Fetch top 10 after saving
        fetch("html.php?action=top10")
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById("leaderboard-body");
                tbody.innerHTML = "";

                data.forEach((user, index) => {
                    const row = document.createElement("tr");
                    row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${user.username}</td>
                        <td>${user.high_score}</td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                document.getElementById("leaderboard-body").innerHTML = `
                    <tr><td colspan="3">Error loading leaderboard.</td></tr>
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
