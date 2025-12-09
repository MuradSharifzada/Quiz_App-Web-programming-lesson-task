<?php
// Start output buffering for safe header redirects
ob_start();

// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Start session and ensure user is a teacher
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get the selected quiz_id from GET (for initial load) or POST (after submission/error)
$selected_quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
if (!$selected_quiz_id) {
    $selected_quiz_id = filter_input(INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT);
}

// Get the group_id from GET (used if navigated from manage_groups.php)
$pre_selected_group_id = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);

// Check for successful action messages passed via redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// --- PART 1: Handle Quiz Assignment (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_groups' && $selected_quiz_id) {
    
    $groups_to_assign = $_POST['group_ids'] ?? [];
    $assignment_date = trim($_POST['assignment_date'] ?? date('Y-m-d')); // Default to today

    if (empty($assignment_date)) {
        $error = "Please provide an Assignment Date.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Fetch current assignments to determine what to delete/add
            // NOTE: Using 'quiz_groups' as the table name based on context.
            $current_assignments_sql = "SELECT group_id FROM quiz_groups WHERE quiz_id = ?";
            $current_assignments_stmt = $pdo->prepare($current_assignments_sql);
            $current_assignments_stmt->execute([$selected_quiz_id]);
            $current_assigned_groups = $current_assignments_stmt->fetchAll(PDO::FETCH_COLUMN);

            $groups_to_add = array_diff($groups_to_assign, $current_assigned_groups);
            $groups_to_remove = array_diff($current_assigned_groups, $groups_to_assign);

            // 2. DELETE (Un-assign) groups that were unchecked
            if (!empty($groups_to_remove)) {
                $placeholders = implode(',', array_fill(0, count($groups_to_remove), '?'));
                $delete_sql = "DELETE FROM quiz_groups WHERE quiz_id = ? AND group_id IN ($placeholders)";
                $delete_params = array_merge([$selected_quiz_id], $groups_to_remove);
                $pdo->prepare($delete_sql)->execute($delete_params);
            }
            
            // 3. INSERT (Assign) new groups that were checked
            if (!empty($groups_to_add)) {
                $insert_sql = "INSERT INTO quiz_groups (quiz_id, group_id, assignment_date) VALUES (?, ?, ?)";
                $insert_stmt = $pdo->prepare($insert_sql);
                foreach ($groups_to_add as $group_id) {
                    $insert_stmt->execute([$selected_quiz_id, $group_id, $assignment_date]);
                }
            }

            $pdo->commit();
            $message = "Quiz assignment updated successfully! Assigned to " . count($groups_to_assign) . " group(s).";
            
            // Redirect to GET request to show results
            header("Location: assign_quizzes.php?quiz_id={$selected_quiz_id}&message=" . urlencode($message));
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error during assignment: " . $e->getMessage();
        }
    }
}


// --- PART 2: Fetch Data for UI (READ) ---

// A. Fetch all quizzes created by the teacher
$quizzes = [];
try {
    $quiz_sql = "SELECT quiz_id, title FROM quizzes WHERE teacher_id = ? ORDER BY title ASC";
    $quiz_stmt = $pdo->prepare($quiz_sql);
    $quiz_stmt->execute([$teacher_id]);
    $quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Failed to load quizzes: " . $e->getMessage();
}

// B. Fetch all groups created by the teacher
$groups = [];
try {
    $group_sql = "SELECT group_id, group_name FROM groups WHERE teacher_id = ? ORDER BY group_name ASC";
    $group_stmt = $pdo->prepare($group_sql);
    $group_stmt->execute([$teacher_id]);
    $groups = $group_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Failed to load groups: " . $e->getMessage();
}

// C. Fetch current assignments and assignment date if a quiz is selected
$quiz_title = 'Select a Quiz';
$default_assignment_date = date('Y-m-d'); 
$assigned_group_ids = [];

if ($selected_quiz_id) {
    try {
        // Verify quiz ownership and get title
        $quiz_verify_sql = "SELECT title FROM quizzes WHERE quiz_id = ? AND teacher_id = ?";
        $quiz_verify_stmt = $pdo->prepare($quiz_verify_sql);
        $quiz_verify_stmt->execute([$selected_quiz_id, $teacher_id]);
        $quiz_title = $quiz_verify_stmt->fetchColumn();

        if (!$quiz_title) {
            $error = "Quiz not found or you do not have permission to view it.";
            $selected_quiz_id = null;
        } else {
            // Fetch the groups currently assigned to this quiz
            $assignment_sql = "SELECT group_id, assignment_date FROM quiz_groups WHERE quiz_id = ?";
            $assignment_stmt = $pdo->prepare($assignment_sql);
            $assignment_stmt->execute([$selected_quiz_id]);
            $assignments = $assignment_stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($assignments as $assignment) {
                $assigned_group_ids[] = $assignment['group_id'];
                
                // Use the latest assignment date found as a default
                if ($assignment['assignment_date'] > $default_assignment_date) {
                    $default_assignment_date = $assignment['assignment_date'];
                }
            }
        }

    } catch (PDOException $e) {
        $error .= " Failed to load quiz assignments: " . $e->getMessage();
        $selected_quiz_id = null;
    }
}

