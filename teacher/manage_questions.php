<?php
// Start output buffering and session
ob_start();

include_once '../includes/db_config.php';
include_once '../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT) 
           ?? filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);

$message = '';
$error = '';
$quiz_title = '';
$edit_question = null;

// Validate quiz_id and confirm teacher owns it
if (!$quiz_id) {
    $error = "Quiz ID missing. Please select a quiz.";
} else {
    try {
        $sql = "SELECT title FROM quizzes WHERE quiz_id = ? AND teacher_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quiz_id, $teacher_id]);
        $quiz_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$quiz_data) {
            $error = "Quiz not found or you don't have permission.";
            $quiz_id = null;
        } else {
            $quiz_title = htmlspecialchars($quiz_data['title']);
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle POST actions: add, update, delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && $quiz_id && !$error) {
    $action = $_POST['action'] ?? '';
    $question_id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);

    $question_text  = trim($_POST['question_text'] ?? '');
    $option_a      = trim($_POST['option_a'] ?? '');
    $option_b      = trim($_POST['option_b'] ?? '');
    $option_c      = trim($_POST['option_c'] ?? '');
    $option_d      = trim($_POST['option_d'] ?? '');
    $correct_option = trim($_POST['correct_option'] ?? '');
    $points        = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT);

    // Validate inputs for add/update
    if (in_array($action, ['add', 'update'])) {
        if (empty($question_text) || empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d)
            || !in_array($correct_option, ['A', 'B', 'C', 'D'])
            || $points === false || $points < 1) {
            $error = "Please fill all fields correctly, select a correct option (A-D), and provide valid points.";
        }
    }

    try {
        $pdo->beginTransaction();

        if ($action === 'add' && empty($error)) {
            $sql = "INSERT INTO questions 
                (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, points)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $points]);
            $message = "Question added successfully.";

        } elseif ($action === 'update' && $question_id && empty($error)) {
            // Ensure question belongs to quiz
            $sql_check = "SELECT question_id FROM questions WHERE question_id = ? AND quiz_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$question_id, $quiz_id]);
            if ($stmt_check->rowCount() === 0) {
                throw new Exception("Question not found or doesn't belong to this quiz.");
            }
            $sql = "UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, points = ? 
                    WHERE question_id = ? AND quiz_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$question_text, $option_a, $option_b, $option_c, $option_d, $correct_option, $points, $question_id, $quiz_id]);
            $message = "Question updated successfully.";

        } elseif ($action === 'delete' && $question_id) {
            // Ensure question belongs to quiz before deleting
            $sql_check = "SELECT question_id FROM questions WHERE question_id = ? AND quiz_id = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$question_id, $quiz_id]);
            if ($stmt_check->rowCount() === 0) {
                throw new Exception("Question not found or doesn't belong to this quiz.");
            }
            $sql = "DELETE FROM questions WHERE question_id = ? AND quiz_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$question_id, $quiz_id]);
            $message = "Question deleted successfully.";
        }

        $pdo->commit();

        // Redirect to clear POST data and avoid resubmission
        header("Location: manage_questions.php?quiz_id=$quiz_id&message=" . urlencode($message));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Load question for edit if requested
$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
if ($edit_id && $quiz_id && !$error) {
    try {
        $sql = "SELECT * FROM questions WHERE question_id = ? AND quiz_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$edit_id, $quiz_id]);
        $edit_question = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_question) {
            $error = "Question to edit not found.";
            $edit_question = null;
        }
    } catch (PDOException $e) {
        $error = "Error loading question: " . $e->getMessage();
    }
}

// Fetch all questions for this quiz
$questions = [];
if ($quiz_id && !$error) {
    try {
        $sql = "SELECT * FROM questions WHERE quiz_id = ? ORDER BY question_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error .= " Error loading questions list: " . $e->getMessage();
    }
}

unset($pdo);
ob_end_flush();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Manage Questions for: <?php echo $quiz_title; ?></title>
<style>
/* Reset some defaults */
* {
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f0f2f5;
    color: #222;
    margin: 0;
    padding: 0;
    line-height: 1.5;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.container {
    max-width: 960px;
    margin: 2rem auto;
    background-color: #fff;
    padding: 2rem 2.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgb(0 0 0 / 0.08);
}

h1, h2 {
    color: #222;
    font-weight: 700;
    margin-bottom: 1rem;
    line-height: 1.2;
}

h1 {
    font-size: 2rem;
    border-bottom: 3px solid #007bff;
    padding-bottom: 0.3rem;
}

h2 {
    font-size: 1.5rem;
    margin-top: 2rem;
}

.message {
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
}

.success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1.5px solid #badbcc;
}

.error {
    background-color: #f8d7da;
    color: #842029;
    border: 1.5px solid #f5c2c7;
}

form {
    margin-top: 1rem;
}

form label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.4rem;
    color: #444;
}

form input[type="text"],
form input[type="number"],
form select,
form textarea {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: 1.8px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    font-family: inherit;
}

form input[type="text"]:focus,
form input[type="number"]:focus,
form select:focus,
form textarea:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

form textarea {
    min-height: 100px;
    resize: vertical;
}

form button {
    margin-top: 1.25rem;
    background-color: #007bff;
    border: none;
    padding: 0.7rem 1.5rem;
    font-size: 1.1rem;
    color: white;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.25s ease;
    display: inline-block;
}

