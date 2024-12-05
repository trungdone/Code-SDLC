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

// Kiểm tra vai trò người dùng
$userRole = $_SESSION['role'] ?? ''; // Lấy role từ session
$isAdminOrTeacher = ($userRole === 'Admin' || $userRole === 'Teacher');

// Xử lý yêu cầu POST (Thêm hoặc sửa lớp học)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!$isAdminOrTeacher) {
        die("You do not have permission to perform this action.");
    }

    if (isset($_POST['addClass'])) {
        // Thêm lớp học
        $className = $_POST['className'];

        $sql = "INSERT INTO Classes (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $className);

        if ($stmt->execute()) {
            header('Location: class.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editClass'])) {
        // Sửa lớp học
        $id = $_POST['id'];
        $className = $_POST['className'];

        $sql = "UPDATE Classes SET name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $className, $id);

        if ($stmt->execute()) {
            header('Location: class.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Xóa hoặc hiển thị danh sách lớp học)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if ($isAdminOrTeacher && isset($_GET['deleteClassId'])) {
        $id = $_GET['deleteClassId'];
        $sql = "DELETE FROM Classes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header('Location: class.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Xử lý tìm kiếm lớp học
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchQuery = '';
    if ($search) {
        $searchQuery = " WHERE name LIKE ?";
    }

    // Lấy danh sách lớp học với điều kiện tìm kiếm nếu có
    $sql = "SELECT id, name FROM Classes $searchQuery";

    $stmt = $conn->prepare($sql);
    if ($search) {
        $searchParam = "%" . $search . "%";
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
    <title>Manage Classes</title>
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
    <h2 class="mb-4 text-center">Manage Classes</h2>
    <a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>

    <!-- Search Form -->
    <form class="mb-3 d-flex justify-content-center" method="GET" action="class.php">
        <input type="text" class="form-control me-2" name="search" placeholder="Search by class name"
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Add Class Button -->
    <?php if ($userRole !== 'Student') { ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addClassModal">Add New Class</button>
    <?php } ?>

    <!-- Classes List -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td>
                            <?php if ($userRole !== 'Student') { ?>
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['name']); ?>">Edit</button>
                                <a href="class.php?deleteClassId=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            <?php } else { ?>
                                <span class="text-muted">No actions available</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="3">No classes found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add Class -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="class.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="className" class="form-control mb-2" placeholder="Class Name" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addClass" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Class -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="class.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editClassId" name="id">
                    <input type="text" id="editClassName" name="className" class="form-control mb-2" placeholder="Class Name" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editClass" class="btn btn-warning">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('#editClassModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            $('#editClassId').val(button.data('id'));
            $('#editClassName').val(button.data('name'));
        });
    });
</script>
</body>
</html>





