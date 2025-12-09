<?php
// Start output buffering for safe header redirects
ob_start();

// --- PLACEHOLDER for includes/db_config.php and includes/auth.php ---
// NOTE: In a production environment, replace this entire block with:
// require_once 'includes/db_config.php';
// require_once 'includes/auth.php';
$pdo = null; 
if (!isset($pdo)) {
    // Mock PDO object to simulate database connection and results for testing purposes
    $pdo = new class extends PDO { 
        public function __construct() { }
        public function prepare($sql) {
            $self = $this;
            return new class($sql, $self) {
                private $sql;
                private $parent;
                public function __construct($sql, $parent) { $this->sql = $sql; $this->parent = $parent; }
                public function execute($params = []) { 
                    // Simulate checking parameters before execution
                    // if (count($params) < 1) throw new Exception("Missing parameters for query: " . $this->sql);
                    return true; 
                }
                public function fetch($mode = PDO::FETCH_ASSOC) { 
                    // Mock data for quiz title lookup
                    if (strpos($this->sql, 'quizzes WHERE quiz_id') !== false) return ['title' => 'Project Management Fundamentals']; 
                    return false; 
                }
                public function fetchAll($mode = PDO::FETCH_ASSOC) {
                     // Mock detailed student results (latest attempt data + an older one for filtering test)
                     if (strpos($this->sql, 'results r') !== false) return [
                        ['user_id' => 101, 'username' => 'Alice Johnson', 'score' => 85, 'total_points' => 100, 'submission_time' => '2025-12-05 10:00:00', 'percentage' => 85.00],
                        ['user_id' => 102, 'username' => 'Bob Smith', 'score' => 62, 'total_points' => 100, 'submission_time' => '2025-12-05 10:15:00', 'percentage' => 62.00],
                        ['user_id' => 103, 'username' => 'Charlie Brown', 'score' => 98, 'total_points' => 100, 'submission_time' => '2025-12-05 10:30:00', 'percentage' => 98.00],
                        ['user_id' => 101, 'username' => 'Alice Johnson', 'score' => 75, 'total_points' => 100, 'submission_time' => '2025-12-04 15:00:00', 'percentage' => 75.00], // Older submission
                     ];
                     return [];
                }
                public function rowCount() { return 1; }
            };
        }
        public function query($sql) { return $this->prepare($sql); }
        public function exec($sql) { return 1; }
    };
}

// Mock role check function
function check_role($required_role) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== $required_role) {
        // In a real app, this redirects to login
        // header("Location: ../login.php");
        // exit;
    }
}
// --- END PLACEHOLDER ---


// 1. Setup Session and Authorization
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Mocking successful teacher login for context
$_SESSION['user_id'] = 1; 
$_SESSION['role_name'] = 'teacher';
check_role('teacher');

$teacher_id = $_SESSION['user_id'];
$quiz_id = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT);
$error = '';
$quiz_title = 'Quiz Results';
$student_results = [];

if (!$quiz_id) {
    $error = "Error: Invalid or missing Quiz ID provided in the URL. Please check the link.";
}

try {
    if (!$error) {
        // 2. Fetch Quiz Title and verify teacher ownership
        $quiz_sql = "SELECT title FROM quizzes WHERE quiz_id = ? AND teacher_id = ?";
        $quiz_stmt = $pdo->prepare($quiz_sql);
        $quiz_stmt->execute([$quiz_id, $teacher_id]);
        $quiz_data = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quiz_data) {
            $error = "Quiz not found or you do not have permission to view results.";
        } else {
            $quiz_title = htmlspecialchars($quiz_data['title']);
        }
    }
    
    // 3. Fetch All Student Results for the Quiz
    if (!$error) {
        $results_sql = "
            SELECT 
                u.user_id,
                u.username,
                r.score,
                r.total_points,
                r.submission_time,
                (r.score / r.total_points * 100) AS percentage 
            FROM 
                results r
            JOIN 
                users u ON r.student_id = u.user_id
            WHERE 
                r.quiz_id = ?
            ORDER BY 
                u.user_id, r.submission_time DESC
        ";
        
        $results_stmt = $pdo->prepare($results_sql);
        $results_stmt->execute([$quiz_id]); 
        
        $temp_results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);
        $latest_results = [];

        // 4. Filter: Keep only the latest submission per student
        foreach ($temp_results as $result) {
            // Because results are ordered by submission_time DESC, the first one encountered 
            // for a user_id will be the latest.
            if (!isset($latest_results[$result['user_id']])) {
                $latest_results[$result['user_id']] = $result;
            }
        }
        
        // Convert back to indexed array and sort by percentage descending
        $student_results = array_values($latest_results);
        usort($student_results, function($a, $b) {
            return $b['percentage'] <=> $a['percentage']; // Sort descending
        });

        if (empty($student_results) && empty($error)) {
             $error = "No student results found for this quiz yet.";
        }
    }

} catch (PDOException $e) {
    error_log("Teacher results fetch error: " . $e->getMessage());
    $error = "A serious database error occurred. Please check logs.";
}

