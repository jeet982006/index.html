const questions = [
    {
        questions: "What does HTML stand for?",
        answers: [
            { text: "Home Tool Markup Language", correct: false },
            { text: "Hyperlinks and Text Markup Language", correct: false },
            { text: "Hyper Text Markup Language", correct: true },
            { text: "Hyper Tool Modern Language", correct: false },
        ]
    },
    {
        questions: "Who is the inventor of HTML?",
        answers: [
            { text: "Brendan Eich", correct: false },
            { text: "Tim Berners-Lee", correct: true },
            { text: "Bill Gates", correct: false },
            { text: "Larry Page", correct: false },
        ]
    },
    {
        questions: "What is the correct HTML element for the largest heading?",
        answers: [
            { text: "< heading >", correct: false },
            { text: "< h6 >", correct: false },
            { text: "< h1 >", correct: true },
            { text: "< head >", correct: false },
        ]
    },
    {
        questions: "Which tag is used to create a hyperlink in HTML?",
        answers: [
            { text: "< hyperlink >", correct: false },
            { text: "< a >", correct: true },
            { text: "< link >", correct: false },
            { text: "< href >", correct: false },
        ]
    },
    {
        questions: "What is the correct HTML element for inserting a line break?",
        answers: [
            { text: "< br >", correct: true },
            { text: "< lb >", correct: false },
            { text: "< break >", correct: false },
            { text: "< hr >", correct: false },
        ]
    },
    {
        questions: "Which HTML attribute specifies an alternate text for an image?",
        answers: [
            { text: "title", correct: false },
            { text: "alt", correct: true },
            { text: "src", correct: false },
            { text: "longdesc", correct: false },
        ]
    },
    {
        questions: "What does the < title > tag do in HTML?",
        answers: [
            { text: "Sets the page background", correct: false },
            { text: "Displays text in the body", correct: false },
            { text: "Defines the page title in the browser tab", correct: true },
            { text: "Creates a heading", correct: false },
        ]
    },
    {
        questions: "Which tag is used to define a table row?",
        answers: [
            { text: "< td >", correct: false },
            { text: "< th >", correct: false },
            { text: "< table >", correct: false },
            { text: "< tr >", correct: true },
        ]
    },
    {
        questions: "Which tag is used to create an unordered list in HTML?",
        answers: [
            { text: "< ul >", correct: true },
            { text: "< ol >", correct: false },
            { text: "< li >", correct: false },
            { text: "< list >", correct: false },
        ]
    },
    {
        questions: "Which HTML tag is used to display an image on a webpage?",
        answers: [
            { text: "< img >", correct: true },
            { text: "< image >", correct: false },
            { text: "< pic >", correct: false },
            { text: "< src >", correct: false },
        ]
    },
];
const startBtn = document.getElementById("start-btn");
const quizContainer = document.getElementById("quiz-container");
const questionsElement = document.getElementById("question");
const answerButtons = document.getElementById("answer-buttons");
const nextButton = document.getElementById("next-btn");
const timerElement = document.getElementById("timer");

let currentQuestionIndex = 0;
let score = 0;
let timer;
let timeLeft = 15;
let selectedQuestions = [];

startBtn.addEventListener("click", () => {
    const count = parseInt(document.getElementById("question-count").value);
    if (isNaN(count) || count < 1) {
        alert("Please enter a valid number of questions.");
        return;
    }

    selectedQuestions = allQuestions.slice(0, count); // use random selection if needed
    setupElement.style.display = "none";
    quizElement.style.display = "block";
    startQuiz();
});

function startQuiz() {
    currentQuestionIndex = 0;
    score = 0;
    nextButton.innerHTML = "Next";
    nextButton.style.display = "none";
    showQuestion();
}

function startTimer() {
    timeLeft = 15;
    timerElement.innerHTML = `Time left: ${timeLeft}s`;
    timerElement.classList.remove("warning"); // in case you added blinking effect
    timer = setInterval(() => {
        timeLeft--;
        timerElement.innerHTML = `Time left: ${timeLeft}s`;

        if (timeLeft <= 5) {
            timerElement.classList.add("warning"); // optional blinking effect
        }

        if (timeLeft === 0) {
            clearInterval(timer);
            showCorrectAnswerOnTimeout();
        }
    }, 1000);
}

function resetTimer() {
    clearInterval(timer);
    timerElement.innerHTML = "";
    timerElement.classList.remove("warning");
}

function showCorrectAnswerOnTimeout() {
    // Disable all answer buttons and highlight correct answer
    Array.from(answerButtons.children).forEach(button => {
        if (button.dataset.correct === "true") {
            button.classList.add("correct");
        }
        button.disabled = true;
    });
    nextButton.style.display = "block"; // Show next button
}

function showQuestion() {
    resetState();
    resetTimer();
    let currentQuestion = questions[currentQuestionIndex];
    let questionNo = currentQuestionIndex + 1;
    questionsElement.innerHTML = questionNo + ". " + currentQuestion.questions;

    currentQuestion.answers.forEach(answer => {
        const button = document.createElement("button");
        button.innerHTML = answer.text;
        button.classList.add("btn");
        answerButtons.appendChild(button);
        if (answer.correct) {
            button.dataset.correct = answer.correct;
        }
        button.addEventListener("click", selectAnswer);
    });
    startTimer(); // Start the timer here
}

function resetState() {
    nextButton.style.display = "none";
    while (answerButtons.firstChild) {
        answerButtons.removeChild(answerButtons.firstChild);
    }
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
    resetTimer(); // stop timer when answered
    nextButton.style.display = "block";
}
function showScore() {
    resetState();
    resetTimer();
    questionsElement.innerHTML = `You scored ${score} out of ${questions.length}!`;
    nextButton.innerHTML = "Play Again";
    nextButton.style.display = "block";
}

function handleNextButton() {
    currentQuestionIndex++;
    if (currentQuestionIndex < questions.length) {
        showQuestion();
    } else {
        showScore();
    }
}

nextButton.addEventListener("click", () => {
    if (currentQuestionIndex < questions.length) {
        handleNextButton();
    } else {
        startQuiz();
    }
})
startQuiz();
