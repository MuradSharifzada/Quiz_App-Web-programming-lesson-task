<?php
// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Start session and check authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Ensure only teachers can access this page
check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- PART 1: Handle Quiz Deletion ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_quiz') {
    $quiz_to_delete_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
    
    if ($quiz_to_delete_id) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // 1. Delete associated questions 
            $sql_questions = "DELETE FROM questions WHERE quiz_id = ?";
            $stmt_questions = $pdo->prepare($sql_questions);
            $stmt_questions->execute([$quiz_to_delete_id]);
            
            // 2. Delete the quiz itself, ensuring it belongs to the current teacher
            $sql_quiz = "DELETE FROM quizzes WHERE quiz_id = ? AND teacher_id = ?";
            $stmt_quiz = $pdo->prepare($sql_quiz);
            
            if ($stmt_quiz->execute([$quiz_to_delete_id, $teacher_id]) && $stmt_quiz->rowCount() > 0) {
                $pdo->commit();
                $message = "Quiz (ID: {$quiz_to_delete_id}) and all its questions deleted successfully.";
            } else {
                $pdo->rollBack();
                $error = "Error: Quiz not found, or you do not have permission to delete it.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error during deletion: " . $e->getMessage();
        }
    } else {
        $error = "Invalid Quiz ID provided for deletion.";
    }
}


// --- PART 2: Fetch All Quizzes for Display ---
$quizzes = [];
try {
    $sql = "
        SELECT 
            q.quiz_id, q.title, q.duration_minutes, s.subject_name,
            (SELECT COUNT(*) FROM questions qs WHERE qs.quiz_id = q.quiz_id) as question_count
        FROM quizzes q
        JOIN subjects s ON q.subject_id = s.subject_id
        WHERE q.teacher_id = ?
        ORDER BY q.quiz_id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Failed to load quizzes: " . $e->getMessage();
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Quizzes</title>
    <style>
        /* Embedded CSS for Quiz Management */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #0d6efd; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0b5ed7; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #0a58ca; }
        .container { max-width: 1000px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .btn-create { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s; margin-bottom: 20px; text-decoration: none; display: inline-block; }
        .btn-create:hover { background-color: #218838; }
        
        .quiz-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .quiz-table th, .quiz-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .quiz-table th { background-color: #f2f2f2; font-weight: bold; }
        .quiz-table tr:nth-child(even) { background-color: #f9f9f9; }
        
        .action-link { display: inline-block; padding: 6px 10px; margin-right: 5px; border-radius: 4px; text-decoration: none; font-size: 0.9em; font-weight: bold; }
        .link-edit { background-color: #0d6efd; color: white; }
        .link-edit:hover { background-color: #0b5ed7; }
        .link-secondary { background-color: #ffc107; color: #333; }
        .link-secondary:hover { background-color: #e0a800; }
        .link-delete { background-color: #dc3545; color: white; border: none; cursor: pointer; }
        .link-delete:hover { background-color: #c82333; }
        
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
    <script>
        function confirmDelete(title) {
            return confirm('Are you sure you want to delete the quiz "' + title + '"? This will also delete all associated questions.');
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Manage Your Quizzes</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>Your Quizzes</h2>
        
        <a href="create_exam.php" class="btn-create">Create New Quiz/Exam</a>

        <?php if (empty($quizzes)): ?>
            <p>You have not created any quizzes yet. Click the button above to start.</p>
        <?php else: ?>
            <table class="quiz-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Duration (Min)</th>
                        <th>Questions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quizzes as $quiz): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                            <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($quiz['duration_minutes']); ?></td>
                            <td><?php echo htmlspecialchars($quiz['question_count']); ?></td>
                            <td>
                                <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" class="action-link link-edit">
                                    Manage Questions
                                </a>
                                <a href="create_exam.php?id=<?php echo $quiz['quiz_id']; ?>" class="action-link link-secondary">
                                    Edit Quiz
                                </a>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display:inline-block;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($quiz['title'], ENT_QUOTES); ?>');">
                                    <input type="hidden" name="action" value="delete_quiz">
                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                                    <button type="submit" class="action-link link-delete">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>