<?php
// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'StudentManagement');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Phân quyền dựa trên vai trò người dùng
session_start();
$userRole = $_SESSION['role'] ?? 'Student'; // Lấy vai trò từ session, mặc định là 'Student'

// Fetch students and courses for the dropdown menus
$studentsQuery = "SELECT id, name FROM Users";
$coursesQuery = "SELECT id, name FROM Courses";
$studentsResult = $conn->query($studentsQuery);
$coursesResult = $conn->query($coursesQuery);

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

// Xử lý yêu cầu POST (Thêm hoặc sửa điểm)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['addGrade'])) {
        // Thêm điểm
        $userId = $_POST['userId'];
        $courseId = $_POST['courseId'];
        $grade = $_POST['grade'];

        $sql = "INSERT INTO Grades (userId, courseId, grade) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $userId, $courseId, $grade);

        if ($stmt->execute()) {
            header('Location: grade.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editGrade'])) {
        // Sửa điểm
        $userId = $_POST['userId'];
        $courseId = $_POST['courseId'];
        $grade = $_POST['grade'];

        $sql = "UPDATE Grades SET grade = ? WHERE userId = ? AND courseId = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $grade, $userId, $courseId);

        if ($stmt->execute()) {
            header('Location: grade.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Hiển thị danh sách điểm)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Lấy danh sách điểm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchQuery = '';
    if ($search) {
        // Thêm điều kiện tìm kiếm vào truy vấn
        $searchQuery = " WHERE grade LIKE ?";
    }

    $sql = "SELECT g.userId, g.courseId, g.grade, u.name AS student_name, c.name AS course_name 
            FROM Grades g
            JOIN Users u ON g.userId = u.id
            JOIN Courses c ON g.courseId = c.id $searchQuery";

    $stmt = $conn->prepare($sql);
    if ($search) {
        $searchParam = "%" . $search . "%"; // Tìm kiếm linh hoạt
        $stmt->bind_param("s", $searchParam);
    }

    $stmt->execute();
    $result = $stmt->get_result();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h2 class="mb-4 text-center">Manage Grades</h2>
    <a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>


    <!-- Search Form -->
    <form class="mb-3 d-flex justify-content-center" method="GET" action="grade.php">
        <input type="text" class="form-control me-2" name="search" placeholder="Search by grade"
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Add Grade Button (Only for Admin or Staff) -->
    <?php if ($userRole !== 'Student') { ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addGradeModal">Add Grade</button>
    <?php } ?>

    <!-- Grades List -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Course Name</th>
                <th>Grade</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['grade']); ?></td>
                        <td>
                            <?php if ($userRole !== 'Student') { ?>
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editGradeModal"
                                        data-userid="<?php echo $row['userId']; ?>"
                                        data-courseid="<?php echo $row['courseId']; ?>"
                                        data-grade="<?php echo htmlspecialchars($row['grade']); ?>">Edit</button>
                            <?php } else { ?>
                                <span class="text-muted">No actions available</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="4">No grades found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add Grade -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="grade.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Grade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Student Dropdown -->
                    <select name="userId" class="form-control mb-2" required>
                        <option value="">Select Student</option>
                        <?php while ($student = $studentsResult->fetch_assoc()) { ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo $student['name']; ?></option>
                        <?php } ?>
                    </select>
                    <!-- Course Dropdown -->
                    <select name="courseId" class="form-control mb-2" required>
                        <option value="">Select Course</option>
                        <?php while ($course = $coursesResult->fetch_assoc()) { ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo $course['name']; ?></option>
                        <?php } ?>
                    </select>
                    <input type="text" name="grade" class="form-control mb-2" placeholder="Grade" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addGrade" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Grade -->
<div class="modal fade" id="editGradeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="grade.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Grade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="userId" id="editUserId">
                    <input type="hidden" name="courseId" id="editCourseId">
                    <input type="text" name="grade" id="editGrade" class="form-control mb-2" placeholder="Grade" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editGrade" class="btn btn-warning">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#editGradeModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            $('#editUserId').val(button.data('userid'));
            $('#editCourseId').val(button.data('courseid'));
            $('#editGrade').val(button.data('grade'));
        });
    });
</script>
</body>
</html>














