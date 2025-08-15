<?php
session_start();

// Handle score saving
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

    $stmt = $conn->prepare("INSERT INTO java_scores (username, score, total_questions) VALUES (?, ?, ?)");
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
        FROM java_scores
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
  <title>Java Quiz</title>
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
    <a href="#" class="active">JAVA</a>
    <a href="python.php">PYTHON</a>
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
      <h1>Quiz Java</h1>
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

  <!-- Scripts -->
  <script>
    // Load questions function
function loadQuestionsForQuiz() {
    return [
        {
            type: "mcq",
            question: "Which company developed Java?",
            answers: [
                { text: "Sun Microsystems", correct: true },
                { text: "Microsoft", correct: false },
                { text: "Google", correct: false },
                { text: "IBM", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to inherit a class in Java?",
            answers: [
                { text: "this", correct: false },
                { text: "super", correct: false },
                { text: "extends", correct: true },
                { text: "implements", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which method is the entry point of a Java program?",
            answers: [
                { text: "start()", correct: false },
                { text: "main()", correct: true },
                { text: "init()", correct: false },
                { text: "run()", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of the following is not a Java keyword?",
            answers: [
                { text: "class", correct: false },
                { text: "interface", correct: false },
                { text: "unsigned", correct: true },
                { text: "enum", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of these is a wrapper class in Java?",
            answers: [
                { text: "int", correct: false },
                { text: "Integer", correct: true },
                { text: "float", correct: false },
                { text: "char", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What does JVM stand for?",
            answers: [
                { text: "Java Virtual Machine", correct: true },
                { text: "Java Verified Mode", correct: false },
                { text: "Java Visual Manager", correct: false },
                { text: "Java Virtual Method", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which data type is used to create a variable that should store text?",
            answers: [
                { text: "myString", correct: false },
                { text: "String", correct: true },
                { text: "Txt", correct: false },
                { text: "str", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which symbol is used to import packages in Java?",
            answers: [
                { text: "#", correct: false },
                { text: "@", correct: false },
                { text: "import", correct: true },
                { text: "$", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of the following is not an access modifier in Java?",
            answers: [
                { text: "public", correct: false },
                { text: "private", correct: false },
                { text: "internal", correct: true },
                { text: "protected", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What is used to handle exceptions in Java?",
            answers: [
                { text: "throw/try/catch", correct: true },
                { text: "if/else", correct: false },
                { text: "switch", correct: false },
                { text: "define", correct: false }
            ]
        },
        {
            type: "truefalse",
            question: "Java supports multiple inheritance through classes.",
            correct: false
        },
        {
            type: "truefalse",
            question: "The 'final' keyword is used to declare constants in Java.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Java code is compiled into machine code directly.",
            correct: false
        },
        {
            type: "truefalse",
            question: "Java is platform-independent due to the JVM.",
            correct: true
        },
        {
            type: "truefalse",
            question: "String in Java is a primitive data type.",
            correct: false
        },
        {
            type: "truefalse",
            question: "Java arrays are zero-indexed.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The 'this' keyword refers to the current object in Java.",
            correct: true
        },
        {
            type: "truefalse",
            question: "The 'static' keyword can be used with variables and methods.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Java does not support method overloading.",
            correct: false
        },
        {
            type: "truefalse",
            question: "A Java interface can contain implemented methods.",
            correct: true  // Since Java 8 (default/static methods)
        },
        {
            type: "fillblank",
            question: "The entry point of a Java application is the _____ method.",
            correctAnswer: "main"
        },
        {
            type: "fillblank",
            question: "Java code is compiled into _____ bytecode.",
            correctAnswer: "class"
        },
        {
            type: "fillblank",
            question: "To create a new object in Java, we use the _____ keyword.",
            correctAnswer: "new"
        },
        {
            type: "fillblank",
            question: "To inherit a class in Java, we use the _____ keyword.",
            correctAnswer: "extends"
        },
        {
            type: "fillblank",
            question: "A _____ is a blueprint for creating objects in Java.",
            correctAnswer: "class"
        },
        {
            type: "fillblank",
            question: "An exception in Java can be handled using the _____ block.",
            correctAnswer: "try"
        },
        {
            type: "fillblank",
            question: "The _____ keyword is used to define a constant in Java.",
            correctAnswer: "final"
        },
        {
            type: "fillblank",
            question: "The _____ keyword refers to the current object.",
            correctAnswer: "this"
        },
        {
            type: "fillblank",
            question: "The _____ method is used to print output in Java.",
            correctAnswer: "System.out.println"
        },
        {
            type: "fillblank",
            question: "A Java file must be saved with the _____ extension.",
            correctAnswer: ".java"
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

  if (!quizNum || quizNum < 1 || quizNum > 20) {
    errorMsg.textContent = "Please enter a valid quiz number between 1 and 20.";
    return;
  }

  errorMsg.textContent = "";
  questions = loadQuestionsForQuiz();

  selectedQuestions = getRandomQuestions(questions, quizNum);

  currentQuestionIndex = 0;
  score = 0;
  attemptedQuestions = []; // ✅ reset attempts each run

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
    current.answers.forEach((ans) => {
      const btn = document.createElement("button");
      btn.classList.add("btn");
      btn.textContent = ans.text;
      btn.dataset.correct = ans.correct;
      btn.onclick = selectAnswer;
      answerButtons.appendChild(btn);
    });
  } else if (current.type === "truefalse") {
    ["True", "False"].forEach((val) => {
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
      clearInterval(timer);
      const userAnswer = input.value.trim();
      const correct = String(current.correctAnswer).trim();

      // Save attempt
      attemptedQuestions.push({
        question: current.question,
        userAnswer: userAnswer || "No answer",
        correctAnswer: correct
      });

      if (userAnswer.toLowerCase() === correct.toLowerCase()) {
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
    let userAns = "";
    if (input && !input.disabled) {
      userAns = input.value.trim();
      input.classList.add("incorrect");
      input.value = ` (Correct: ${current.correctAnswer})`;
      input.disabled = true;
    }
    attemptedQuestions.push({
      question: current.question,
      userAnswer: userAns || "No answer",
      correctAnswer: current.correctAnswer
    });
    const submitBtn = answerButtons.querySelector("button");
    if (submitBtn) submitBtn.disabled = true;

  } else {
    // Find correct label
    let correctText = "";
    if (current.type === "mcq" && current.answers) {
      const ok = current.answers.find(a => a.correct);
      correctText = ok ? ok.text : "";
    } else if (current.type === "truefalse") {
      correctText = current.correct ? "True" : "False";
    }

    Array.from(answerButtons.children).forEach((button) => {
      if (button.dataset.correct === "true") button.classList.add("correct");
      button.disabled = true;
    });

    attemptedQuestions.push({
      question: current.question,
      userAnswer: "No answer",
      correctAnswer: correctText || current.correctAnswer || ""
    });
  }

  nextBtn.style.display = "block";
}

function selectAnswer(e) {
  clearInterval(timer);
  const selectedBtn = e.target;
  const isCorrect = selectedBtn.dataset.correct === "true";
  const current = selectedQuestions[currentQuestionIndex];

  // Record attempt
  attemptedQuestions.push({
    question: current.question,
    userAnswer: selectedBtn.textContent,
    correctAnswer: (current.type === "mcq" || current.type === "truefalse")
      ? (current.answers
          ? (current.answers.find(ans => ans.correct)?.text || "")
          : (current.correct ? "True" : "False"))
      : (current.correctAnswer || "")
  });

  if (isCorrect) {
    selectedBtn.classList.add("correct");
    score++;
  } else {
    selectedBtn.classList.add("incorrect");
  }

  Array.from(answerButtons.children).forEach((button) => {
    if (button.dataset.correct === "true") button.classList.add("correct");
    button.disabled = true;
  });

  nextBtn.style.display = "block";
  resetTimer();
}
function handleNextClick() {
  currentQuestionIndex++;
  if (currentQuestionIndex < selectedQuestions.length) {
    showQuestion();
  } else {
    showScore();
  }
}

function showScore() {
  resetState();
  resetTimer();

  document.getElementById("finalScore").innerText =
    `${score} / ${selectedQuestions.length}`;

  document.querySelector(".app").style.display = "none";
  document.querySelector(".leaderboard").style.display = "block";

  // Populate Attempted popup
  const attemptList = document.getElementById("attemptList");
  attemptList.innerHTML = "";
  attemptedQuestions.forEach((item, index) => {
    const rawUser = item.userAnswer && item.userAnswer.trim() !== "" ? item.userAnswer : "No answer";
    const rawCorrect = item.correctAnswer || "";
    const isCorrect = rawUser.trim().toLowerCase() === rawCorrect.trim().toLowerCase();

    const li = document.createElement("li");
    li.innerHTML = `
      <div class="attempt-question">
        <strong>${index + 1}.</strong> ${escapeTags(item.question)}
      </div>
      <div class="attempt-answer ${isCorrect ? 'correct' : 'incorrect'}">
        Your Answer: ${escapeTags(rawUser)}
      </div>
      <div class="correct-answer">
        Correct Answer: ${escapeTags(rawCorrect)}
      </div>
    `;
    attemptList.appendChild(li);
  });

  // Save score then load leaderboard
  fetch("java.php?action=save_score", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ score: score, total: selectedQuestions.length })
  })
  .then(() => fetch("java.php?action=top10"))
  .then(res => {
    if (!res.ok) throw new Error(`HTTP error: ${res.status}`);
    return res.json();
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
  .catch(() => {
    document.getElementById("leaderboard-body").innerHTML =
      `<tr><td colspan="4">Error loading leaderboard.</td></tr>`;
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

    nextBtn.onclick = handleNextClick;
});

/* ===========================
   Attempted Popup
   =========================== */
   document.addEventListener("DOMContentLoaded", function () {
  const attemptBtn = document.getElementById("viewAttemptsBtn");
  const popup = document.getElementById("attemptedPopup");
  const closeBtn = document.getElementById("closeAttempted");

  if (attemptBtn) attemptBtn.addEventListener("click", () => { popup.style.display = "flex"; });
  if (closeBtn) closeBtn.addEventListener("click", () => { popup.style.display = "none"; });
  if (popup) popup.addEventListener("click", (e) => { if (e.target === popup) popup.style.display = "none"; });

});

/* ===========================
   Login form (if present)
   =========================== */
const loginForm = document.getElementById("loginForm");
if (loginForm) {
  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(loginForm);
    fetch("login.php", { method:"POST", body:formData })
      .then(r => r.text())
      .then(html => {
        const msg = document.getElementById("message");
        if (msg) {
          msg.innerHTML = html;
          msg.style.color = html.includes("successful") ? "green" : "red";
        }
      })
      .catch(() => {
        const msg = document.getElementById("message");
        if (msg) msg.textContent = "An error occurred.";
      });
  });
}

</script>
</body>
</html> 
