<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';
check_role('admin');

$message = '';
$error = '';

// NOTE: This script requires a 'teacher_subject' linkage table with columns: teacher_id, subject_id

// Fetch all teachers
// FIX: Using 'id' for user_id to match primary key in schema
$sql_teachers = "SELECT id, username FROM users 
                 WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'teacher') 
                 ORDER BY username";
$stmt_teachers = $pdo->query($sql_teachers);
$teachers = $stmt_teachers->fetchAll(PDO::FETCH_ASSOC);


// Fetch all subjects
$sql_subjects = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
$stmt_subjects = $pdo->query($sql_subjects);
$subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);


// --- Handle Assignment Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $teacher_id = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

    if (!$teacher_id || !$subject_id) {
        $error = "Please select both a teacher and a subject.";
    } else {
        $sql = "INSERT INTO teacher_subject (teacher_id, subject_id) VALUES (?, ?)";
        try {
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$teacher_id, $subject_id])) {
                // Fetch names for confirmation message
                // FIX: Using 'id' for user lookup
                $t_name = $pdo->query("SELECT username FROM users WHERE id = $teacher_id")->fetchColumn();
                $s_name = $pdo->query("SELECT subject_name FROM subjects WHERE subject_id = $subject_id")->fetchColumn();
                $message = "Subject **$s_name** successfully assigned to Teacher **$t_name**!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $error = "This subject is already assigned to this teacher.";
            } else {
                $error = "Database error during assignment: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Assign Subject</title>
    <style>
        /* Embedded CSS for Assign Subject */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0056b3; margin-left: 10px; }
        .header a:hover { background-color: #004085; }
        .container { max-width: 700px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #28a745; color: white; padding: 12px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; transition: background-color 0.3s; width: 100%; }
        .btn-submit:hover { background-color: #218838; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Assign Subject to Teacher</h1>
        <div>
            <a href="index.php">Admin Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Assignment Form 🧑‍🏫 📚</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="teacher_id">Select Teacher:</label>
                <select id="teacher_id" name="teacher_id" required>
                    <option value="">-- Select Teacher --</option>
                    <?php if (empty($teachers)): ?>
                        <option disabled>No teachers available. Create one first.</option>
                    <?php endif; ?>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject_id">Select Subject to Assign:</label>
                <select id="subject_id" name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php if (empty($subjects)): ?>
                        <option disabled>No subjects available. (Requires manual DB insert).</option>
                    <?php endif; ?>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>">
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-submit">Assign Subject</button>
        </form>
    </div>
</body>
</html>