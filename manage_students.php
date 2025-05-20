<?php
session_start();
include 'config.php';

// Ensure only admins can access
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Handle search
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql_students = "SELECT * FROM students WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
} else {
    $sql_students = "SELECT * FROM students";
}
$result_students = $conn->query($sql_students);

// Check if delete_id is set and handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // First get the student's university_id
    $sql_get_university = "SELECT university_id FROM students WHERE id = ?";
    $stmt_get = $conn->prepare($sql_get_university);
    $stmt_get->bind_param("i", $delete_id);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    
    if ($student = $result_get->fetch_assoc()) {
        $university_id = $student['university_id'];
        $stmt_get->close();

        // Start transaction
        $conn->begin_transaction();

        try {
           // 1. Delete related courses first
$sql_delete_courses = "DELETE FROM courses WHERE university_id = ?";
$stmt_courses = $conn->prepare($sql_delete_courses);
$stmt_courses->bind_param("s", $university_id);
$stmt_courses->execute();
$stmt_courses->close();

// Reset auto-increment for courses table
$conn->query("ALTER TABLE courses AUTO_INCREMENT = 1");

// 2. Delete the student's profile picture if it exists
$sql_pic = "SELECT profile_picture FROM students WHERE id = ?";
$stmt_pic = $conn->prepare($sql_pic);
$stmt_pic->bind_param("i", $delete_id);
$stmt_pic->execute();
$result_pic = $stmt_pic->get_result();
if ($row_pic = $result_pic->fetch_assoc()) {
    if (!empty($row_pic['profile_picture']) && file_exists("uploads/" . $row_pic['profile_picture'])) {
        unlink("uploads/" . $row_pic['profile_picture']);
    }
}
$stmt_pic->close();

// 3. Delete the student
$sql_delete_student = "DELETE FROM students WHERE id = ?";
$stmt_student = $conn->prepare($sql_delete_student);
$stmt_student->bind_param("i", $delete_id);
$stmt_student->execute();
$stmt_student->close();

// 4. Reset auto-increment counter for students
$conn->query("ALTER TABLE students AUTO_INCREMENT = 1");


            // Commit transaction
            $conn->commit();

            $_SESSION['message'] = 'Student and related courses deleted successfully!';
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            $conn->rollback();
            $_SESSION['message'] = 'Error deleting student and related data: '.$e->getMessage();
        }
    } else {
        $_SESSION['message'] = 'Student not found!';
    }

    // Redirect after deletion
    header("Location: manage_students.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #f0ad4e;
            --danger-color: #d9534f;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            background-color: #f5f7ff;
            min-height: 100vh;
            color: var(--dark-color);
        }

        /* Sidebar (unchanged) */
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #2C3E50;
            color: #fff;
            padding: 20px;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 15px;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            background: #34495E;
            border-radius: 5px;
            transition: 0.3s;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #1ABC9C;
        }

        /* Main Content */
        .main-content {
            margin-left: 270px;
            padding: 40px;
            flex: 1;
            background-color: #f5f7ff;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: var(--secondary-color);
            font-size: 1.8rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        .page-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        /* Search Box */
        .search-box {
            margin-bottom: 30px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1em;
            transition: var(--transition);
            background-color: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7F8C8D;
        }

        /* Student Table */
        .student-table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            transition: var(--transition);
            overflow-x: auto;
        }

        .student-table-container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .student-table thead tr {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .student-table th, .student-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .student-table tbody tr {
            transition: var(--transition);
            background-color: white;
        }

        .student-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .student-table tbody tr:hover {
            background-color: #f1f3ff;
            transform: scale(1.01);
        }

        .student-table img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eee;
            transition: var(--transition);
        }

        .student-table tr:hover img {
            border-color: var(--accent-color);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn i {
            margin-right: 5px;
            font-size: 0.9em;
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #ec971f;
            transform: translateY(-2px);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #c9302c;
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            text-align: center;
            font-weight: 500;
        }

        .success {
            background-color: rgba(75, 181, 67, 0.2);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .error {
            background-color: rgba(217, 83, 79, 0.2);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        @media (max-width: 992px) {
            .main-content {
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .student-table th, .student-table td {
                padding: 10px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar (unchanged) -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="addstudents.php">Add Students</a></li>
            <li><a href="manage_students.php" class="active">Manage Students</a></li>
            <li><a href="pwreset.php">Reset Password</a></li>
            <li><a href="index.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Manage Students</h1>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo strpos($_SESSION['message'], 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Search Box -->
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <form method="GET">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <!-- Student Table -->
        <div class="student-table-container">
            <table class="student-table">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Batch No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_students->fetch_assoc()): ?>
                    <tr>
                        <td><img src="uploads/<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Profile Picture"></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="edit_student.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="view_student.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                                    <i class="fas fa-edit"></i> view details
                                </a>
                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this student?');">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>