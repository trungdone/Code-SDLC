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

// Xử lý yêu cầu POST (Thêm hoặc sửa sinh viên)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['addStudent'])) {
        // Thêm sinh viên
        $username = $_POST['username'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $classId = $_POST['classId'];

        $sql = "INSERT INTO Users (username, name, email, classId) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $name, $email, $classId);

        if ($stmt->execute()) {
            header('Location: student.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editStudent'])) {
        // Sửa sinh viên
        $id = $_POST['id'];
        $username = $_POST['username'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $classId = $_POST['classId'];

        $sql = "UPDATE Users SET username = ?, name = ?, email = ?, classId = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $username, $name, $email, $classId, $id);

        if ($stmt->execute()) {
            header('Location: student.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Xóa hoặc hiển thị danh sách sinh viên)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['deleteStudentId'])) {
        $id = $_GET['deleteStudentId'];
        $sql = "DELETE FROM Users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header('Location: student.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Xử lý tìm kiếm
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchQuery = '';
    if ($search) {
        // Thêm điều kiện tìm kiếm vào truy vấn
        $searchQuery = " WHERE Users.username LIKE ? OR Users.name LIKE ? OR Users.email LIKE ? OR Classes.name LIKE ?";
    }

    // Lấy danh sách sinh viên với điều kiện tìm kiếm nếu có
    $sql = "
        SELECT Users.id, Users.username, Users.name, Users.email, Users.classId, Classes.name AS className
        FROM Users
        LEFT JOIN Classes ON Users.classId = Classes.id
        $searchQuery
    ";

    // Chuẩn bị câu truy vấn và thực thi
    $stmt = $conn->prepare($sql);
    if ($search) {
        $searchParam = "%" . $search . "%"; // Thêm ký tự % để tìm kiếm linh hoạt
        $stmt->bind_param("ssss", $searchParam, $searchParam, $searchParam, $searchParam);
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
    <title>Student Management</title>
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
<a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>
    <h2 class="mb-4 text-center">Manage Students</h2>

     <!-- Search Form -->

     <form class="mb-3 d-flex justify-content-center" method="GET" action="student.php">
    <input type="text" class="form-control me-2" name="search" placeholder="Search by name, username, email, or class"
           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="max-width: 400px;">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

    <!-- Thêm sinh viên -->
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add New Student</button>

    <!-- Danh sách sinh viên -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Class</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['className'] ?? 'N/A'; ?></td>
                        <td>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editStudentModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-classid="<?php echo $row['classId'] ?? ''; ?>">Edit</button>
                            <a href="student.php?deleteStudentId=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="6">No students found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="student.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                    <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
                    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
                    <input type="number" name="classId" class="form-control mb-2" placeholder="Class ID">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addStudent" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Student -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="student.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editStudentId" name="id">
                    <input type="text" id="editUsername" name="username" class="form-control mb-2" placeholder="Username" required>
                    <input type="text" id="editName" name="name" class="form-control mb-2" placeholder="Name" required>
                    <input type="email" id="editEmail" name="email" class="form-control mb-2" placeholder="Email" required>
                    <input type="number" id="editClassId" name="classId" class="form-control mb-2" placeholder="Class ID">
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editStudent" class="btn btn-warning">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#editStudentModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            $('#editStudentId').val(button.data('id'));
            $('#editUsername').val(button.data('username'));
            $('#editName').val(button.data('name'));
            $('#editEmail').val(button.data('email'));
            $('#editClassId').val(button.data('classid') || '');
        });
    });
</script>
</body>
</html>






