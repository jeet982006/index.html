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

    $stmt = $conn->prepare("INSERT INTO cpp_scores (username, score, total_questions) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $username, $score, $total);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    echo "Score saved successfully!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'top10') {
    require_once 'config.php';

    $sql = "SELECT username, MAX(score) AS high_score FROM cpp_scores GROUP BY username ORDER BY high_score DESC LIMIT 10";
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
  <title>C++ Quiz</title>
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
    <a href="html.php">HTML</a>
    <a href="java.php">JAVA</a>
    <a href="python.php">PYTHON</a>
    <a href="php.php">PHP</a>
    <a href="#" class="active">C++</a>
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
<script>
    // Load questions function
function loadQuestionsForQuiz() {
    return [
        {
            type: "mcq",
            question: "Who developed C++?",
            answers: [
                { text: "Dennis Ritchie", correct: false },
                { text: "Bjarne Stroustrup", correct: true },
                { text: "James Gosling", correct: false },
                { text: "Guido van Rossum", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which of the following is the correct syntax to read input in C++?",
            answers: [
                { text: "cin << variable;", correct: false },
                { text: "input(variable);", correct: false },
                { text: "cin >> variable;", correct: true },
                { text: "read >> variable;", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which symbol is used for single-line comments in C++?",
            answers: [
                { text: "//", correct: true },
                { text: "/*", correct: false },
                { text: "#", correct: false },
                { text: "<!--", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which header file is required for `cout` and `cin`?",
            answers: [
                { text: "#include <stdio.h>", correct: false },
                { text: "#include <iostream>", correct: true },
                { text: "#include <stdlib.h>", correct: false },
                { text: "#include <conio.h>", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which is the correct way to declare a class in C++?",
            answers: [
                { text: "class MyClass {}", correct: false },
                { text: "MyClass class {}", correct: false },
                { text: "class MyClass {};", correct: true },
                { text: "def class MyClass {}", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to create an object in C++?",
            answers: [
                { text: "define", correct: false },
                { text: "class", correct: false },
                { text: "new", correct: true },
                { text: "make", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "What does OOP stand for?",
            answers: [
                { text: "Object-Oriented Programming", correct: true },
                { text: "Open Object Protocol", correct: false },
                { text: "Operating Oriented Program", correct: false },
                { text: "Only One Program", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which access specifier allows access to all classes?",
            answers: [
                { text: "private", correct: false },
                { text: "protected", correct: false },
                { text: "public", correct: true },
                { text: "global", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which keyword is used to inherit a class?",
            answers: [
                { text: "inherits", correct: false },
                { text: "extends", correct: false },
                { text: "public", correct: true },
                { text: "child", correct: false }
            ]
        },
        {
            type: "mcq",
            question: "Which function is automatically called when an object is created?",
            answers: [
                { text: "destructor", correct: false },
                { text: "constructor", correct: true },
                { text: "init()", correct: false },
                { text: "create()", correct: false }
            ]
        },
        {
            type: "truefalse",
            question: "C++ supports both procedural and object-oriented programming.",
            correct: true
        },
        {
            type: "truefalse",
            question: "C++ was developed before the C language.",
            correct: false
        },
        {
            type: "truefalse",
            question: "C++ supports function overloading.",
            correct: true
        },
        {
            type: "truefalse",
            question: "Private members of a class can be accessed directly outside the class.",
            correct: false
        },
        {
            type: "truefalse",
            question: "C++ programs must have a `main()` function.",
            correct: true
        },
        {
            type: "truefalse",
            question: "`#include` is a preprocessor directive in C++.",
            correct: true
        },
        {
            type: "truefalse",
            question: "In C++, `int` is used to declare a floating-point number.",
            correct: false
        },
        {
            type: "truefalse",
            question: "`new` is used to allocate memory dynamically in C++.",
            correct: true
        },
        {
            type: "truefalse",
            question: "A destructor in C++ starts with a tilde (~) symbol.",
            correct: true
        },
        {
            type: "truefalse",
            question: "C++ supports multiple inheritance.",
            correct: true
        },
        {
            type: "fillblank",
            question: "The keyword used to create a class in C++ is _____ .",
            correctAnswer: "class"
        },
        {
            type: "fillblank",
            question: "The function where program execution starts is _____ .",
            correctAnswer: "main"
        },
        {
            type: "fillblank",
            question: "To output text in C++, we use _____ .",
            correctAnswer: "cout"
        },
        {
            type: "fillblank",
            question: "To take input in C++, we use _____ .",
            correctAnswer: "cin"
        },
        {
            type: "fillblank",
            question: "To include libraries, we use the _____ directive.",
            correctAnswer: "#include"
        },
        {
            type: "fillblank",
            question: "A member function with the same name as the class is called a _____ .",
            correctAnswer: "constructor"
        },
        {
            type: "fillblank",
            question: "To allocate memory dynamically, we use the _____ keyword.",
            correctAnswer: "new"
        },
        {
            type: "fillblank",
            question: "To destroy an object and free memory, we use the _____ keyword.",
            correctAnswer: "delete"
        },
        {
            type: "fillblank",
            question: "The operator used for scope resolution is _____ .",
            correctAnswer: "::"
        },
        {
            type: "fillblank",
            question: "Functions with the same name but different parameters are called _____ functions.",
            correctAnswer: "overloaded"
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

    // Save score to server using html.php
    fetch("c++.php?action=save_score", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            score: score,
            total: selectedQuestions.length
        })
    }).then(() => {
        // Fetch top 10 after saving
        fetch("c++.php?action=top10")
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
