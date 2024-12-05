<?php
session_start();
include 'db_connection.php';

// Check session for logged-in user
if (!isset($_SESSION['user_id'])) {
    die("You need to login to access this page.");
}

// Get role from session (Student or Teacher)
$userRole = $_SESSION['role'] ?? 'Student'; // Default to 'Student'

// Search handling
$searchQuery = '';
if (isset($_POST['search'])) {
    $searchQuery = trim($_POST['search']);
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


// Handle attendance recording
if ($userRole === 'Teacher' && isset($_POST['attendance'])) {
    $attendanceStatus = $_POST['attendanceStatus'];
    $userId = $_POST['userId'];
    $classId = $_POST['classId'];
    $date = date('Y-m-d');

    try {
        $stmt = $conn->prepare("SELECT id FROM Users WHERE id = ?");
        $stmt->execute([$userId]);
        $userExists = $stmt->fetch();

        $stmt = $conn->prepare("SELECT id FROM Classes WHERE id = ?");
        $stmt->execute([$classId]);
        $classExists = $stmt->fetch();

        if ($userExists && $classExists) {
            $stmt = $conn->prepare("INSERT INTO Attendance (userId, classId, date, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $classId, $date, $attendanceStatus]);

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Attendance recorded successfully!'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid data for attendance.'];
        }
    } catch (Exception $e) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
    }
}

// Fetch courses for the teacher
$stmt = $conn->prepare("
    SELECT DISTINCT c.id AS classId, c.name AS className, co.id AS courseId, co.name AS courseName
    FROM ClassesToCourses cc
    JOIN Classes c ON cc.classId = c.id
    JOIN Courses co ON cc.courseId = co.id
    WHERE cc.courseId IN (
        SELECT courseId FROM ClassesToCourses WHERE classId IN (
            SELECT classId FROM Users WHERE id = ? 
        )
    )
");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch attendance data for the student (if role is 'Student')
if ($userRole === 'Student') {
    $sql = "
        SELECT u.name AS studentName, c.name AS className, a.date, a.status 
        FROM Attendance a
        JOIN Classes c ON a.classId = c.id
        JOIN Users u ON a.userId = u.id
        WHERE a.userId = ?
    ";

    // Add search query filter if search query exists
    if ($searchQuery) {
        $sql .= " AND (u.name LIKE ? OR u.id LIKE ?)";
    }

    $stmt = $conn->prepare($sql);
    if ($searchQuery) {
        $stmt->execute([$_SESSION['user_id'], '%' . $searchQuery . '%', '%' . $searchQuery . '%']);
    } else {
        $stmt->execute([$_SESSION['user_id']]);
    }
    $attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .home-btn {
        position: fixed;
        bottom: 5px;
        left: 20px;
        font-size: 15px;
        padding: 10px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
</style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center">Dashboard</h2>

    <!-- Nút Home ở góc phải -->
    <a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>

    <!-- Display messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type']; ?>">
            <?= $_SESSION['message']['text']; ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="POST" action="attendence_teacher.php">
        <div class="mb-3">
            <input type="text" class="form-control" name="search" placeholder="Search students by name or ID" value="<?= htmlspecialchars($searchQuery); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Search Results -->
    <?php if ($searchQuery): ?>
        <h4>Search Results:</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->prepare("SELECT id, name, email FROM Users WHERE name LIKE ? OR id LIKE ?");
                $stmt->execute(['%' . $searchQuery . '%', '%' . $searchQuery . '%']);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($students as $student):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($student['id']); ?></td>
                        <td><?= htmlspecialchars($student['name']); ?></td>
                        <td><?= htmlspecialchars($student['email']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Display Attendance for Students -->
    <?php if ($userRole === 'Student'): ?>
        <h4>Your Attendance</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendanceRecords as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['studentName']); ?></td>
                        <td><?= htmlspecialchars($record['className']); ?></td>
                        <td><?= htmlspecialchars($record['date']); ?></td>
                        <td><?= htmlspecialchars($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Attendance Form for Teachers -->
    <?php if ($userRole === 'Teacher'): ?>
        <h4>Record Attendance</h4>
        <form method="POST" action="attendence_teacher.php">
            <div class="mb-3">
                <label for="classId" class="form-label">Select Class</label>
                <select name="classId" class="form-select" required>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['classId']; ?>"><?= htmlspecialchars($course['className']); ?> - <?= htmlspecialchars($course['courseName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="userId" class="form-label">Select Student</label>
                <select name="userId" class="form-select" required>
                    <?php
                    $stmt = $conn->prepare("SELECT id, name FROM Users");
                    $stmt->execute();
                    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($students as $student):
                    ?>
                        <option value="<?= $student['id']; ?>"><?= htmlspecialchars($student['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="attendanceStatus" class="form-label">Attendance Status</label>
                <select name="attendanceStatus" class="form-select" required>
                    <option value="Present">Present</option>
                    <option value="Absent">Absent</option>
                    <option value="Late">Late</option>
                </select>
            </div>
            <button type="submit" name="attendance" class="btn btn-primary">Record Attendance</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>

























