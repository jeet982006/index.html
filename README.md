<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Quiz App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="menu">
    <a href="#" class="active">HTML</a>
    <a href="#">CSS</a>
    <a href="#">JavaScript</a>
    <a href="#">Python</a>
    <a href="#">PHP</a>
    <a href="#">SQL</a>
  </div>
  <div class="quiz-container">
    <h2>Enter Quiz Number</h2>
    <input type="number" id="quizNumber" placeholder="Enter Quiz No.." />
    <button onclick="startQuiz()">Start</button>
    <p id="message"></p>
    <div id="quizArea"></div>
  </div>
  <div class="app">
    <h1>Simpal Quiz Html</h1>
    <div class="quiz">
      <p id="timer">Time left: 15s</p>
      <h2 id="question">Question</h2>
      <div id="answer-buttons">
        <button class="btn">Answer 1</button>
        <button class="btn">Answer 2</button>
        <button class="btn">Answer 3</button>
        <button class="btn">Answer 4</button>
      </div>
      <button id="next-btn">Next</button>
    </div>
  </div>
  <script src="script.js"></script>

</body>

</html>
