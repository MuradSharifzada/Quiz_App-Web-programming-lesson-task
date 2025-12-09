<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header("Location: ../login.php"); exit;
}
$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$error = '';
if ($quiz_id <= 0) $error = "Invalid quiz ID.";

try {
    if (!$error) {
        $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$quiz) $error = "Quiz not found.";
    }
    if (!$error) {
        $stmt = $pdo->prepare("SELECT question_id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE quiz_id = ? ORDER BY question_id");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$questions) $error = "No questions for this quiz.";
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head><title><?php echo htmlspecialchars($quiz['title'] ?? 'Quiz'); ?></title></head>
<body>
<?php if ($error): ?>
    <div style="color:red;"><?php echo htmlspecialchars($error); ?></div>
<?php else: ?>
    <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
    <form action="submit_quiz.php" method="post">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <?php foreach ($questions as $i => $q): ?>
            <fieldset>
                <legend><?php echo ($i+1) . ". " . htmlspecialchars($q['question_text']); ?></legend>
                <label><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="A" required> A. <?php echo htmlspecialchars($q['option_a']); ?></label><br>
                <label><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="B"> B. <?php echo htmlspecialchars($q['option_b']); ?></label><br>
                <label><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="C"> C. <?php echo htmlspecialchars($q['option_c']); ?></label><br>
                <label><input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="D"> D. <?php echo htmlspecialchars($q['option_d']); ?></label><br>
            </fieldset>
        <?php endforeach; ?>
        <button type="submit">Submit Quiz</button>
    </form>
<?php endif; ?>
</body>
</html>
