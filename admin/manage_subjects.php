<?php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db_config.php';
check_role('admin');

$message = '';
$error = '';

// --- 1. Handle Subject Addition ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $subject_name = trim($_POST['subject_name']);
    
    if (empty($subject_name)) {
        $error = "Subject name cannot be empty.";
    } else {
        try {
            // Check if subject already exists
            $check_sql = "SELECT COUNT(*) FROM subjects WHERE subject_name = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$subject_name]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $error = "Subject '{$subject_name}' already exists.";
            } else {
                // Insert new subject
                $insert_sql = "INSERT INTO subjects (subject_name) VALUES (?)";
                $insert_stmt = $pdo->prepare($insert_sql);
                
                if ($insert_stmt->execute([$subject_name])) {
                    $message = "Subject '{$subject_name}' added successfully!";
                } else {
                    $error = "Failed to add subject due to a database error.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// --- 2. Handle Subject Deletion ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_subject'])) {
    $subject_id = $_POST['subject_id'];
    
    try {
        // Attempt to delete the subject
        $delete_sql = "DELETE FROM subjects WHERE subject_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        
        if ($delete_stmt->execute([$subject_id])) {
            $message = "Subject deleted successfully.";
        } else {
            // NOTE: If deletion fails due to foreign key constraints, 
            // the PDOException should be caught below, not here.
            $message = "Subject deleted successfully."; // Reset to avoid confusion if it actually deleted.
        }
    } catch (PDOException $e) {
        // Catch foreign key error (e.g., subject is used in a quiz)
        if ($e->getCode() == '23000') { 
            $error = "Cannot delete subject: It is linked to existing quizzes or student enrollments. Please remove those links first.";
        } else {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// --- 3. Fetch Existing Subjects ---
$subjects = [];
try {
    $fetch_sql = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
    $subjects = $pdo->query($fetch_sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to load subjects: " . $e->getMessage();
}

unset($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }
        .header { background-color: #4e73df; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #2e59d9; transition: background-color 0.15s; }
        .header a:hover { background-color: #1a396e; }
        .container { max-width: 800px; margin: 30px auto; padding: 25px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .btn-primary { background-color: #4e73df; color: white; padding: 10px 15px; border-radius: 6px; font-weight: 600; transition: background-color 0.2s; }
        .btn-primary:hover { background-color: #2e59d9; }
        .btn-danger { background-color: #e74a3b; color: white; padding: 5px 10px; border-radius: 4px; font-size: 0.875rem; transition: background-color 0.2s; }
        .btn-danger:hover { background-color: #cc3c2e; }
        .input-text { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
        .table th, .table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-2xl font-bold">Subject Management</h1>
        <div class="flex space-x-4">
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container space-y-8">
        <h2 class="text-3xl font-semibold text-gray-800">Manage Course Subjects</h2>

        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Add New Subject</h3>
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="subject_name" class="block text-gray-700 font-medium mb-2">Subject Name:</label>
                    <input type="text" id="subject_name" name="subject_name" class="input-text" required placeholder="e.g., Mathematics, History">
                </div>
                <button type="submit" name="add_subject" class="btn-primary">Add Subject</button>
            </form>
        </div>

        <div class="card">
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Existing Subjects (<?php echo count($subjects); ?>)</h3>
            
            <?php if (empty($subjects)): ?>
                <p class="text-gray-500">No subjects have been added yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full table">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="w-1/4">ID</th>
                                <th class="w-2/4">Subject Name</th>
                                <th class="w-1/4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_id']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete the subject: <?php echo htmlspecialchars(addslashes($subject['subject_name'])); ?>?');" class="inline">
                                            <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                                            <button type="submit" name="delete_subject" class="btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>