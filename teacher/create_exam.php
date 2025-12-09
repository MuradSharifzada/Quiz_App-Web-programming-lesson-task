<?php
// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Start session and check authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';
$edit_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$page_title = $edit_id ? 'Edit Quiz Details' : 'Create New Quiz/Exam';

// Default form data
$quiz_data = [
    'title' => '',
    'subject_id' => '',
    'duration_minutes' => 60,
    'quiz_id' => null
];

// --- Fetch Subjects for the dropdown list ---
$subjects = [];
try {
    $sub_sql = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
    $sub_stmt = $pdo->query($sub_sql);
    $subjects = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error fetching subjects: " . $e->getMessage();
}


// --- PART 1: Handle Editing (Fetch existing data) ---
if ($edit_id) {
    try {
        $edit_sql = "SELECT quiz_id, title, subject_id, duration_minutes FROM quizzes WHERE quiz_id = ? AND teacher_id = ?";
        $edit_stmt = $pdo->prepare($edit_sql);
        $edit_stmt->execute([$edit_id, $teacher_id]);
        $existing_data = $edit_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_data) {
            $quiz_data = $existing_data;
        } else {
            $error = "Quiz not found or you do not have permission to edit it.";
            $edit_id = null; // Prevent submission from attempting an edit
        }
    } catch (PDOException $e) {
        $error = "Database error fetching quiz data: " . $e->getMessage();
    }
}


// --- PART 2: Handle Form Submission (Create or Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    $new_title = trim($_POST['title']);
    $new_subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $new_duration = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $submitted_quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT); // Only present for edits
    
    // Update data array in case of validation failure to retain user input
    $quiz_data['title'] = $new_title;
    $quiz_data['subject_id'] = $new_subject_id;
    $quiz_data['duration_minutes'] = $new_duration;

    // Input validation
    if (empty($new_title) || !$new_subject_id || $new_duration < 10) {
        $error = "Please provide a valid Title, select a Subject, and ensure Duration is at least 10 minutes.";
    } else {
        try {
            if ($submitted_quiz_id) {
                // UPDATE Logic (Editing an existing quiz)
                $sql = "UPDATE quizzes SET title = ?, subject_id = ?, duration_minutes = ?, updated_at = NOW() WHERE quiz_id = ? AND teacher_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_title, $new_subject_id, $new_duration, $submitted_quiz_id, $teacher_id]);
                
                $message = "Quiz **" . htmlspecialchars($new_title) . "** updated successfully!";
                
                // Redirect back to the quiz management page
                header("Location: manage_quizzes.php?message=" . urlencode($message));
                exit;
            } else {
                // INSERT Logic (Creating a new quiz)
                $sql = "INSERT INTO quizzes (title, subject_id, duration_minutes, teacher_id, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_title, $new_subject_id, $new_duration, $teacher_id]);
                $new_quiz_id = $pdo->lastInsertId();
                
                $message = "Quiz **" . htmlspecialchars($new_title) . "** created successfully! Now, add some questions.";
                
                // Redirect immediately to the question manager for the new quiz
                header("Location: manage_questions.php?quiz_id={$new_quiz_id}&message=" . urlencode($message));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #0d6efd; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0b5ed7; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #0a58ca; }
        .container { max-width: 600px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s; width: 100%; }
        .btn-submit:hover { background-color: #218838; }
        .btn-update { background-color: #ffc107; color: #333; }
        .btn-update:hover { background-color: #e0a800; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="manage_quizzes.php">Manage Quizzes</a>
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

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . ($edit_id ? "?id={$edit_id}" : ''); ?>" method="POST">
            <?php if ($edit_id): ?>
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_data['quiz_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Quiz/Exam Title:</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($quiz_data['title']); ?>">
            </div>

            <div class="form-group">
                <label for="subject_id">Subject:</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" 
                            <?php echo ((int)$quiz_data['subject_id'] === (int)$subject['subject_id'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="duration_minutes">Duration (in minutes, minimum 10):</label>
                <input type="number" id="duration_minutes" name="duration_minutes" min="10" required value="<?php echo htmlspecialchars($quiz_data['duration_minutes']); ?>">
            </div>

            <button type="submit" class="btn-submit <?php echo $edit_id ? 'btn-update' : ''; ?>">
                <?php echo $edit_id ? 'Update Quiz' : 'Create Quiz'; ?>
            </button>
            
            <div style="margin-top: 15px; text-align: center;">
                <a href="manage_quizzes.php" style="color: #0d6efd; text-decoration: none;">← Back to Manage Quizzes</a>
            </div>
        </form>
    </div>
</body>
</html>