<?php
// Đảm bảo rằng session_start() được gọi đầu tiên
session_start();

// Nếu chưa đăng nhập, chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit(); // Dừng lại sau khi chuyển hướng
}

// Nếu người dùng là Admin, Teacher hoặc Student, thay đổi URL về trang chính tương ứng
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'Admin':
            $homeLink = 'index.php'; // Trang chính cho Admin
            break;
        case 'Teacher':
            $homeLink = 'teacher_dashboard.php'; // Trang chính cho Teacher
            break;
        case 'Student':
            $homeLink = 'student_dashboard.php'; // Trang chính cho Student
            break;
        default:
            $homeLink = 'student_dashboard.php'; // Nếu không có vai trò xác định, quay lại trang mặc định
            break;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            color: white;
            padding: 20px;
            position: fixed;
            width: 250px;
        }
        .sidebar h4 {
            color: #fff;
        }
        .sidebar a {
            text-decoration: none;
            color: #ddd;
            display: block;
            padding: 10px;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background-color: #495057;
            color: #fff;
        }
        .content {
            margin-left: 270px;
            padding: 20px;
        }
        .btn {
            font-size: 14px;
        }
        .modal-backdrop {
            display: none !important;
        }
        .user-info {
            position: fixed;
            top: 20px;
            right: 30px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>Management</h4>
        <a href="#" id="manageTimetable">View Timetable</a>
        <a href="#" id="teacherAttendence">Attendence Report</a>
        <a href="#" id="manageCourses">Manage Courses</a>
        <a href="#" id="manageClasses">Manage Classes</a>
        <a href="#" id="manageGrade">Manage Grades</a>
        <a href="#" id="manageTuition">Manage Tuition</a>
    </div>

    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>

    <div class="content">
        <h2>Welcome to the Student Dashboard</h2>
        <p>Select a menu item to view details here.</p>
        <img src="images/BLOG-technology-in-higher-education@1X.jpg" alt="" width="100%">
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function(){

            // Khi click vào "Manage Courses"
            $('#manageCourses').click(function() {
                $('.content').load('courses.php');
            });

            // Khi click vào "Manage Classes"
            $('#manageClasses').click(function() {
                $('.content').load('class.php');
            });
            // Khi click vào "Manage Grade"
            $('#manageGrade').click(function() {
                $('.content').load('grade.php');
            });
            // Khi click vào "View Timetable"
            $('#manageTimetable').click(function() {
                $('.content').load('timetable.php');
            });
            // Khi click vào "View Tuition"
            $('#manageTuition').click(function() {
                $('.content').load('tuition.php');
            });
            // Khi click vào "Manage Students"
            $('#teacherAttendence').click(function() {
                $('.content').load('attendence_teacher.php');
            });
        });
    </script>
</body>
</html>









