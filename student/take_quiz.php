<?php
// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$error = '';
$quiz = [];
$questions = [];

if ($quiz_id <= 0) {
    $error = "Invalid quiz ID provided.";
}

try {
    if (!$error) {
        // Fetch Quiz Details
        $quiz_sql = "SELECT title, duration_minutes FROM quizzes WHERE quiz_id = ?";
        $quiz_stmt = $pdo->prepare($quiz_sql);
        $quiz_stmt->execute([$quiz_id]);
        $quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quiz) {
            $error = "Quiz not found or not available.";
        }
    }

    if (!$error) {
        // Fetch Questions
        $questions_sql = "
            SELECT 
                question_id, question_text,
                option_a, option_b, option_c, option_d
            FROM 
                questions
            WHERE 
                quiz_id = ?
            ORDER BY question_id ASC
        ";
        $questions_stmt = $pdo->prepare($questions_sql);
        $questions_stmt->execute([$quiz_id]);
        $questions = $questions_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            $error = "This quiz has no questions defined yet.";
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $quiz ? htmlspecialchars($quiz['title']) : 'Start Quiz'; ?> - Student View</title>
    <style>
        /* Basic styling omitted for brevity (you can reuse your CSS from above) */
        body { font-family: Arial, sans-serif; background-color: #eef1f5; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .question-card { background: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 6px; border-left: 4px solid #0d6efd; }
        .question-card h3 { margin-top: 0; color: #333; }
        .options label { display: block; margin: 10px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; transition: background-color 0.2s, border-color 0.2s; }
        .options label:hover { background-color: #e6f7ff; border-color: #0d6efd; }
        .options input[type="radio"] { margin-right: 10px; }
        .submit-btn { background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 4px; font-size: 1.1em; cursor: pointer; transition: background-color 0.3s; width: 100%; margin-top: 20px; }
        .submit-btn:hover { background-color: #218838; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div style="color:red; font-weight:bold;"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <form action="submit_quiz.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-card">
                        <h3><?php echo $index + 1; ?>. <?php echo htmlspecialchars($q['question_text']); ?></h3>
                        <div class="options">
                            <label>
                                <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="A" required>
                                A. <?php echo htmlspecialchars($q['option_a']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="B">
                                B. <?php echo htmlspecialchars($q['option_b']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="C">
                                C. <?php echo htmlspecialchars($q['option_c']); ?>
                            </label>
                            <label>
                                <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="D">
                                D. <?php echo htmlspecialchars($q['option_d']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="submit-btn">Submit Quiz</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
