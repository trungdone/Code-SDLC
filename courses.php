<?php
session_start();

// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'StudentManagement');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// Kiểm tra vai trò
$role = $_SESSION['role'] ?? null; // Lấy vai trò từ session

// Nếu không phải Admin hoặc Teacher, chặn các thao tác POST hoặc DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['deleteCourseId'])) {
    if ($role !== 'Admin' && $role !== 'Teacher') {
        die("You do not have permission to perform this action.");
    }
}

// Xử lý yêu cầu POST (Thêm hoặc sửa khóa học)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['addCourse'])) {
        // Thêm khóa học
        $courseName = $_POST['courseName'];
        $courseDescription = $_POST['courseDescription'];

        $sql = "INSERT INTO Courses (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $courseName, $courseDescription);

        if ($stmt->execute()) {
            header('Location: courses.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editCourse'])) {
        // Sửa khóa học
        $id = $_POST['id'];
        $courseName = $_POST['courseName'];
        $courseDescription = $_POST['courseDescription'];

        $sql = "UPDATE Courses SET name = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $courseName, $courseDescription, $id);

        if ($stmt->execute()) {
            header('Location: courses.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Xóa hoặc hiển thị danh sách khóa học)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['deleteCourseId'])) {
        $id = $_GET['deleteCourseId'];
        $sql = "DELETE FROM Courses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header('Location: courses.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Xử lý tìm kiếm khóa học
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchQuery = '';
    if ($search) {
        // Thêm điều kiện tìm kiếm vào truy vấn
        $searchQuery = " WHERE name LIKE ? OR description LIKE ?";
    }

    // Lấy danh sách khóa học với điều kiện tìm kiếm nếu có
    $sql = "SELECT id, name, description FROM Courses $searchQuery";

    // Chuẩn bị câu truy vấn và thực thi
    $stmt = $conn->prepare($sql);
    if ($search) {
        $searchParam = "%" . $search . "%"; // Thêm ký tự % để tìm kiếm linh hoạt
        $stmt->bind_param("ss", $searchParam, $searchParam);
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
    <title>Manage Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
<!-- Home Button -->
<a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>

    <h2 class="mb-4 text-center">Manage Courses</h2>

    <!-- Search Form -->
    <form class="mb-3 d-flex justify-content-center" method="GET" action="courses.php">
        <input type="text" class="form-control me-2" name="search" placeholder="Search by course name or description"
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Add Course Button (Ẩn với Student) -->
    <?php if ($role === 'Admin' || $role === 'Teacher') { ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCourseModal">Add New Course</button>
    <?php } ?>

    <!-- Courses List -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Course Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td>
                        <?php if ($role === 'Admin' || $role === 'Teacher') { ?>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editCourseModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($row['description']); ?>">Edit</button>
                            <a href="courses.php?deleteCourseId=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            <?php } else { ?>
                                No actions allowed
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="4">No courses found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add Course -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="courses.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="courseName" class="form-control mb-2" placeholder="Course Name" required>
                    <textarea name="courseDescription" class="form-control mb-2" placeholder="Course Description" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addCourse" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Course -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="courses.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCourseId" name="id">
                    <input type="text" id="editCourseName" name="courseName" class="form-control mb-2" placeholder="Course Name" required>
                    <textarea id="editCourseDescription" name="courseDescription" class="form-control mb-2" placeholder="Course Description" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editCourse" class="btn btn-warning">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#editCourseModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            $('#editCourseId').val(button.data('id'));
            $('#editCourseName').val(button.data('name'));
            $('#editCourseDescription').val(button.data('description'));
        });
    });
</script>

</body>
</html>