form button:hover,
.actions button:hover {
    background-color: #0056b3;
}

form a {
    font-weight: 600;
    margin-left: 1rem;
    color: #007bff;
    text-decoration: none;
    vertical-align: middle;
    line-height: 2.4;
}

form a:hover {
    text-decoration: underline;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2rem;
    font-size: 0.95rem;
}

th, td {
    border: 1px solid #ddd;
    padding: 0.8rem 1rem;
    vertical-align: top;
}

th {
    background-color: #007bff;
    color: #fff;
    font-weight: 700;
    text-align: left;
    user-select: none;
}

td {
    background-color: #fafafa;
}

.correct {
    color: #198754;
    font-weight: 700;
    font-size: 1.05rem;
    text-align: center;
}

.actions {
    white-space: nowrap;
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.actions a {
    color: #0d6efd;
    font-weight: 600;
    text-decoration: none;
    padding: 4px 10px;
    border: 1.5px solid #0d6efd;
    border-radius: 6px;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.actions a:hover {
    background-color: #0d6efd;
    color: #fff;
}

.actions form {
    margin: 0;
    display: inline;
}

.actions button {
    background-color: #dc3545;
    border: none;
    color: white;
    padding: 5px 12px;
    font-size: 0.9rem;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.actions button:hover {
    background-color: #a71d2a;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    .container {
        margin: 1rem 1rem;
        padding: 1.5rem 1.5rem;
    }

    form button,
    .actions a,
    .actions button {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }

    table, th, td {
        font-size: 0.85rem;
    }

    .actions {
        flex-direction: column;
        gap: 0.3rem;
    }
}

</style>
<script>
    function confirmDelete(questionText) {
        return confirm('Are you sure you want to delete this question?\n\n' + questionText);
    }
</script>
</head>
<body>
<div class="container">

    <h1>Manage Questions for Quiz: <?php echo $quiz_title; ?></h1>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <h2><?php echo $edit_question ? "Edit Question ID: " . intval($edit_question['question_id']) : "Add New Question"; ?></h2>

    <form method="POST" action="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
        <?php if ($edit_question): ?>
            <input type="hidden" name="question_id" value="<?php echo intval($edit_question['question_id']); ?>">
        <?php endif; ?>

        <label for="question_text">Question Text</label>
        <textarea name="question_text" id="question_text" rows="4" required><?php echo htmlspecialchars($edit_question['question_text'] ?? ''); ?></textarea>

        <label for="option_a">Option A</label>
        <input type="text" name="option_a" id="option_a" value="<?php echo htmlspecialchars($edit_question['option_a'] ?? ''); ?>" required>

        <label for="option_b">Option B</label>
        <input type="text" name="option_b" id="option_b" value="<?php echo htmlspecialchars($edit_question['option_b'] ?? ''); ?>" required>

        <label for="option_c">Option C</label>
        <input type="text" name="option_c" id="option_c" value="<?php echo htmlspecialchars($edit_question['option_c'] ?? ''); ?>" required>

        <label for="option_d">Option D</label>
        <input type="text" name="option_d" id="option_d" value="<?php echo htmlspecialchars($edit_question['option_d'] ?? ''); ?>" required>

        <label for="correct_option">Correct Option</label>
        <select name="correct_option" id="correct_option" required>
            <option value="">-- Select --</option>
            <?php
            $opts = ['A', 'B', 'C', 'D'];
            $current_correct = $edit_question['correct_option'] ?? '';
            foreach ($opts as $opt) {
                $selected = ($opt === $current_correct) ? 'selected' : '';
                echo "<option value=\"$opt\" $selected>$opt</option>";
            }
            ?>
        </select>

        <label for="points">Points</label>
        <input type="number" name="points" id="points" min="1" required value="<?php echo htmlspecialchars($edit_question['points'] ?? 1); ?>">

        <button type="submit" name="action" value="<?php echo $edit_question ? 'update' : 'add'; ?>">
            <?php echo $edit_question ? 'Update Question' : 'Add Question'; ?>
        </button>
        <?php if ($edit_question): ?>
            <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" style="margin-left:10px;">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <h2>Existing Questions (<?php echo count($questions); ?>)</h2>

    <?php if (empty($questions)): ?>
        <p>No questions found for this quiz.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question Text</th>
                    <th>Options</th>
                    <th>Correct</th>
                    <th>Points</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($questions as $q): ?>
                <tr>
                    <td><?php echo intval($q['question_id']); ?></td>
                    <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                    <td>
                        A) <?php echo htmlspecialchars($q['option_a']); ?><br>
                        B) <?php echo htmlspecialchars($q['option_b']); ?><br>
                        C) <?php echo htmlspecialchars($q['option_c']); ?><br>
                        D) <?php echo htmlspecialchars($q['option_d']); ?>
                    </td>
                    <td class="correct"><?php echo htmlspecialchars($q['correct_option']); ?></td>
                    <td><?php echo intval($q['points']); ?></td>
                    <td class="actions">
                        <a href="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>&edit_id=<?php echo intval($q['question_id']); ?>">Edit</a>
                        <form method="POST" action="manage_questions.php?quiz_id=<?php echo $quiz_id; ?>" onsubmit="return confirmDelete('<?php echo htmlspecialchars(addslashes($q['question_text'])); ?>')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="question_id" value="<?php echo intval($q['question_id']); ?>">
                            <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                            <button type="submit">Delete</button>
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
