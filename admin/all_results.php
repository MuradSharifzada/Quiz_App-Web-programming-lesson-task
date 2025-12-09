<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) session_start();
check_role('admin');

// Fetch ALL results
$sql = "
    SELECT 
        r.result_id,
        r.score,
        r.total_points,
        r.submission_time,
        q.title AS quiz_title,
        u.fullname AS student_name
    FROM results r
    JOIN quizzes q ON r.quiz_id = q.quiz_id
    JOIN users u ON r.student_id = u.user_id
    ORDER BY r.submission_time DESC
";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>All Quiz Results</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 10px; border-bottom: 1px solid #ccc; }
        th { background: #333; color: #fff; }
        a.view-btn {
            background: #3498db; padding: 6px 12px; color: #fff;
            border-radius: 5px; text-decoration: none;
        }
    </style>
</head>
<body>

<h1>All Student Quiz Results</h1>

<table>
    <tr>
        <th>Quiz</th>
        <th>Student</th>
        <th>Score</th>
        <th>Submitted At</th>
        <th>Action</th>
    </tr>

    <?php foreach ($results as $row): ?>
    <tr>
        <td><?php echo htmlspecialchars($row['quiz_title']); ?></td>
        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
        <td><?php echo $row['score'] . " / " . $row['total_points']; ?></td>
        <td><?php echo $row['submission_time']; ?></td>
        <td>
            <a class="view-btn" href="view_quiz_result.php?result_id=<?php echo $row['result_id']; ?>">
                View Result
            </a>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
