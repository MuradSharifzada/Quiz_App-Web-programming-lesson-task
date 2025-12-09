<?php
// admin/index.php
session_start();
require_once '../includes/auth.php';
require_once '../includes/db_config.php';
check_role('admin'); 

// Fetch user counts for the dashboard statistics
try {
    $sql_students = "SELECT COUNT(u.id) 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.role_id 
                     WHERE r.role_name = 'student'";
    $student_count = $pdo->query($sql_students)->fetchColumn();

    $sql_teachers = "SELECT COUNT(u.id) 
                     FROM users u 
                     JOIN roles r ON u.role_id = r.role_id 
                     WHERE r.role_name = 'teacher'";
    $teacher_count = $pdo->query($sql_teachers)->fetchColumn();
    
    $sql_subjects = "SELECT COUNT(subject_id) FROM subjects";
    $subject_count = $pdo->query($sql_subjects)->fetchColumn();
    
} catch (PDOException $e) {
    $student_count = "N/A";
    $teacher_count = "N/A";
    $subject_count = "N/A";
    error_log("Database error on Admin Dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }
        .header { background-color: #4e73df; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #2e59d9; }
        .header a:hover { background-color: #1a396e; }
        .container { max-width: 1200px; margin: 30px auto; padding: 25px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); }
        .stat-card { text-align: center; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; margin-bottom: 5px; color: #4e73df; }
        .stat-card .label { font-size: 1rem; color: #6b7280; }
        .action-card { text-align: center; }
        .action-link { padding: 12px 20px; margin: 5px; border-radius: 6px; font-weight: 600; display: block; }
        .btn-students { background-color: #10b981; color: white; }
        .btn-students:hover { background-color: #059669; }
        .btn-teachers { background-color: #f59e0b; color: white; }
        .btn-teachers:hover { background-color: #d97706; }
        .btn-subjects { background-color: #3b82f6; color: white; }
        .btn-subjects:hover { background-color: #2563eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-2xl font-bold">Admin Dashboard</h1>
        <p>Logged in as: <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</p>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="container">
        <h2 class="text-3xl font-semibold mb-6 text-gray-800">System Overview</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="card stat-card bg-blue-100 border-l-4 border-blue-500">
                <div class="number"><?php echo $student_count; ?></div>
                <div class="label">Total Students</div>
            </div>

            <div class="card stat-card bg-yellow-100 border-l-4 border-yellow-500">
                <div class="number"><?php echo $teacher_count; ?></div>
                <div class="label">Total Teachers</div>
            </div>

            <div class="card stat-card bg-green-100 border-l-4 border-green-500">
                <div class="number"><?php echo $subject_count; ?></div>
                <div class="label">Total Subjects</div>
            </div>
        </div>

        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Management Actions</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="card action-card">
                <h3 class="text-xl font-semibold mb-4">Student & Enrollment Management</h3>
                <a href="manage_students.php" class="action-link btn-students">Manage Students</a>
            </div>
            
            <div class="card action-card">
                <h3 class="text-xl font-semibold mb-4">Teacher Account Creation</h3>
                <a href="create_teacher.php" class="action-link btn-teachers">Create Teacher</a>
            </div>
            
            <div class="card action-card">
                <h3 class="text-xl font-semibold mb-4">Subject Management</h3>
                <a href="manage_subjects.php" class="action-link btn-subjects">Manage Subjects</a>
            </div>

            <div class="card action-card">
                <h3 class="text-xl font-semibold mb-4 text-gray-700">Student Quiz Results</h3>
                <a href="manage_students_result.php" class="action-link btn-subjects">Manage Student Results</a>
            </div>


        </div>
    </div>
</body>
</html>
