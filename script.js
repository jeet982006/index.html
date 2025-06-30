// Load questions function
function loadQuestionsForQuiz() {
    return [
        {
            question: "What does HTML stand for?",
            answers: [
                { text: "Home Tool Markup Language", correct: false },
                { text: "Hyperlinks and Text Markup Language", correct: false },
                { text: "Hyper Text Markup Language", correct: true },
                { text: "Hyper Tool Modern Language", correct: false },
            ]
        },
        {
            question: "Who is the inventor of HTML?",
            answers: [
                { text: "Brendan Eich", correct: false },
                { text: "Tim Berners-Lee", correct: true },
                { text: "Bill Gates", correct: false },
                { text: "Larry Page", correct: false },
            ]
        },
        {
            question: "What is the correct HTML element for the largest heading?",
            answers: [
                { text: "< heading >", correct: false },
                { text: "< h6 >", correct: false },
                { text: "< h1 >", correct: true },
                { text: "< head >", correct: false },
            ]
        },
        {
            question: "Which tag is used to create a hyperlink in HTML?",
            answers: [
                { text: "< hyperlink >", correct: false },
                { text: "< a >", correct: true },
                { text: "< link >", correct: false },
                { text: "< href >", correct: false },
            ]
        },
        {
            question: "What is the correct HTML element for inserting a line break?",
            answers: [
                { text: "< br >", correct: true },
                { text: "< lb >", correct: false },
                { text: "< break >", correct: false },
                { text: "< hr >", correct: false },
            ]
        },
        {
            question: "Which HTML attribute specifies an alternate text for an image?",
            answers: [
                { text: "title", correct: false },
                { text: "alt", correct: true },
                { text: "src", correct: false },
                { text: "longdesc", correct: false },
            ]
        },
        {
            question: "What does the < title > tag do in HTML?",
            answers: [
                { text: "Sets the page background", correct: false },
                { text: "Displays text in the body", correct: false },
                { text: "Defines the page title in the browser tab", correct: true },
                { text: "Creates a heading", correct: false },
            ]
        },
        {
            question: "Which tag is used to define a table row?",
            answers: [
                { text: "< td >", correct: false },
                { text: "< th >", correct: false },
                { text: "< table >", correct: false },
                { text: "< tr >", correct: true },
            ]
        },
        {
            question: "Which tag is used to create an unordered list in HTML?",
            answers: [
                { text: "< ul >", correct: true },
                { text: "< ol >", correct: false },
                { text: "< li >", correct: false },
                { text: "< list >", correct: false },
            ]
        },
        {
            question: "Which HTML tag is used to display an image on a webpage?",
            answers: [
                { text: "< img >", correct: true },
                { text: "< image >", correct: false },
                { text: "< pic >", correct: false },
                { text: "< src >", correct: false },
            ]
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
    if (value > 50) {
        quizInput.value = 50;
    } else if (value < 1) {
        quizInput.value = "";
    }
});

startBtn.addEventListener("click", () => {
    const quizNum = parseInt(quizInput.value, 10);

    if (!quizNum || quizNum < 1 || quizNum > 50) {
        errorMsg.textContent = "Please enter a valid quiz number between 1 and 50.";
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

    const currentQuestion = selectedQuestions[currentQuestionIndex];

    questionEl.textContent = `${currentQuestionIndex + 1}. ${currentQuestion.question}`;

    currentQuestion.answers.forEach(answer => {
        const button = document.createElement("button");
        button.classList.add("btn");
        button.textContent = answer.text;
        button.dataset.correct = answer.correct ? "true" : "false";
        button.addEventListener("click", selectAnswer);
        answerButtons.appendChild(button);
    });

    startTimer();
    nextBtn.style.display = "none";
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
    Array.from(answerButtons.children).forEach(button => {
        if (button.dataset.correct === "true") {
            button.classList.add("correct");
        }
        button.disabled = true;
    });
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
    questionEl.textContent = `You scored ${score} out of ${selectedQuestions.length}!`;
    nextBtn.textContent = "Play Again";
    nextBtn.style.display = "block";
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
