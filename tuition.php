<?php 
// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'StudentManagement');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Phân quyền dựa trên vai trò người dùng
session_start();
$userRole = $_SESSION['role'] ?? 'Student'; // Lấy vai trò từ session, mặc định là 'Student'

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

// Xử lý yêu cầu POST (Chỉ dành cho Admin hoặc Staff)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $userRole !== 'Student') {
    if (isset($_POST['addTuition'])) {
        // Thêm học phí
        $userId = $_POST['userId'];
        $amount = $_POST['amount'];
        $paid = $_POST['paid'];
        $dueDate = $_POST['dueDate'];

        $sql = "INSERT INTO Tuition (userId, amount, paid, dueDate) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idds", $userId, $amount, $paid, $dueDate);

        if ($stmt->execute()) {
            header('Location: tuition.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editTuition'])) {
        // Chỉnh sửa học phí
        $id = $_POST['id'];
        $userId = $_POST['userId'];
        $amount = $_POST['amount'];
        $paid = $_POST['paid'];
        $dueDate = $_POST['dueDate'];

        $sql = "UPDATE Tuition SET userId = ?, amount = ?, paid = ?, dueDate = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddsi", $userId, $amount, $paid, $dueDate, $id);

        if ($stmt->execute()) {
            header('Location: tuition.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Xóa bản ghi học phí - chỉ dành cho Admin hoặc Staff)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['deleteTuitionId']) && $userRole !== 'Student') {
    $id = $_GET['deleteTuitionId'];
    $sql = "DELETE FROM Tuition WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header('Location: tuition.php');
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Lấy danh sách học phí
$sql = "SELECT tuition.id, users.name AS userName, tuition.amount, tuition.paid, tuition.dueDate
        FROM Tuition tuition
        JOIN Users users ON tuition.userId = users.id";
$result = $conn->query($sql);

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tuition</title>
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
    <h2 class="mb-4 text-center">Manage Tuition</h2>
    <a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>

    <!-- Nút Thêm học phí -->
    <?php if ($userRole !== 'Student') { ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addTuitionModal">Add New Tuition</button>
    <?php } ?>

    <!-- Danh sách học phí -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Amount</th>
                <th>Paid</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['userName']; ?></td>
                        <td><?php echo $row['amount']; ?></td>
                        <td><?php echo $row['paid']; ?></td>
                        <td><?php echo $row['dueDate']; ?></td>
                        <td>
                            <?php if ($userRole !== 'Student') { ?>
                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editTuitionModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($row['userName']); ?>"
                                        data-amount="<?php echo htmlspecialchars($row['amount']); ?>"
                                        data-paid="<?php echo htmlspecialchars($row['paid']); ?>"
                                        data-duedate="<?php echo htmlspecialchars($row['dueDate']); ?>">Edit</button>
                                <a href="tuition.php?deleteTuitionId=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            <?php } else { ?>
                                <span class="text-muted">No actions available</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="6">No tuition records found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>


<!-- Modal: Add Tuition -->
<div class="modal fade" id="addTuitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="tuition.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Tuition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="userId">Student</label>
                    <select name="userId" id="userId" class="form-select" required>
                        <!-- Fetch and display students -->
                        <?php
                        $usersResult = $conn->query("SELECT id, name FROM Users");
                        while ($user = $usersResult->fetch_assoc()) {
                            echo "<option value='{$user['id']}'>{$user['name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="amount" class="form-control mb-2" placeholder="Amount" step="0.01" required>
                    <input type="number" name="paid" class="form-control mb-2" placeholder="Paid" step="0.01" required>
                    <input type="date" name="dueDate" class="form-control mb-2" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addTuition" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Tuition -->
<div class="modal fade" id="editTuitionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="tuition.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tuition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editTuitionId">
                    <label for="editUserId">Student</label>
                    <select name="userId" id="editUserId" class="form-select" required>
                        <?php
                        // Fetch students again for the edit form
                        $usersResult = $conn->query("SELECT id, name FROM Users");
                        while ($user = $usersResult->fetch_assoc()) {
                            echo "<option value='{$user['id']}'>{$user['name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="amount" class="form-control mb-2" id="editAmount" placeholder="Amount" step="0.01" required>
                    <input type="number" name="paid" class="form-control mb-2" id="editPaid" placeholder="Paid" step="0.01" required>
                    <input type="date" name="dueDate" class="form-control mb-2" id="editDueDate" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editTuition" class="btn btn-warning">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Script để điền thông tin vào modal chỉnh sửa
    $('#editTuitionModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Chọn nút chỉnh sửa
        var id = button.data('id'); // Lấy ID
        var userName = button.data('username'); // Lấy tên sinh viên
        var amount = button.data('amount'); // Lấy số tiền học phí
        var paid = button.data('paid'); // Lấy số tiền đã trả
        var dueDate = button.data('duedate'); // Lấy ngày hết hạn

        // Điền thông tin vào modal
        var modal = $(this);
        modal.find('#editTuitionId').val(id);
        modal.find('#editUserId').val(userName);
        modal.find('#editAmount').val(amount);
        modal.find('#editPaid').val(paid);
        modal.find('#editDueDate').val(dueDate);
    });
</script>
</body>
</html>