// Check for pre-selected group from the manage_groups.php link
if (empty($assigned_group_ids) && $pre_selected_group_id && !$selected_quiz_id) {
    // Only pre-select if no quiz is loaded and no assignments exist for the current load
    // Need to make sure the pre-selected group exists for the teacher
    $group_exists = false;
    foreach ($groups as $group) {
        if ($group['group_id'] == $pre_selected_group_id) {
            $group_exists = true;
            break;
        }
    }
    if ($group_exists) {
        $assigned_group_ids[] = $pre_selected_group_id;
    }
}


unset($pdo);
// Send the output buffer content to the browser
ob_end_flush(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Quizzes to Groups</title>
    <style>
        /* CSS Styles for Assignment Management */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #0d6efd; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0b5ed7; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #0a58ca; }
        .container { max-width: 700px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-section { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 6px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        select, input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .group-list { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .group-item { border: 1px solid #eee; padding: 10px; border-radius: 4px; background: #f9f9f9; }
        .group-item.pre-selected { border: 2px solid #0d6efd; background-color: #e6f0ff; font-weight: bold; } /* Highlight pre-selected group */
        .group-item input[type="checkbox"] { margin-right: 10px; transform: scale(1.2); }
        
        .btn-submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s; width: 100%; margin-top: 15px; }
        .btn-submit:hover { background-color: #218838; }
        
        .selected-quiz-info { background: #e9f2ff; padding: 10px; border-radius: 4px; margin-bottom: 20px; border-left: 5px solid #0d6efd; }
        .selected-quiz-info strong { color: #0d6efd; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Assign Quizzes to Groups ✏️</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="manage_quizzes.php">Manage Quizzes</a>
            <a href="manage_groups.php">Manage Groups</a>
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

        <div class="form-section">
            <h2>1. Select Quiz</h2>
            <?php if (empty($quizzes)): ?>
                <p class="error">You must create a quiz before you can assign one. Go to <a href="manage_quizzes.php">Manage Quizzes</a>.</p>
            <?php else: ?>
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <?php if ($pre_selected_group_id): ?>
                        <input type="hidden" name="group_id" value="<?php echo $pre_selected_group_id; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="quiz_id">Choose a Quiz:</label>
                        <select id="quiz_id" name="quiz_id" onchange="this.form.submit()" required>
                            <option value="">-- Select Quiz --</option>
                            <?php foreach ($quizzes as $quiz): ?>
                                <option value="<?php echo $quiz['quiz_id']; ?>" 
                                    <?php echo ($quiz['quiz_id'] == $selected_quiz_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($quiz['title']); ?> (ID: <?php echo $quiz['quiz_id']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($selected_quiz_id): ?>
        
            <div class="selected-quiz-info">
                You are currently managing assignments for: <strong><?php echo htmlspecialchars($quiz_title); ?></strong>
            </div>

            <div class="form-section">
                <h2>2. Select Groups & Assignment Date</h2>
                
                <?php if (empty($groups)): ?>
                    <p class="error">You must create groups before you can assign a quiz. Go to <a href="manage_groups.php">Manage Groups</a>.</p>
                <?php else: ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="assign_groups">
                        <input type="hidden" name="quiz_id" value="<?php echo $selected_quiz_id; ?>">

                        <div class="form-group">
                            <label for="assignment_date">Assignment Date:</label>
                            <input type="date" id="assignment_date" name="assignment_date" required 
                                   value="<?php echo htmlspecialchars($default_assignment_date); ?>">
                        </div>
                        
                        <label>Select Groups to Assign:</label>
                        <ul class="group-list">
                            <?php foreach ($groups as $group): ?>
                                <?php
                                    $g_id = $group['group_id'];
                                    
                                    // 1. Determine if the group is currently assigned (from DB)
                                    $is_assigned = in_array($g_id, $assigned_group_ids);
                                    
                                    // 2. Override based on POST data if there was a submission error
                                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['group_ids'])) {
                                        $is_assigned = in_array($g_id, $_POST['group_ids']);
                                    }
                                    
                                    // 3. Apply highlight class if this group was passed in the URL (pre-selection)
                                    $highlight_class = ($g_id == $pre_selected_group_id) ? 'pre-selected' : '';
                                ?>
                                <li class="group-item <?php echo $highlight_class; ?>">
                                    <label>
                                        <input type="checkbox" name="group_ids[]" value="<?php echo $g_id; ?>"
                                            <?php echo $is_assigned ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($group['group_name']); ?>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <button type="submit" class="btn-submit">Update Assignments</button>
                    </form>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="form-section">
                <p>Please select a quiz from the dropdown above to view and manage its group assignments.</p>
                <?php 
                // Find the group name to display in the success message if pre-selected
                $pre_selected_group_name = '';
                if ($pre_selected_group_id) {
                    $found_key = array_search($pre_selected_group_id, array_column($groups, 'group_id'));
                    if ($found_key !== false) {
                        $pre_selected_group_name = $groups[$found_key]['group_name'];
                    }
                }
                
                if ($pre_selected_group_name): ?>
                     <div class="message success">You navigated here from **<?php echo htmlspecialchars($pre_selected_group_name); ?>**. Select a quiz to assign it!</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>