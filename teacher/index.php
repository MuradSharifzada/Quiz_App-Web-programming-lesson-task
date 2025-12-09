<?php
session_start();
// Include the authorization and database configuration files
// NOTE: These files are assumed to exist in the parent directory based on the path "../includes/..."
// require_once '../includes/auth.php';
// require_once '../includes/db_config.php';

// Mock functions for demonstration purposes
function check_role($required_role) {
    if (session_status() == PHP_SESSION_NONE) { session_start(); }
    if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== $required_role) {
        // error_log("Unauthorized access attempt."); 
        // exit;
    }
}

// Ensure only teachers can access this page
$_SESSION['user_id'] = 1; // Mock teacher ID
$_SESSION['role_name'] = 'teacher';
check_role('teacher');

// Get the logged-in teacher's username
$username = $_SESSION['username'] ?? 'Teacher';

// Fetch key metrics or links for the teacher
$dashboard_cards = [
    [
        'title' => 'Manage Quizzes',
        'description' => 'Create, edit, and assign quizzes to groups.',
        'link' => 'manage_quizzes.php', 
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 18.07a4.5 4.5 0 01-1.897 1.13L6 20l.922-2.31c.36-.72.909-1.269 1.629-1.629l7.07-7.071zM19.5 7.72V18a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h10.28" /></svg>',
        'color' => 'bg-indigo-600'
    ],
    [
        'title' => 'View Student Groups',
        'description' => 'Organize and manage your registered student groups.',
        'link' => 'manage_groups.php', 
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a4.5 4.5 0 002.343-5.877c.4-.734.72-1.748.72-2.823A9 9 0 0012 2a9 9 0 00-7.062 14.659 4.47 4.47 0 00-2.28 2.062M18 18.72h1.5a2.25 2.25 0 002.25-2.25V16.5A2.25 2.25 0 0021 14.25h-3a2.25 2.25 0 00-2.25 2.25v1.972l-.71-.71M12 9a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        'color' => 'bg-green-600'
    ],
    [
        'title' => 'Review Results',
        'description' => 'Analyze student quiz scores and performance data.',
        // CORRECTED LINK: Points to the quiz list so the teacher can select a quiz ID
        'link' => 'manage_students_result.php', 
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-white"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5l10.5-10.5 7.5 7.5-10.5 10.5L3 13.5zM12 21v-8M7.5 16.5L10.5 13.5M16.5 7.5L13.5 10.5" /></svg>',
        'color' => 'bg-red-600'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f6; }
        .header { background-color: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #3498db; transition: background-color 0.15s; font-weight: 600; }
        .header a:hover { background-color: #2980b9; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-2xl font-bold">Teacher Dashboard</h1>
        <div class="flex space-x-4">
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="py-8">
            <h2 class="text-4xl font-extrabold text-gray-800 mb-2">Welcome Back, <?php echo htmlspecialchars($username); ?>!</h2>
            <p class="text-lg text-gray-500">Your central hub for managing quizzes and student progress.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($dashboard_cards as $card): ?>
                <a href="<?php echo htmlspecialchars($card['link']); ?>" class="card block hover:shadow-xl transition-shadow duration-300">
                    <div class="flex items-center space-x-4 mb-4">
                        <div class="p-3 rounded-full <?php echo $card['color']; ?>">
                            <?php echo $card['icon']; ?>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($card['title']); ?></h3>
                    </div>
                    <p class="text-gray-600"><?php echo htmlspecialchars($card['description']); ?></p>
                    <div class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800">
                        Go to <?php echo htmlspecialchars($card['title']); ?> &rarr;
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-12">
            <h3 class="text-2xl font-semibold text-gray-800 mb-4">Quick Stats</h3>
            <div class="card p-6">
                <p class="text-gray-500">Statistics (e.g., active quizzes, pending grading, recent student activity) will appear here once relevant functionality is built out.</p>
            </div>
        </div>
    </div>
</body>
</html>