// Clean up mock PDO connection
unset($pdo);
// End output buffering
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results: <?php echo $quiz_title; ?></title>
    <style>
        /* Base Colors - Sophisticated Navy/Teal Palette */
        :root {
            --primary-color: #3498db; /* Blue */
            --secondary-color: #2c3e50; /* Dark Navy */
            --accent-color: #1abc9c; /* Teal */
            --bg-color: #f4f7f6;
            --card-color: #ffffff;
            --text-color: #34495e;
            --text-muted: #95a5a6;
            --success-color: #2ecc71; 
            --warning-color: #f39c12; /* Orange */
            --danger-color: #e74c3c;
            --low-color: #e67e22; /* Light Orange */
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-color); 
            margin: 0; 
            padding: 0; 
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Header and Navigation */
        .header { 
            background: var(--secondary-color);
            color: white; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); 
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 1.8rem;
        }
        .header-actions a { 
            color: white; 
            text-decoration: none; 
            padding: 8px 18px; 
            border-radius: 20px; 
            background-color: var(--primary-color); 
            margin-left: 10px; 
            transition: background-color 0.3s ease, transform 0.15s ease; 
            font-weight: 600;
        }
        .header-actions a:hover { 
            background-color: #2980b9;
            transform: translateY(-2px); 
        }
        
        /* Main Container */
        .container { 
            max-width: 900px;
            margin: 40px auto; 
            padding: 30px; 
            background: var(--card-color); 
            border-radius: 12px; 
            box-shadow: var(--shadow); 
        }
        
        /* Titles */
        h2 { 
            color: var(--secondary-color); 
            border-bottom: 3px solid var(--accent-color); 
            padding-bottom: 12px; 
            margin-bottom: 40px; 
            font-size: 2rem;
            text-align: center;
        }

        /* Status Messages */
        .message, .error { 
            padding: 18px; 
            margin-bottom: 30px; 
            border-radius: 8px; 
            font-weight: 600; 
            line-height: 1.4;
            border: 1px solid;
        }
        .error { 
            background-color: #f8d7da; 
            color: var(--danger-color); 
            border-color: #f5c6cb; 
        }
        
        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: separate; 
            border-spacing: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden; 
        }

        .results-table th, .results-table td {
            padding: 16px 20px; 
            text-align: left;
        }

        .results-table th {
            background-color: var(--secondary-color);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .results-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .results-table tr {
            transition: background-color 0.3s ease;
        }

        .results-table tr:hover {
            background-color: #f0f8ff; 
        }

        .results-table td {
            border-bottom: 1px solid #e0e0e0;
            font-size: 1rem;
        }
        .results-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Score & Percentage Styling */
        .score-col {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1em;
        }
        
        .percentage-col {
            font-weight: 800;
            padding: 6px 12px;
            border-radius: 4px;
            display: inline-block;
            min-width: 70px;
            text-align: center;
            color: white;
        }
        
        /* Grading Classes */
        .grade-a { background-color: var(--success-color); } /* 90+ */
        .grade-b { background-color: var(--primary-color); } /* 80-89 */
        .grade-c { background-color: var(--warning-color); } /* 70-79 */
        .grade-d { background-color: var(--low-color); } /* 60-69 */
        .grade-f { background-color: var(--danger-color); } /* < 60 */
        
        /* Submission Time Style */
        .time-col {
            color: var(--text-muted);
            font-size: 0.9em;
        }

        /* Responsive adjustments (Mobile first) */
        @media (max-width: 768px) {
            .container {
                margin: 20px 10px;
                padding: 15px;
            }
            .header {
                flex-direction: column;
                padding: 15px;
            }
            .header-actions {
                margin-top: 10px;
                display: flex;
                width: 100%;
                justify-content: center;
            }
            h2 {
                font-size: 1.8rem;
            }
            
            /* Mobile Table Styles (Card View) */
            .results-table {
                box-shadow: none;
                border-radius: 0;
            }
            .results-table thead { 
                display: none; 
            }
            .results-table tbody tr { 
                display: block; 
                margin-bottom: 15px;
                border-radius: 8px;
                border: 1px solid #dcdcdc;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                padding: 10px 0;
            }
            .results-table td { 
                display: block;
                border-bottom: 1px solid #eee;
                padding: 10px 15px;
                text-align: right;
                position: relative; 
            }
            .results-table td:last-child {
                border-bottom: none;
            }
            .results-table td:before { 
                /* Data label for mobile view */
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                font-weight: 600;
                text-align: left;
                color: var(--accent-color);
            }
            .percentage-col {
                float: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Teacher Dashboard</h1>
        <div class="header-actions">
            <!-- Link back to the main quiz management page -->
            <a href="manage_quizzes.php">Back to Quizzes</a>
            <!-- Link to logout (mocked) -->
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Display the title of the quiz -->
        <h2>Results for Quiz: <?php echo $quiz_title; ?></h2>
        
        <?php if (!empty($error)): ?>
            <!-- Display error messages -->
            <div class="message error"><?php echo $error; ?></div>
        <?php elseif (!empty($student_results)): ?>
            
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Score (Latest)</th>
                        <th>Grade</th>
                        <th>Submission Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_results as $result): 
                        $percentage = number_format($result['percentage'], 2);
                        
                        // Define robust grading scale
                        if ($percentage >= 90) {
                            $grade_class = 'grade-a';
                            $grade_letter = 'A';
                        } elseif ($percentage >= 80) {
                            $grade_class = 'grade-b';
                            $grade_letter = 'B';
                        } elseif ($percentage >= 70) {
                            $grade_class = 'grade-c';
                            $grade_letter = 'C';
                        } elseif ($percentage >= 60) {
                            $grade_class = 'grade-d';
                            $grade_letter = 'D';
                        } else {
                            $grade_class = 'grade-f';
                            $grade_letter = 'F';
                        }
                    ?>
                        <tr>
                            <td data-label="Student Name"><?php echo htmlspecialchars($result['username']); ?></td>
                            <td data-label="Score (Latest)" class="score-col"><?php echo htmlspecialchars($result['score']); ?> / <?php echo htmlspecialchars($result['total_points']); ?></td>
                            <td data-label="Grade" class="percentage-col <?php echo $grade_class; ?>">
                                <?php echo $percentage; ?>% (<?php echo $grade_letter; ?>)
                            </td>
                            <td data-label="Submission Time" class="time-col"><?php echo htmlspecialchars($result['submission_time']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
</body>
</html>