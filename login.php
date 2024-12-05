<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'db_connection.php';

    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    try {
        $stmt = $conn->prepare("SELECT u.id, u.password, r.name as role 
                                FROM Users u
                                JOIN UsersToRole ur ON u.id = ur.userId
                                JOIN Roles r ON ur.roleId = r.id
                                WHERE u.username = ? AND LOWER(r.name) = LOWER(?)");
        $stmt->execute([$username, $role]);

        $stmt->setFetchMode(PDO::FETCH_ASSOC); // Đảm bảo định dạng
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid username or role!";
        } elseif ($user['password'] !== $password) {
            $error = "Incorrect password!";
        } else {
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            switch ($user['role']) {
                case 'Admin':
                    header('Location: index.php');
                    break;
                case 'Teacher':
                    header('Location: teacher_dashboard.php');
                    break;
                case 'Student':
                    header('Location: student_dashboard.php');
                    break;
            }
            exit();
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-form" style="max-width: 400px; margin: auto; margin-top: 100px;">
        <h3 class="text-center">Login</h3>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="Admin">Admin</option>
                    <option value="Teacher">Teacher</option>
                    <option value="Student">Student</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3"><a href="register.php">Register here.Create an account.</a></p>
    </div>
</body>
</html>



