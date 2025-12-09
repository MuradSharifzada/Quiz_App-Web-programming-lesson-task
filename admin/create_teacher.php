<?php
include_once '../includes/db_config.php';
include_once '../includes/auth.php';
check_role('admin');

$username = $password = "";
$username_err = $password_err = "";
$success_msg = "";

// Role ID for 'teacher'
$sql_role = "SELECT role_id FROM roles WHERE role_name = 'teacher'";
$stmt_role = $pdo->query($sql_role);
$role_id = $stmt_role->fetchColumn();

if (!$role_id) {
    // Critical error if 'teacher' role is not in the DB
    $success_msg = "Error: 'teacher' role not found in the roles table. Cannot create account.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $role_id) {
    // 1. Validate username
    $username = trim($_POST["username"]);
    if (empty($username)) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Check if username already exists
        // FIX: Using 'id' for user_id to match primary key in schema
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $username, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $username_err = "This username is already taken.";
        }
    }

    // 2. Validate password
    $password = $_POST["password"];
    if (empty($password)) {
        $password_err = "Please enter a password.";
    } elseif (strlen($password) < 6) {
        $password_err = "Password must have at least 6 characters.";
    }

    // 3. Insert user if no errors
    if (empty($username_err) && empty($password_err)) {
        
        // --- SECURITY WARNING: INSECURE PLAIN TEXT STORAGE (as requested) ---
        $plain_password = $password;

        // FIX: Changed password_hash to 'password' (to match login.php and schema assumption)
        // FIX: Added placeholder columns (email, first_name, last_name) needed for NOT NULL safety
        $sql = "INSERT INTO users (username, password, role_id, email, first_name, last_name, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(1, $username, PDO::PARAM_STR);
            $stmt->bindParam(2, $plain_password, PDO::PARAM_STR);
            $stmt->bindParam(3, $role_id, PDO::PARAM_INT);
            
            // Temporary default/placeholder values for NOT NULL columns
            $default_email = "{$username}@teacher.local";
            $default_first = "Teacher";
            $default_last = $username;

            $stmt->bindParam(4, $default_email, PDO::PARAM_STR);
            $stmt->bindParam(5, $default_first, PDO::PARAM_STR);
            $stmt->bindParam(6, $default_last, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $success_msg = "Teacher account for **" . htmlspecialchars($username) . "** created successfully! Role: Teacher";
                // Clear inputs
                $username = $password = "";
            } else {
                $success_msg = "Error creating account. Please try again.";
            }
        }
    }
}
unset($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Create Teacher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f4f8; }
        .header { background-color: #4e73df; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; background-color: #2e59d9; margin-left: 10px; }
        .header a:hover { background-color: #1a396e; }
        .container { max-width: 600px; margin: 30px auto; padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .error { color: #dc3545; font-size: 0.9em; margin-top: 5px; }
        .success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Create Teacher Account</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="../logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>New Teacher Details</h2>

        <?php if (!empty($success_msg)): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <input type="text" name="username" id="username" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($username); ?>" required>
                <span class="error"><?php echo $username_err; ?></span>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input type="password" name="password" id="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <span class="error"><?php echo $password_err; ?></span>
            </div>

            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150">
                Create Teacher
            </button>
        </form>
    </div>
</body>
</html>