<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = "Invalid request.";
}

$quiz_id = $_POST['quiz_id'] ?? 0;
$answers = $_POST['answers'] ?? [];

if (!$error && ($quiz_id <= 0 || empty($answers))) {
    $error = "Missing quiz_id or answers.";
}

try {
    if (!$error) {
        // Validate answer options
        $valid_opts = ['A', 'B', 'C', 'D'];
        foreach ($answers as $qid => $ans) {
            $answers[$qid] = strtoupper(trim($ans));
            if (!in_array($answers[$qid], $valid_opts, true)) {
                throw new Exception("Invalid answer option submitted.");
            }
        }
        $question_ids = array_map('intval', array_keys($answers));
        if (count($question_ids) === 0) throw new Exception("No answers submitted.");

        // Verify these questions belong to the quiz
        $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE question_id IN ($placeholders) AND quiz_id = ?");
        $stmt->execute(array_merge($question_ids, [$quiz_id]));
        if ($stmt->fetchColumn() != count($question_ids)) {
            throw new Exception("Submitted questions do not match quiz.");
        }

        // Get correct answers
        $stmt = $pdo->prepare("SELECT question_id, UPPER(correct_option) as correct_option FROM questions WHERE question_id IN ($placeholders)");
        $stmt->execute($question_ids);
        $corrects = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $score = 0;
        foreach ($answers as $qid => $ans) {
            if (isset($corrects[$qid]) && $ans === $corrects[$qid]) {
                $score++;
            }
        }

        // Save results
        $stmt = $pdo->prepare("INSERT INTO results (student_id, quiz_id, score, total_points, submission_time) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$student_id, $quiz_id, $score, count($question_ids)]);
        $result_id = $pdo->lastInsertId();

        // Save each submitted answer with student_answer and submitted_answer_value
        $insertAnsStmt = $pdo->prepare("INSERT INTO submitted_answers (result_id, question_id, submitted_answer_value, student_answer, is_correct) VALUES (?, ?, ?, ?, ?)");

        foreach ($answers as $qid => $ans) {
            $is_correct = (isset($corrects[$qid]) && $ans === $corrects[$qid]) ? 1 : 0;
            $insertAnsStmt->execute([$result_id, $qid, $ans, $ans, $is_correct]);
        }

        // Redirect to results page
        header("Location: view_results.php?quiz_id=$quiz_id&submitted=true");
        exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head><title>Quiz Submission Error</title></head>
<body>
<h1>Error</h1>
<p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<a href="quiz_form.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>">Back to Quiz</a>
</body>
</html>
