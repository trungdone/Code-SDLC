<?php
// Xử lý form khi người dùng gửi yêu cầu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include 'db_connection.php';  // Kết nối cơ sở dữ liệu

    // Lấy thông tin từ form
    $id = $_POST['id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $gender = $_POST['gender'];
    $birthYear = $_POST['birthYear'];
    $classId = $_POST['classId'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $error = "";  // Biến lưu thông báo lỗi

    // Kiểm tra xem tên đăng nhập đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $error = "Username already exists!";
    }

    // Nếu không có lỗi, tiến hành thêm người dùng mới
    if (empty($error)) {
        // Mã hóa mật khẩu trước khi lưu
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Thực hiện insert vào cơ sở dữ liệu
        $stmt = $conn->prepare("INSERT INTO Users (id, username, password, name, gender, birthYear, classId, email) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$id, $username, $hashedPassword, $name, $gender, $birthYear, $classId, $email])) {
            // Redirect đến trang đăng nhập sau khi đăng ký thành công
            header('Location: login.php');
            exit();
        } else {
            $error = "Error occurred while registering. Please try again!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-form {
            max-width: 500px;
            margin: auto;
            margin-top: 30px; /* Giảm khoảng cách từ trên xuống */
            padding: 40px; /* Thêm padding để các phần tử không bị quá sát nhau */
            height: auto; /* Đảm bảo form tự động điều chỉnh chiều cao */
        }
    </style>
</head>
<body>
    <div class="register-form">
        <h3 class="text-center">Register</h3>

        <!-- Hiển thị thông báo lỗi nếu có -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="id" class="form-label">User ID</label>
                <input type="text" class="form-control" id="id" name="id" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Gender</label>
                <select class="form-select" id="gender" name="gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="birthYear" class="form-label">Birth Year</label>
                <input type="number" class="form-control" id="birthYear" name="birthYear" required>
            </div>
            <div class="mb-3">
                <label for="classId" class="form-label">Class ID</label>
                <input type="text" class="form-control" id="classId" name="classId" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select class="form-select" id="role" name="role">
                    <option value="AM01">Admin</option>
                    <option value="TC02">Teacher</option>
                    <option value="US03">Student</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>



