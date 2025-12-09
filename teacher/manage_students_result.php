
<?php
// --- Configuration and Security Check ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Start session and ensure user is a teacher
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Enforces that only users with the 'teacher' role can access this page
check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$error = '';
// Safely get the quiz_id from the URL, or null if not provided/invalid
$selected_quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$selected_quiz_title = 'All Quizzes';

// --- PART 1: Fetch Summary of All Quizzes and Metrics for the Teacher ---
$quiz_summaries = [];
try {
    $sql_summary = "
        SELECT 
            q.quiz_id, 
            q.title, 
            s.subject_name,
            -- Count all distinct students who have submitted a result
            COUNT(DISTINCT r.student_id) AS total_attempts,
            -- Calculate the average score (r.score) across all results for this quiz
            AVG(r.score) AS average_score
        FROM quizzes q
        JOIN subjects s ON q.subject_id = s.subject_id
        LEFT JOIN results r ON q.quiz_id = r.quiz_id
        WHERE q.teacher_id = ? 
        GROUP BY q.quiz_id, q.title, s.subject_name
        ORDER BY q.quiz_id DESC
    ";
    $stmt_summary = $pdo->prepare($sql_summary);
    $stmt_summary->execute([$teacher_id]);
    $quiz_summaries = $stmt_summary->fetchAll(PDO::FETCH_ASSOC);

    // If a quiz ID is selected, verify it belongs to the teacher and set the header title
    if ($selected_quiz_id) {
        $found = false;
        foreach ($quiz_summaries as $summary) {
            if ($summary['quiz_id'] == $selected_quiz_id) {
                $selected_quiz_title = htmlspecialchars($summary['title']);
                $found = true;
                break;
            }
        }
        if (!$found) {
             $error = "The selected Quiz ID is invalid or not associated with your account.";
             $selected_quiz_id = null; // Revert to summary view if unauthorized
        }
    }

} catch (PDOException $e) {
    $error .= " Failed to load quiz summaries: " . $e->getMessage();
}

