<?php
session_start(); // Ensure session is started for authentication
require_once '../includes/db_config.php'; 
require_once '../includes/auth.php';
check_role('admin');

$username = $password = $group_number = $selected_subject_id = "";
$username_err = $password_err = $subject_err = "";
$success_msg = ""; 
$error_msg_display = "";

// Get roles and subjects
try {
    $student_role_id = $pdo->query("SELECT role_id FROM roles WHERE role_name = 'student'")->fetchColumn();
    $subjects = $pdo->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Catch initial fetch error (e.g., if 'subjects' or 'roles' table is missing)
    $error_msg_display = "Critical Database Error: Could not fetch roles or subjects on startup: " . $e->getMessage();
    error_log("Manage Students Initial Fetch Error: " . $e->getMessage());
    $student_role_id = 0; 
    $subjects = [];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'create_student') {
        
        if ($student_role_id == 0) {
            $error_msg_display = "Cannot create student: The 'student' role ID could not be found.";
        } else {
            // --- 1. Validate and Create Student ---
            $username = trim($_POST["username"]);
            $password = $_POST["password"];
            $group_number = trim($_POST["group_number"]);
            $selected_subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

            // Validation (simplified)
            if (empty($username) || empty($password) || empty($group_number) || !$selected_subject_id) {
                $success_msg = "Error: All fields are required.";
            } else {
                // Check if username exists
                $sql_check = "SELECT id FROM users WHERE username = ?";
                $stmt_check = $pdo->prepare($sql_check);
                $stmt_check->execute([$username]);
                
                if ($stmt_check->rowCount() > 0) {
                    $username_err = "This username is already taken.";
                    $success_msg = "Error: Username conflict.";
                } else {
                    $plain_password = $password;

                    try {
                        // We no longer need a transaction as only one INSERT is required.
                        
                        // 1. Insert user and enroll them by setting the subject_id
                        // NOTE: You MUST add the 'subject_id' column to your 'users' table.
                        $sql_user = "INSERT INTO users (username, password, role_id, group_number, subject_id, email, first_name, last_name, is_active) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                        $stmt_user = $pdo->prepare($sql_user);
                        
                        // Temporary default/placeholder values for NOT NULL columns
                        $default_email = "{$username}@student.local";
                        $default_first = "Student";
                        $default_last = $username;

                        $stmt_user->execute([
                            $username, 
                            $plain_password, 
                            $student_role_id, 
                            $group_number,
                            $selected_subject_id, // <-- New: Subject ID stored directly in users table
                            $default_email,
                            $default_first,
                            $default_last
                        ]);
                        
                        // Fetch subject name 
                        $subject_name_query = $pdo->prepare("SELECT subject_name FROM subjects WHERE subject_id = ?");
                        $subject_name_query->execute([$selected_subject_id]);
                        $subject_name = $subject_name_query->fetchColumn();

                        $success_msg = "Student **" . htmlspecialchars($username) . "** created and enrolled in **" . 
                                       htmlspecialchars($subject_name) . "** successfully!";
                        // Clear inputs on success
                        $username = $password = $group_number = $selected_subject_id = "";
                        
                    } catch (PDOException $e) {
                        $success_msg = "Database Error: Student creation failed. Check users table required columns (did you add subject_id?): " . $e->getMessage();
                        error_log("Student creation error: " . $e->getMessage());
                    }
                }
            }
        }
    }
}

// Fetch list of all students for display
$students = [];
// Only attempt to fetch students if the required role ID was found
if ($student_role_id > 0) { 
    // FIX: Simplified the query to JOIN directly from users to subjects using u.subject_id
    $sql_students = "
        SELECT u.id, u.username, u.group_number, s.subject_name 
        FROM users u
        LEFT JOIN subjects s ON u.subject_id = s.subject_id
        WHERE u.role_id = ?
        ORDER BY u.group_number, u.username
    ";
    try {
        $stmt_students = $pdo->prepare($sql_students);
        $stmt_students->execute([$student_role_id]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
         // This catches the "Could not load student list" error
         $error_msg_display = "Database Error: Could not load student list. Check if the 'subject_id' column exists in the 'users' table.";
         error_log("Student list fetch error: " . $e->getMessage());
    }
} else if (empty($error_msg_display)) {
    $error_msg_display = "Error: Cannot load students because the 'student' role ID could not be found.";
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Manage Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }
        .header { background-color: #4e73df; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #2e59d9; margin-left: 10px; transition: background-color 0.15s ease; }
        .header a:hover { background-color: #1a396e; }
        .container { max-width: 1000px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .form-card { border: 1px solid #e0e0e0; padding: 20px; border-radius: 8px; margin-bottom: 30px; background-color: #fcfcfc; }
        .success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: 600; }
        .error-message { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-weight: 600; }
        .students-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .students-table th, .students-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .students-table th { background-color: #f7f7f7; font-weight: bold; text-transform: uppercase; font-size: 0.9em; }
        .students-table tr:nth-child(even) { background-color: #fafafa; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Manage Students</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Create New Student & Enroll</h2>

        <?php 
        if (!empty($error_msg_display)): ?>
            <div class="error-message"><?php echo $error_msg_display; ?></div>
        <?php endif;

        if (!empty($success_msg)): 
            if (strpos($success_msg, 'Error') !== false || strpos($success_msg, 'failed') !== false): ?>
                <div class="error-message"><?php echo $success_msg; ?></div>
            <?php else: ?>
                <div class="success"><?php echo $success_msg; ?></div>
            <?php endif; 
        endif; ?>

        <div class="form-card">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="action" value="create_student">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                        <input type="text" name="username" id="username" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($username); ?>" required>
                        <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                        <input type="password" name="password" id="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="group_number">Group No.</label>
                        <input type="text" name="group_number" id="group_number" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($group_number); ?>" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="subject_id">Enroll in Subject</label>
                        <select name="subject_id" id="subject_id" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" 
                                    <?php echo $selected_subject_id == $subject['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-red-500 text-xs italic"><?php echo $subject_err; ?></span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg focus:outline-none focus:shadow-outline transition duration-150">
                        Create & Enroll Student
                    </button>
                </div>
            </form>
        </div>

        <h2 class="mt-8">Existing Students</h2>
        <?php if ($student_role_id == 0 && !empty($error_msg_display)): ?>
             <p class="text-red-600">Cannot load student list due to a critical setup error. See message above.</p>
        <?php elseif (empty($students)): ?>
            <p>No students found in the database under the 'student' role.</p>
        <?php else: ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Group No.</th>
                        <th>Enrolled Subject</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                            <td><?php echo htmlspecialchars($student['group_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['subject_name'] ?: 'None'); ?></td> 
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>