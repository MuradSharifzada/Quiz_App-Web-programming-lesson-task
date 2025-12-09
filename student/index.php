<?php
// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$message = '';
$error = '';
$group_name = null; // Stores the group name (e.g., '232King')
$group_ids = [];
$assigned_quizzes = [];

$current_date = date('Y-m-d');

try {
    // NOTE: Assuming users.group_number contains the group_name, 
    // adjust if it's actually a group_id or named differently.
    $group_name_sql = "SELECT group_number FROM users WHERE id = ?";
    $stmt = $pdo->prepare($group_name_sql);
    $stmt->execute([$student_id]);
    $group_name = $stmt->fetchColumn();

    if (!$group_name) {
        $error = "Your user account is not assigned to a group (missing 'group_number' value). Please contact admin.";
    } else {
        // Get group IDs for this group name
        $group_ids_sql = "SELECT group_id FROM groups WHERE group_name = ?";
        $stmt = $pdo->prepare($group_ids_sql);
        $stmt->execute([$group_name]);
        $group_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$group_ids) {
            $error = "No groups found in database for your group name '{$group_name}'. Contact admin.";
        } else {
            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($group_ids), '?'));
            $params = array_merge($group_ids, [$current_date]);

            $quiz_sql = "
                SELECT 
                    q.quiz_id, 
                    q.title, 
                    q.duration_minutes, 
                    s.subject_name,
                    qg.assignment_date
                FROM quizzes q
                JOIN quiz_groups qg ON q.quiz_id = qg.quiz_id
                JOIN subjects s ON q.subject_id = s.subject_id
                WHERE qg.group_id IN ({$placeholders})
                  AND qg.assignment_date <= ?
                ORDER BY qg.assignment_date DESC, q.quiz_id DESC
            ";

            $stmt = $pdo->prepare($quiz_sql);
            $stmt->execute($params);
            $assigned_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Student Dashboard - Available Quizzes</title>
    <style>
        /* ... your CSS from above ... */
        body { font-family: Arial, sans-serif; background-color: #eef1f5; margin: 0; padding: 0; }
        .header { background-color: #5a5f6a; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #494c53; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #3b3e44; }
        .container { max-width: 900px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .quiz-list { list-style: none; padding: 0; }
        .quiz-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 5px solid #0d6efd;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .quiz-details { flex-grow: 1; }
        .quiz-details h3 { margin: 0 0 5px 0; color: #0d6efd; }
        .quiz-meta { font-size: 0.9em; color: #666; }
        .quiz-meta span { margin-right: 15px; }
        .quiz-meta strong { color: #333; }
        .quiz-action a {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .quiz-action a:hover { background-color: #218838; }
        .no-quizzes { padding: 20px; text-align: center; color: #555; border: 1px dashed #ccc; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard</h1>
        <div>
            <a href="../logout.php">Logout (ID: <?php echo htmlspecialchars($student_id); ?>)</a>
        </div>
    </div>

    <div class="container">
        <h2>Available Quizzes</h2>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($group_ids) && empty($assigned_quizzes) && !$error): ?>
            <div class="no-quizzes">
                <p>You have no quizzes available for your groups (IDs: <?php echo htmlspecialchars(implode(', ', $group_ids)); ?>) as of today (<?php echo htmlspecialchars($current_date); ?>).</p>
                <p>Assignments must have an assignment date on or before <?php echo htmlspecialchars($current_date); ?>.</p>
            </div>
        <?php elseif (!empty($group_ids)): ?>
            <ul class="quiz-list">
                <?php foreach ($assigned_quizzes as $quiz): ?>
                    <li class="quiz-item">
                        <div class="quiz-details">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <div class="quiz-meta">
                                <span>Subject: <strong><?php echo htmlspecialchars($quiz['subject_name']); ?></strong></span>
                                <span>Duration: <strong><?php echo htmlspecialchars($quiz['duration_minutes']); ?> mins</strong></span>
                                <span>Assigned Date: <strong><?php echo htmlspecialchars($quiz['assignment_date']); ?></strong></span>
                            </div>
                        </div>
                        <div class="quiz-action">
                            <a href="take_quiz.php?quiz_id=<?php echo htmlspecialchars($quiz['quiz_id']); ?>">Start Quiz</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
