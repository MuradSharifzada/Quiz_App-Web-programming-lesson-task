<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';
check_role('admin');

$results = [];
$message = '';
$error = '';

// --- Fetch all quizzes and subjects for filtering ---

// Fetch all subjects
$sql_subjects = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
$stmt_subjects = $pdo->query($sql_subjects);
$subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

// FIX: Changed 'exams' table/columns to 'quizzes' table/columns
$sql_quizzes = "SELECT quiz_id, title, duration_minutes FROM quizzes ORDER BY title"; // duration_minutes is a placeholder for 'type'
$stmt_quizzes = $pdo->query($sql_quizzes);
$quizzes = $stmt_quizzes->fetchAll(PDO::FETCH_ASSOC);


// --- Handle filtering and fetching results ---
$filter_subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
$filter_quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT); // FIX: Changed $filter_exam_id to $filter_quiz_id

// Start building the query
$results_sql = "
    SELECT 
        r.score, 
        r.total_points,  /* FIX: Changed r.total_questions to r.total_points */
        r.submission_time,
        u.username, 
        u.group_number,
        q.title AS quiz_title, /* FIX: Changed e.exam_title to q.title */
        q.duration_minutes AS quiz_duration, /* Using duration as proxy for type/info */
        s.subject_name
    FROM results r
    JOIN users u ON r.student_id = u.id /* FIX: Changed u.user_id to u.id */
    JOIN quizzes q ON r.quiz_id = q.quiz_id /* FIX: Changed exams e to quizzes q, and exam_id to quiz_id */
    JOIN subjects s ON q.subject_id = s.subject_id /* FIX: Subject is linked to quiz, not result */
    WHERE 1=1
";

$params = [];
$where_clauses = [];

if ($filter_subject_id) {
    // Subject is linked via the quiz
    $where_clauses[] = "q.subject_id = ?";
    $params[] = $filter_subject_id;
}
if ($filter_quiz_id) {
    // FIX: Changed r.exam_id to r.quiz_id
    $where_clauses[] = "r.quiz_id = ?";
    $params[] = $filter_quiz_id;
}

if (!empty($where_clauses)) {
    $results_sql .= " AND " . implode(' AND ', $where_clauses);
}

$results_sql .= " ORDER BY s.subject_name, q.title, r.score DESC";

try {
    $results_stmt = $pdo->prepare($results_sql);
    $results_stmt->execute($params);
    $results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        $message = "No results found matching the selected criteria.";
    }

} catch (PDOException $e) {
    $error = "Error fetching results: " . $e->getMessage();
}
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: View All Results</title>
    <style>
        /* Embedded CSS for Admin Results View */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #0056b3; margin-left: 10px; }
        .header a:hover { background-color: #004085; }
        .container { max-width: 1200px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        
        .filter-form { margin-bottom: 25px; padding: 15px; border: 1px solid #ddd; border-radius: 6px; background-color: #f9f9f9; }
        .filter-group { display: flex; gap: 20px; align-items: flex-end; }
        .filter-item { flex: 1; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        select, .btn-apply { padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; width: 100%; }
        .btn-apply { background-color: #007bff; color: white; border: none; cursor: pointer; transition: background-color 0.3s; }
        .btn-apply:hover { background-color: #0056b3; }

        .results-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .results-table th, .results-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .results-table th { background-color: #e9ecef; font-weight: bold; }
        .score-pass { color: #28a745; font-weight: bold; }
        .score-fail { color: #dc3545; font-weight: bold; }
        .message, .error { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>System-Wide Results Overview</h1>
        <div>
            <a href="index.php">Admin Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Filter Results</h2>
        <div class="filter-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET">
                <div class="filter-group">
                    <div class="filter-item">
                        <label for="subject_id">Filter by Subject:</label>
                        <select id="subject_id" name="subject_id">
                            <option value="">-- All Subjects --</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" 
                                    <?php echo $filter_subject_id == $subject['subject_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="quiz_id">Filter by Quiz/Exam:</label>
                        <select id="quiz_id" name="quiz_id">
                            <option value="">-- All Quizzes --</option>
                            <?php foreach ($quizzes as $quiz): // FIX: Looping through $quizzes ?>
                                <option value="<?php echo $quiz['quiz_id']; ?>" 
                                    <?php echo $filter_quiz_id == $quiz['quiz_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($quiz['title']); ?> (Duration: <?php echo $quiz['duration_minutes']; ?> min)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-item" style="flex: 0 0 150px;">
                        <button type="submit" class="btn-apply">Apply Filters</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>Detailed Results List</h2>
        
        <?php if (!empty($results)): ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Quiz/Exam</th>
                        <th>Student</th>
                        <th>Group</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): 
                        // FIX: Changed $r['total_questions'] to $r['total_points']
                        $total_possible = $r['total_points'] > 0 ? $r['total_points'] : 1; 
                        $percentage = number_format(($r['score'] / $total_possible) * 100, 2);
                        // Assuming 50% is a general pass/fail threshold for color coding
                        $score_class = ($percentage >= 50) ? 'score-pass' : 'score-fail';
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['quiz_title']); ?> (<?php echo $r['quiz_duration']; ?> min)</td>
                            <td><?php echo htmlspecialchars($r['username']); ?></td>
                            <td><?php echo htmlspecialchars($r['group_number']); ?></td>
                            <td class="<?php echo $score_class; ?>">
                                <?php echo $r['score']; ?> / <?php echo $r['total_points']; ?> </td>
                            <td class="<?php echo $score_class; ?>">
                                <?php echo $percentage; ?>%
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($r['submission_time'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No results have been recorded for the current filters or no quizzes have been taken yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>