// --- PART 2: Fetch Detailed Results for Selected Quiz (if applicable) ---
$detailed_results = [];
if ($selected_quiz_id) {
    try {
        // FIX: Changed JOIN condition from u.user_id to u.id to match your 'users' table schema.
        $sql_details = "
            SELECT 
                r.result_id AS attempt_id,
                u.username AS student_name,
                r.score AS final_score, 
                r.submission_time
            FROM results r
            JOIN users u ON r.student_id = u.id -- *** FIX APPLIED HERE ***
            JOIN quizzes q ON r.quiz_id = q.quiz_id
            WHERE r.quiz_id = ? AND q.teacher_id = ? 
            ORDER BY r.score DESC, r.submission_time DESC
        ";
        $stmt_details = $pdo->prepare($sql_details);
        $stmt_details->execute([$selected_quiz_id, $teacher_id]);
        $detailed_results = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Updated error message to include the database error for debugging
        $error .= " Failed to load detailed results: " . $e->getMessage();
    }
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Student Results</title>
    <style>
        /* === MODERN STYLING === */
        :root {
            --primary-color: #3b82f6; /* Blue 500 */
            --primary-dark: #2563eb; /* Blue 600 */
            --text-color: #1f2937; /* Dark Gray */
            --bg-light: #f9fafb; /* Very Light Gray */
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --success-color: #10b981; /* Emerald */
            --error-color: #ef4444; /* Red */
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: var(--bg-light); 
            margin: 0; 
            padding: 0; 
            color: var(--text-color);
        }

        /* Header (Navigation Bar) */
        .header { 
            background-color: var(--primary-color); 
            color: white; 
            padding: 18px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
        }
        .header h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 600;
        }
        .header a { 
            color: white; 
            text-decoration: none; 
            padding: 10px 18px; 
            border-radius: 6px; 
            background-color: var(--primary-dark);
            margin-left: 15px; 
            transition: background-color 0.2s, transform 0.1s; 
            font-weight: 500;
        }
        .header a:hover { 
            background-color: #1e40af; /* Darker Blue */
            transform: translateY(-1px);
        }

        /* Main Content Container */
        .container { 
            max-width: 1400px; 
            margin: 40px auto; 
            padding: 30px; 
            background: var(--card-bg); 
            border-radius: 12px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1); 
        }

        /* Headings */
        h2 { 
            color: var(--primary-color); 
            border-bottom: 1px solid var(--border-color); 
            padding-bottom: 15px; 
            margin-bottom: 25px; 
            font-weight: 700;
        }
        
        /* Table Styling */
        .results-section { 
            margin-top: 30px; 
        }
        .results-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 20px; 
            border-radius: 8px;
            overflow: hidden; /* Ensures rounded corners clip table borders */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .results-table th, .results-table td { 
            padding: 15px 20px; 
            text-align: left; 
            border-bottom: 1px solid var(--border-color);
        }
        .results-table th { 
            background-color: #eff6ff; /* Light Blue for Header */
            color: var(--primary-dark);
            font-weight: 600; 
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        .results-table tr:last-child td {
            border-bottom: none;
        }
        .results-table tr:nth-child(even) { 
            background-color: #fcfcfc; 
        }
        .results-table tr:hover { 
            background-color: #f0f9ff; 
            cursor: pointer;
        }

        /* Action Links/Buttons */
        .action-link { 
            display: inline-block; 
            padding: 8px 14px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-size: 0.9rem; 
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .link-view { 
            background-color: var(--primary-color); 
            color: white; 
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.4);
        }
        .link-view:hover { 
            background-color: var(--primary-dark);
        }
        
        /* Message Boxes (Error/Success) */
        .message, .error { 
            padding: 15px; 
            margin-bottom: 25px; 
            border-radius: 8px; 
            font-weight: 600; 
            display: flex;
            align-items: center;
        }
        .success { 
            background-color: #d1fae5; 
            color: var(--success-color); 
            border: 1px solid #a7f3d0; 
        }
        .error { 
            background-color: #fee2e2; 
            color: var(--error-color); 
            border: 1px solid #fecaca; 
        }

        /* Specific Data Highlights */
        .score-avg { 
            font-weight: 700; 
            color: var(--success-color); 
            font-size: 1.1rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Results Dashboard</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="message error">
                <!-- Icon for error could be added here -->
                <p style="margin:0;"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <h2>
            <?php echo $selected_quiz_id ? "Detailed Results for: " . $selected_quiz_title : "Quiz Performance Summary"; ?>
        </h2>
        
        <?php if ($selected_quiz_id): ?>
            <p><a href="manage_students_result.php" class="back-link">← Back to Quiz Summary</a></p>
        <?php endif; ?>

        <!-- === QUIZ SUMMARY TABLE (Default View) === -->
        <?php if (!$selected_quiz_id): ?>
            <div class="results-section">
                <?php if (empty($quiz_summaries)): ?>
                    <p>You have not created any quizzes yet, or no student has attempted your quizzes.</p>
                <?php else: ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Subject</th>
                                <th>Total Attempts</th>
                                <th>Average Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_summaries as $summary): 
                                // Format average score (e.g., 85.5%)
                                $avg_score = $summary['average_score'] !== null ? number_format($summary['average_score'], 1) . '%' : 'N/A';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($summary['title']); ?></td>
                                    <td><?php echo htmlspecialchars($summary['subject_name']); ?></td>
                                    <td><?php echo (int)$summary['total_attempts']; ?></td>
                                    <td class="score-avg"><?php echo $avg_score; ?></td>
                                    <td>
                                        <a href="manage_students_result.php?quiz_id=<?php echo $summary['quiz_id']; ?>" class="action-link link-view">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- === DETAILED RESULTS TABLE (Selected Quiz View) === -->
        <?php if ($selected_quiz_id): ?>
            <div class="results-section">
                <?php if (empty($detailed_results)): ?>
                    <p>No student results found for this quiz.</p>
                <?php else: ?>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Final Score (%)</th>
                                <th>Submission Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailed_results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                    <td>
                                        <!-- Highlight scores with color based on value for quick assessment -->
                                        <strong style="color: <?php echo $result['final_score'] >= 70 ? 'var(--success-color)' : ( $result['final_score'] >= 50 ? '#f59e0b' : 'var(--error-color)' ); ?>;">
                                            <?php echo htmlspecialchars($result['final_score']); ?>%
                                        </strong>
                                    </td>
                                    <td>
                                        <?php echo date('Y-m-d H:i', strtotime($result['submission_time'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
```