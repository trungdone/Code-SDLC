<?php
session_start();

// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'StudentManagement');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Kiểm tra vai trò
$role = $_SESSION['role'] ?? null; // Lấy vai trò từ session

// Nếu không phải Admin hoặc Teacher, chặn các thao tác POST hoặc DELETE
if ($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['deleteTimetableId'])) {
    if ($role !== 'Admin' && $role !== 'Teacher') {
        die("You do not have permission to perform this action.");
    }
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

// Xử lý yêu cầu POST (Thêm hoặc sửa lịch học)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['addTimetable'])) {
        // Thêm lịch học
        $classId = $_POST['classId'];
        $courseId = $_POST['courseId'];
        $day = $_POST['day'];
        $time = $_POST['time'];

        $sql = "INSERT INTO Timetable (classId, courseId, day, time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $classId, $courseId, $day, $time);

        if ($stmt->execute()) {
            header('Location: timetable.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['editTimetable'])) {
        // Sửa lịch học
        $id = $_POST['id'];
        $classId = $_POST['classId'];
        $courseId = $_POST['courseId'];
        $day = $_POST['day'];
        $time = $_POST['time'];

        $sql = "UPDATE Timetable SET classId = ?, courseId = ?, day = ?, time = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissi", $classId, $courseId, $day, $time, $id);

        if ($stmt->execute()) {
            header('Location: timetable.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Xử lý yêu cầu GET (Xóa hoặc hiển thị danh sách lịch học)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['deleteTimetableId'])) {
        $id = $_GET['deleteTimetableId'];
        $sql = "DELETE FROM Timetable WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header('Location: timetable.php');
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Xử lý tìm kiếm lịch học
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $searchQuery = '';
    if ($search) {
        // Thêm điều kiện tìm kiếm vào truy vấn
        $searchQuery = " WHERE day LIKE ? OR time LIKE ?";
    }

    // Lấy danh sách lịch học với điều kiện tìm kiếm nếu có
    $sql = "SELECT timetable.id, classes.name AS className, courses.name AS courseName, timetable.day, timetable.time, timetable.courseId
            FROM Timetable timetable
            JOIN Classes classes ON timetable.classId = classes.id
            JOIN Courses courses ON timetable.courseId = courses.id $searchQuery";

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
    <title>Manage Timetable</title>
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
    <h2 class="mb-4 text-center">Manage Timetable</h2>
    <a href="<?php echo $homeLink; ?>" class="btn btn-primary home-btn">Home</a>

    <!-- Search Form -->
    <form class="mb-3 d-flex justify-content-center" method="GET" action="timetable.php">
        <input type="text" class="form-control me-2" name="search" placeholder="Search by day or time"
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="max-width: 400px;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Add Timetable Button -->
        <!-- Add Timetable Button (Ẩn với Student) -->
        <?php if ($role === 'Admin' || $role === 'Teacher') { ?>
        <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addTimetableModal">Add New Timetable</button>
    <?php } ?>

    <!-- Timetable List -->
    <table class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Course Name</th>
                <th>Day</th>
                <th>Time</th>
                <th>Actions</th>
            </tr>
            
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['className']; ?></td>
                        <td><?php echo $row['courseName']; ?></td>
                        <td><?php echo $row['day']; ?></td>
                        <td><?php echo $row['time']; ?></td>
                        <td>
                        <?php if ($role === 'Admin' || $role === 'Teacher') { ?>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editTimetableModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-classname="<?php echo htmlspecialchars($row['className']); ?>"
                                    data-coursename="<?php echo htmlspecialchars($row['courseName']); ?>"
                                    data-day="<?php echo htmlspecialchars($row['day']); ?>"
                                    data-time="<?php echo htmlspecialchars($row['time']); ?>"
                                    data-courseid="<?php echo htmlspecialchars($row['courseId']); ?>">Edit</button>
                            <a href="timetable.php?deleteTimetableId=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                            <?php } else { ?>
                                No actions allowed
                            <?php } ?>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr><td colspan="6">No timetable found.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Modal: Add Timetable -->
<div class="modal fade" id="addTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="timetable.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="classId">Class</label>
                    <select name="classId" id="classId" class="form-select" required>
                        <!-- Fetch and display classes -->
                        <?php
                        $classesResult = $conn->query("SELECT id, name FROM Classes");
                        while ($class = $classesResult->fetch_assoc()) {
                            echo "<option value='{$class['id']}'>{$class['name']}</option>";
                        }
                        ?>
                    </select>
                    <label for="courseId">Course</label>
                    <select name="courseId" id="courseId" class="form-select" required>
                        <!-- Fetch and display courses -->
                        <?php
                        $coursesResult = $conn->query("SELECT id, name FROM Courses");
                        while ($course = $coursesResult->fetch_assoc()) {
                            echo "<option value='{$course['id']}'>{$course['name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="day" class="form-control mb-2" placeholder="Day (e.g., Monday)" required>
                    <input type="text" name="time" class="form-control mb-2" placeholder="Time (e.g., 10:00)" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addTimetable" class="btn btn-success">Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Timetable -->
<div class="modal fade" id="editTimetableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="timetable.php">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Timetable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editTimetableId">
                    <label for="editClassId">Class</label>
                    <select name="classId" id="editClassId" class="form-select" required>
                        <?php
                        // Fetch classes again for the edit form
                        $classesResult = $conn->query("SELECT id, name FROM Classes");
                        while ($class = $classesResult->fetch_assoc()) {
                            echo "<option value='{$class['id']}'>{$class['name']}</option>";
                        }
                        ?>
                    </select>
                    <label for="editCourseId">Course</label>
                    <select name="courseId" id="editCourseId" class="form-select" required>
                        <?php
                        // Fetch courses again for the edit form
                        $coursesResult = $conn->query("SELECT id, name FROM Courses");
                        while ($course = $coursesResult->fetch_assoc()) {
                            echo "<option value='{$course['id']}'>{$course['name']}</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="day" class="form-control mb-2" id="editDay" placeholder="Day (e.g., Monday)" required>
                    <input type="text" name="time" class="form-control mb-2" id="editTime" placeholder="Time (e.g., 10:00)" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editTimetable" class="btn btn-warning">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Script to fill the Edit Timetable Modal with existing data when 'Edit' is clicked
$('#editTimetableModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var id = button.data('id');
    var className = button.data('classname');
    var courseName = button.data('coursename');
    var day = button.data('day');
    var time = button.data('time');
    var courseId = button.data('courseid');

    var modal = $(this);
    modal.find('#editTimetableId').val(id);
    modal.find('#editClassId').val(className);
    modal.find('#editCourseId').val(courseId);
    modal.find('#editDay').val(day);
    modal.find('#editTime').val(time);
});
</script>

</body>
</html>














