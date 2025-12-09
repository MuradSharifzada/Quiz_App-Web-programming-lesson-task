<?php
// --- START: Authentication and Setup ---
include_once '../includes/db_config.php';
include_once '../includes/auth.php';

// Start session and check authentication
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$submission_status = isset($_GET['submitted']) && $_GET['submitted'] === 'true' ? 'success' : '';
$error = '';
$results_summary = [];
$detailed_results = [];
$quiz_title = 'Quiz Results';
$result_id = 0; // ID for detailed fetch

if ($quiz_id <= 0) {
    $error = "Invalid quiz ID provided.";
}

try {
    if (!$error) {
        // Fetch latest result summary + result_id
        $summary_sql = "
            SELECT 
                r.result_id,             
                r.score, 
                r.total_points, 
                r.submission_time,
                q.title AS quiz_title
            FROM 
                results r
            JOIN 
                quizzes q ON r.quiz_id = q.quiz_id
            WHERE 
                r.student_id = ? AND r.quiz_id = ?
            ORDER BY 
                r.submission_time DESC
            LIMIT 1
        ";
        $summary_stmt = $pdo->prepare($summary_sql);
        $summary_stmt->execute([$student_id, $quiz_id]);
        $results_summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$results_summary) {
            $error = "No results found for this quiz. Please ensure you submitted it.";
        } else {
            $quiz_title = htmlspecialchars($results_summary['quiz_title']);
            $result_id = $results_summary['result_id'];
        }
    }

    // Fetch detailed question results
    if (!$error && $result_id > 0) {
        $detailed_sql = "
            SELECT
                q.question_id,
                q.question_text,
                q.correct_option,
                q.option_a,
                q.option_b,
                q.option_c,
                q.option_d,
                sa.submitted_answer_value,
                sa.is_correct
            FROM
                questions q
            LEFT JOIN
                submitted_answers sa 
                ON q.question_id = sa.question_id 
                AND sa.result_id = ?
            WHERE
                q.quiz_id = ?
            ORDER BY
                q.question_id ASC
        ";
        $detailed_stmt = $pdo->prepare($detailed_sql);
        $detailed_stmt->execute([$result_id, $quiz_id]);
        $detailed_results = $detailed_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detailed_results)) {
            $error = "Could not load detailed question data for this quiz.";
        }
    }
} catch (PDOException $e) {
    error_log("Detailed results fetch error: " . $e->getMessage());
    $error = "Database error while fetching results. Details: " . $e->getMessage();
}

unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo $quiz_title; ?> - Student Results</title>
<style>
    :root {
        --primary-color: #007bff;
        --primary-dark: #0056b3;
        --secondary-color: #f8f9fa;
        --card-background: #ffffff;
        --text-color: #343a40;
        --success-color: #28a745;
        --danger-color: #dc3545;
        --warning-color: #ffc107;
    }

    body { 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--secondary-color);
        margin: 0; padding: 0;
        color: var(--text-color);
        line-height: 1.6;
    }

    .header { 
        background: var(--primary-color);
        color: white; 
        padding: 15px 30px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header h1 { margin: 0; font-weight: 600; font-size: 1.6rem; }
    .header a { 
        color: white; text-decoration: none; padding: 8px 18px;
        border-radius: 4px; background-color: var(--primary-dark);
        margin-left: 10px; font-weight: 500;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: background-color 0.2s;
    }
    .header a:hover { background-color: #004085; }

    .container { 
        max-width: 960px;
        margin: 40px auto;
        padding: 30px;
        background: var(--card-background);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    h1 { 
        color: var(--primary-dark);
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 15px;
        margin-bottom: 30px;
        font-size: 2.2rem;
        font-weight: 700;
    }

    h2 {
        color: var(--text-color);
        margin-top: 30px;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 10px;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 8px;
        font-weight: 600;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 8px;
        font-weight: 600;
    }

    .summary-box {
        background: #e9f5ff;
        border: 1px solid #b8daff;
        padding: 20px 10px;
        margin-bottom: 40px;
        border-radius: 10px;
        display: flex;
        justify-content: space-around;
        font-size: 1.1em;
        text-align: center;
    }
    .summary-item {
        flex: 1;
        padding: 0 15px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .summary-item span {
        font-weight: 500;
        color: #6c757d;
        font-size: 0.9em;
        margin-bottom: 5px;
    }
    .summary-item strong {
        font-size: 2.8em;
        color: var(--primary-dark);
        line-height: 1;
    }
    .summary-item:not(:last-child) {
        border-right: 1px solid #c2e0f4;
    }

    .question-detail {
        background: var(--card-background);
        padding: 25px;
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 6px solid;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        transition: box-shadow 0.3s ease-in-out;
    }
    .question-detail:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .correct {
        border-left-color: var(--success-color);
        background-color: #f6fff8;
    }
    .incorrect {
        border-left-color: var(--danger-color);
        background-color: #fff6f6;
    }

    .question-detail h3 {
        margin-top: 0;
        color: var(--primary-dark);
        font-size: 1.2rem;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .option-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .option-item {
        padding: 10px 15px;
        margin-bottom: 8px;
        border-radius: 6px;
        font-size: 1em;
        color: var(--text-color);
        border: 1px solid #f1f1f1;
        transition: border-color 0.15s;
    }
    .option-item strong {
        color: #6c757d;
        margin-right: 5px;
    }

    .correct-highlight {
        background-color: #e2f0e6;
        color: #1e713e;
        font-weight: 600;
        border: 1px solid var(--success-color);
    }
    .submitted-highlight {
        background-color: #fff9e6;
        color: #856404;
        border: 1px solid #ffeeba;
        font-style: italic;
        position: relative;
    }

    .answer-info {
        padding-top: 15px;
        margin-top: 15px;
        border-top: 1px dashed #e9ecef;
        font-size: 1em;
    }
    .answer-info strong {
        font-weight: 700;
        color: var(--text-color);
        margin-right: 5px;
    }
    .correct-answer {
        font-weight: 700;
        color: var(--success-color);
    }
    .incorrect-answer {
        font-weight: 700;
        color: var(--danger-color);
    }

    @media (max-width: 768px) {
        .container {
            margin: 20px 15px;
            padding: 20px;
        }
        .header {
            flex-direction: column;
            padding: 15px;
        }
        .header div {
            margin-top: 10px;
            display: flex;
            width: 100%;
            justify-content: center;
        }
        .header a {
            padding: 6px 12px;
            font-size: 0.9rem;
        }
        .summary-box {
            flex-direction: column;
            padding: 15px;
        }
        .summary-item {
            border-right: none !important;
            border-bottom: 1px solid #c2e0f4;
            margin-bottom: 15px;
            padding-bottom: 15px;
        }
        .summary-item:last-child {
            border-bottom: none !important;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .summary-item strong {
            font-size: 2.2em;
        }
        h1 {
            font-size: 1.8rem;
        }
    }
</style>
</head>
<body>
    <div class="header">
        <h1>Student Dashboard</h1>
        <div>
            <a href="index.php">Back to Quizzes</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1><?php echo $quiz_title; ?></h1>

        <?php if ($submission_status === 'success'): ?>
            <div class="message success">✅ Quiz successfully submitted and scored!</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($results_summary)): ?>

            <div class="summary-box">
                <div class="summary-item">
                    <span>Total Score:</span>
                    <strong><?php echo htmlspecialchars($results_summary['score']); ?> / <?php echo htmlspecialchars($results_summary['total_points']); ?></strong>
                </div>
                <div class="summary-item">
                    <span>Final Percentage:</span>
                    <strong><?php echo number_format(($results_summary['score'] / $results_summary['total_points']) * 100, 2); ?>%</strong>
                </div>
                <div class="summary-item">
                    <span>Submitted On:</span>
                    <strong><?php echo htmlspecialchars($results_summary['submission_time']); ?></strong>
                </div>
            </div>

            <h2>Detailed Breakdown</h2>

            <?php foreach ($detailed_results as $index => $detail): 
                $is_correct = $detail['is_correct'] ?? 0;

                // Handle null or empty submitted answers gracefully
                $submitted_raw = $detail['submitted_answer_value'];
                $submitted_val = ($submitted_raw === null || $submitted_raw === '') ? 'N/A' : $submitted_raw;
                $submitted = htmlspecialchars($submitted_val);

                $correct_opt = htmlspecialchars($detail['correct_option'] ?? 'N/A');
                $css_class = $is_correct ? 'correct' : 'incorrect';

                if ($is_correct === null) $css_class = 'incorrect'; // Default to incorrect styling if unknown
            ?>
                <div class="question-detail <?php echo $css_class; ?>">
                    <h3><?php echo $index + 1; ?>. <?php echo htmlspecialchars($detail['question_text']); ?></h3>

                    <ul class="option-list">
                        <?php
                        $options_map = [
                            'A' => $detail['option_a'] ?? 'Option A Text Missing',
                            'B' => $detail['option_b'] ?? 'Option B Text Missing',
                            'C' => $detail['option_c'] ?? 'Option C Text Missing',
                            'D' => $detail['option_d'] ?? 'Option D Text Missing',
                        ];

                        foreach ($options_map as $option_letter => $option_text) {
                            $option_classes = 'option-item';
                            $marker = '';

                            if ($option_letter === $correct_opt) {
                                $option_classes .= ' correct-highlight';
                                $marker = ' (Correct Answer)';
                            }
                            if ($option_letter === $submitted && !$is_correct) {
                                $option_classes .= ' submitted-highlight';
                                $marker .= ' (Your Incorrect Answer)';
                            } elseif ($option_letter === $submitted && $is_correct) {
                                $marker .= ' (Your Correct Answer)';
                            }

                            echo "<li class='{$option_classes}'>";
                            echo "<strong>{$option_letter}.</strong> " . htmlspecialchars($option_text) . " " . $marker;
                            echo "</li>";
                        }
                        ?>
                    </ul>

                    <div class="answer-info">
                        <strong>Status:</strong> 
                        <?php if ($is_correct): ?>
                            <span class="correct-answer">✅ Correct</span>
                        <?php else: ?>
                            <span class="incorrect-answer">❌ Incorrect</span>
                            (You chose <?php echo $submitted === 'N/A' ? 'nothing' : $submitted; ?>)
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</body>
</html>
