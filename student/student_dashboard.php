<?php
ob_start();

include_once '../includes/db_config.php';
include_once '../includes/auth.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

check_role('student');

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

$student_group_id = null;

try {
    // Assuming 'group_number' in users table is group_id integer
    $group_sql = "SELECT group_number FROM users WHERE id = ?";
    $stmt = $pdo->prepare($group_sql);
    $stmt->execute([$student_id]);
    $group_number = $stmt->fetchColumn();

    if ($group_number) {
        $student_group_id = (int) $group_number;
    } else {
        $error = "You are not assigned to any group. Please contact your teacher.";
    }
} catch (PDOException $e) {
    $error .= " Database error fetching group ID: " . $e->getMessage();
}

$assigned_quizzes = [];

if ($student_group_id && !$error) {
    try {
        $quiz_sql = "
            SELECT DISTINCT
                q.quiz_id,
                q.title,
                qg.assignment_date,
                s.subject_name
            FROM quizzes q
            JOIN quiz_groups qg ON q.quiz_id = qg.quiz_id
            JOIN subjects s ON q.subject_id = s.subject_id
            WHERE qg.group_id = ?
            ORDER BY qg.assignment_date DESC, q.title ASC
        ";

        $stmt = $pdo->prepare($quiz_sql);
        $stmt->execute([$student_group_id]);
        $assigned_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error .= " Failed to load quizzes: " . $e->getMessage();
    }
}

unset($pdo);
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #1e7e34; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header h1 { margin: 0; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #1c7430; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #17692a; }
        .container { max-width: 800px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #1e7e34; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .quiz-list { list-style: none; padding: 0; }
        .quiz-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 6px; background-color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .quiz-details { flex-grow: 1; }
        .quiz-title { font-size: 1.2em; font-weight: bold; color: #333; }
        .quiz-info { font-size: 0.9em; color: #666; margin-top: 5px; }
        
        .start-button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: bold; transition: background-color 0.3s; }
        .start-button:hover { background-color: #0056b3; }
        .no-quiz { background-color: #ffc107; padding: 15px; border-radius: 4px; color: #333; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard 👋</h1>
        <div>
            <a href="student_results.php">My Results</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Available Quizzes</h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <p>You are viewing quizzes assigned to Group ID: <strong><?php echo htmlspecialchars($student_group_id ?? 'N/A'); ?></strong></p>

        <?php if (empty($assigned_quizzes)): ?>
            <div class="no-quiz">
                <p>There are currently no quizzes assigned to your group.</p>
            </div>
        <?php else: ?>
            <ul class="quiz-list">
                <?php foreach ($assigned_quizzes as $quiz): ?>
                    <li class="quiz-item">
                        <div class="quiz-details">
                            <div class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                            <div class="quiz-info">
                                Subject: <?php echo htmlspecialchars($quiz['subject_name']); ?> | Assigned Date: <?php echo htmlspecialchars($quiz['assignment_date']); ?>
                            </div>
                        </div>
                        <a href="take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['quiz_id']); ?>" class="start-button">Start Quiz</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
