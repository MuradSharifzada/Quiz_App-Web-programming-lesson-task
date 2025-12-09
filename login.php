<?php
// login.php

// --- 1. CONFIG & SETUP ---
// FIX: Corrected path for root-level files (removed ../)
require_once 'includes/db_config.php'; 

// Start session to use $_SESSION variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- 2. Check for existing session and redirect ---
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    switch ($_SESSION['role_name']) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'teacher':
            header("Location: teacher/index.php");
            break;
        case 'student':
            header("Location: student/index.php");
            break;
    }
    exit;
}

$error = '';
// --- 3. Handle POST Request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $group_number = trim($_POST["group_number"] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        // Query to fetch user data including the role_id
        $sql = "SELECT id, password, role_id, group_number FROM users WHERE username = :username";
        
        // Add group number check for students
        if (!empty($group_number)) {
            $sql .= " AND group_number = :group_number";
        }

        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":username", $username);
            if (!empty($group_number)) {
                $stmt->bindParam(":group_number", $group_number);
            }

            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // --- Plain text password comparison ---
                    // NOTE: Use password_verify() with password_hash() for production security!
                    if ($password === $row['password']) {
                        
                        // Success! Create session
                        $_SESSION['user_id'] = $row['id']; 
                        $_SESSION['role_id'] = $row['role_id'];
                        $_SESSION['username'] = $username;

                        // Fetch role name using helper function from db_config.php
                        $role_name = get_role_name_by_id($pdo, $row['role_id']);
                        $_SESSION['role_name'] = $role_name;

                        // Redirect based on role
                        switch ($role_name) {
                            case 'admin':
                                header("Location: admin/index.php");
                                break;
                            case 'teacher':
                                header("Location: teacher/index.php");
                                break;
                            case 'student':
                                header("Location: student/index.php");
                                break;
                            default:
                                $error = "User role not recognized.";
                                break;
                        }
                        exit;
                    } else {
                        $error = "The password you entered was not valid.";
                    }
                } else {
                    $error = "No account found with that username/group combination.";
                }
            } else {
                $error = "Oops! Something went wrong with the database query.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Quiz System</title>
    <style>
        /* Embedded CSS for Login Page */
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; color: #333; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .error { color: #D9534F; margin-bottom: 15px; text-align: center; }
        .btn-login { background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; transition: background-color 0.3s; }
        .btn-login:hover { background-color: #4cae4c; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="group_number">Group Number (Students Only):</label>
                <input type="text" id="group_number" name="group_number">
            </div>
            <div>
                <button type="submit" class="btn-login">Login</button>
            </div>
        </form>
    </div>
</body>
</html>