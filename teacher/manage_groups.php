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
$edit_group = null;

// Check for successful action messages passed via redirect
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// --- PART 1: Handle CRUD Operations (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $group_name = trim($_POST['group_name'] ?? '');
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

    if (empty($group_name) && $action !== 'delete') {
        $error = "Group name cannot be empty.";
    } else {
        try {
            switch ($action) {
                case 'add':
                    // CREATE: Add a new group
                    $sql = "INSERT INTO groups (group_name, teacher_id) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$group_name, $teacher_id]);
                    $message = "Group **" . htmlspecialchars($group_name) . "** created successfully!";
                    break;

                case 'update':
                    // UPDATE: Rename an existing group
                    if ($group_id) {
                        $sql = "UPDATE groups SET group_name = ? WHERE group_id = ? AND teacher_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$group_name, $group_id, $teacher_id]);
                        $message = "Group updated to **" . htmlspecialchars($group_name) . "** successfully!";
                    }
                    break;

                case 'delete':
                    // DELETE: Remove a group and its assignments
                    if ($group_id) {
                        $pdo->beginTransaction();
                        
                        // 1. Remove associated quiz assignments (quiz_groups table)
                        $delete_assignments_sql = "DELETE FROM quiz_groups WHERE group_id = ?";
                        $pdo->prepare($delete_assignments_sql)->execute([$group_id]);

                        // 2. Delete the group itself
                        $delete_group_sql = "DELETE FROM groups WHERE group_id = ? AND teacher_id = ?";
                        $stmt = $pdo->prepare($delete_group_sql);
                        if ($stmt->execute([$group_id, $teacher_id])) {
                            $message = "Group and its quiz assignments deleted successfully!";
                        } else {
                            $error = "Group could not be deleted (it might not exist or belong to you).";
                        }
                        
                        $pdo->commit();
                    }
                    break;
            }
            
            // Redirect to a GET request to clear POST data and show message
            if (!empty($message) || !empty($error)) {
                $params = [];
                if (!empty($message)) $params[] = 'message=' . urlencode($message);
                if (!empty($error)) $params[] = 'error=' . urlencode($error);
                header("Location: manage_groups.php?" . implode('&', $params));
                exit;
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// --- PART 2: Handle Edit Pre-load (GET) ---
$edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
if ($edit_id) {
    try {
        $sql = "SELECT group_id, group_name FROM groups WHERE group_id = ? AND teacher_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$edit_id, $teacher_id]);
        $edit_group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$edit_group) {
            $error = "Group not found or you do not have permission to edit it.";
            $edit_id = null;
        }
    } catch (PDOException $e) {
        $error = "Error loading group for editing: " . $e->getMessage();
    }
}


// --- PART 3: Fetch All Groups (READ) ---
$groups = [];
try {
    $sql = "SELECT group_id, group_name FROM groups WHERE teacher_id = ? ORDER BY group_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Failed to load groups list: " . $e->getMessage();
}

unset($pdo);
ob_end_flush(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Student Groups</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #0d6efd; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0b5ed7; margin-left: 10px; transition: background-color 0.15s; }
        .header a:hover { background-color: #0a58ca; }
        .container { max-width: 900px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 8px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Form Styling */
        .form-card { padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 30px; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; margin-top: 5px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; transition: background-color 0.3s; }
        .btn-primary { background-color: #0d6efd; color: white; }
        .btn-primary:hover { background-color: #0a58ca; }
        .btn-cancel { background-color: #6c757d; color: white; margin-left: 10px; }
        .btn-cancel:hover { background-color: #5a6268; }

        /* Table Styling */
        .group-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .group-table th, .group-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .group-table th { background-color: #e9f2ff; color: #333; font-weight: 600; }
        .group-table tr:hover { background-color: #f0f0ff; }
        .group-table .actions a, .group-table .actions button { margin-right: 5px; text-decoration: none; font-size: 0.9em; padding: 5px 10px; border-radius: 4px; }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-assign { background-color: #28a745; color: white; }
        .btn-delete { background-color: #dc3545; color: white; }
    </style>
    <script>
        function confirmDelete(groupName) {
            // Use a custom confirmation logic instead of the forbidden window.confirm()
            return prompt(`Are you sure you want to delete the group "${groupName}"? Type 'DELETE' to confirm.`) === 'DELETE';
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>Student Group Management 📚</h1>
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

        <!-- Add/Edit Group Form -->
        <div class="form-card">
            <h2><?php echo $edit_group ? 'Edit Group: ' . htmlspecialchars($edit_group['group_name']) : 'Create New Student Group'; ?></h2>
            <form method="POST" action="manage_groups.php">
                <input type="hidden" name="action" value="<?php echo $edit_group ? 'update' : 'add'; ?>">
                <?php if ($edit_group): ?>
                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($edit_group['group_id']); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="group_name">Group Name:</label>
                    <input type="text" id="group_name" name="group_name" required 
                           value="<?php echo htmlspecialchars($edit_group['group_name'] ?? ''); ?>"
                           placeholder="e.g., Physics 101, Fall Semester Group A">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo $edit_group ? 'Save Changes' : 'Add Group'; ?>
                </button>
                
                <?php if ($edit_group): ?>
                    <a href="manage_groups.php" class="btn btn-cancel">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Group List -->
        <h2>Existing Groups (<?php echo count($groups); ?>)</h2>
        <?php if (empty($groups)): ?>
            <div class="message error">You haven't created any student groups yet.</div>
        <?php else: ?>
            <table class="group-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Group Name</th>
                        <th class="w-1/4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['group_id']); ?></td>
                            <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                            <td class="actions">
                                <a href="assign_quizzes.php?group_id=<?php echo $group['group_id']; ?>" class="btn btn-assign">Assign Quiz</a>
                                <a href="manage_groups.php?edit_id=<?php echo $group['group_id']; ?>" class="btn btn-edit">Edit</a>
                                <form method="POST" action="manage_groups.php" style="display:inline-block;" 
                                    onsubmit="return confirmDelete('<?php echo htmlspecialchars($group['group_name']); ?>')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                                    <button type="submit" class="btn btn-delete">Delete</button>
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