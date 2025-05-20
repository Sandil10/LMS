<?php
session_start();
include 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure only admins can access
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch all courses for this student
$university_id = $student['university_id'];
$stmt = $conn->prepare("SELECT * FROM courses WHERE university_id = ?");
$stmt->bind_param("s", $university_id);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate summary statistics
$total_courses = count($courses);
$total_fees = 0;
$total_paid = 0;
$total_due = 0;

foreach ($courses as &$course) {
    $course['due_amount'] = $course['course_fee'] - $course['amount_paid'];
    $total_fees += $course['course_fee'];
    $total_paid += $course['amount_paid'];
    $total_due += $course['due_amount'];
    
    // Calculate overall status
    $failed_subjects = 0;
    for ($i = 1; $i <= 4; $i++) {
        if (isset($course["subject_{$i}_result"]) && $course["subject_{$i}_result"] < 40) {
            $failed_subjects++;
        }
    }
    $course['overall_status'] = ($failed_subjects == 0) ? 'pass' : 'fail';
}
unset($course);

$payment_completion = ($total_fees > 0) ? round(($total_paid / $total_fees) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #43aa8b;
            --light: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--light);
            color: #333;
            min-height: 100vh;
            font-size: 16px;
        }

        .main-content {
            width: 100%;
            padding: 25px;
        }

        /* Top Navigation */
        .top-navbar {
            background: var(--white);
            box-shadow: var(--shadow);
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .breadcrumb {
            display: flex;
            list-style: none;
            font-size: 1rem;
            color: var(--gray);
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            padding: 0 8px;
        }

        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .user-menu {
            display: flex;
            align-items: center;
        }

        .user-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 12px;
            border: 2px solid var(--primary);
        }

        .user-name {
            font-weight: 600;
            margin-right: 18px;
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Cards */
        .card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: none;
        }

        .card-header {
            padding: 18px 25px;
            background: transparent;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h6 {
            font-weight: 600;
            margin: 0;
            color: var(--primary);
            font-size: 1.3rem;
        }

        .card-body {
            padding: 25px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            border-left: 5px solid;
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-icon {
            position: absolute;
            right: 25px;
            top: 25px;
            font-size: 2.5rem;
            opacity: 0.15;
        }

        .stat-card .stat-title {
            font-size: 1rem;
            color: var(--gray);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .stat-card .stat-change {
            font-size: 1rem;
        }

        /* Progress Bar */
        .progress {
            height: 10px;
            background-color: var(--gray-light);
            border-radius: 5px;
            margin-bottom: 12px;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1.1rem;
        }

        .table th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 600;
            padding: 15px 20px;
            text-align: left;
        }

        .table td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }

        .btn i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #e5177e;
        }

        /* Student Profile */
        .student-profile {
            display: flex;
            align-items: center;
            margin-bottom: 35px;
        }

        .student-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: var(--shadow);
            margin-right: 25px;
        }

        .student-info h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: var(--primary);
        }

        .student-info p {
            color: var(--gray);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        /* Grading Results */
        .grade-card {
            background: var(--white);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: var(--shadow);
        }

        .grade-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-light);
        }

        .grade-title {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .grade-value {
            font-weight: 700;
            font-size: 1.3rem;
        }

        .grade-A { color: #43aa8b; }
        .grade-B { color: #4895ef; }
        .grade-C { color: #f8961e; }
        .grade-D { color: #f3722c; }
        .grade-F { color: #f72585; }

        .grade-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .grade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .grade-label {
            font-weight: 500;
            color: var(--gray);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            .student-profile {
                flex-direction: column;
                text-align: center;
            }
            .student-avatar {
                margin-right: 0;
                margin-bottom: 20px;
            }
            .grade-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="students.php">Students</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($student['name']); ?></li>
                </ul>
                <h1>Student Dashboard</h1>
            </div>
            <div class="user-menu">
                <span class="user-name"><?php echo $_SESSION['name']; ?></span>
                <img src="assets/img/admin-avatar.jpg" alt="Admin" class="user-img">
            </div>
        </div>

        <!-- Student Profile -->
        <div class="card">
            <div class="card-body">
                <div class="student-profile">
                    <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Student" class="student-avatar">
                    <div class="student-info">
                        <h2><?php echo htmlspecialchars($student['name']); ?></h2>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                        <div>
                            <span class="badge badge-primary"><?php echo htmlspecialchars($student['portal_status']); ?></span>
                            <span class="badge badge-success"><?php echo htmlspecialchars($student['batch_no']); ?></span>
                            <span class="badge badge-info"><?php echo htmlspecialchars($student['study_location']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-3">
                        <small class="text-muted">Registration Number</small>
                        <p class="font-weight-bold" style="font-size: 1.2rem;"><?php echo htmlspecialchars($student['reg_number']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">University ID</small>
                        <p class="font-weight-bold" style="font-size: 1.2rem;"><?php echo htmlspecialchars($student['university_id']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Batch Number</small>
                        <p class="font-weight-bold" style="font-size: 1.2rem;"><?php echo htmlspecialchars($student['batch_no']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Study Location</small>
                        <p class="font-weight-bold" style="font-size: 1.2rem;"><?php echo htmlspecialchars($student['study_location']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="card stat-card primary">
                <div class="card-body">
                    <div class="stat-title">Enrolled Courses</div>
                    <div class="stat-value"><?php echo $total_courses; ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> Active
                    </div>
                    <i class="fas fa-book stat-icon"></i>
                </div>
            </div>
            <div class="card stat-card success">
                <div class="card-body">
                    <div class="stat-title">Total Fees</div>
                    <div class="stat-value">LKR <?php echo number_format($total_fees, 2); ?></div>
                    <div class="stat-change">
                        All Courses
                    </div>
                    <i class="fas fa-money-bill-wave stat-icon"></i>
                </div>
            </div>
            <div class="card stat-card info">
                <div class="card-body">
                    <div class="stat-title">Amount Paid</div>
                    <div class="stat-value">LKR <?php echo number_format($total_paid, 2); ?></div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?php echo $payment_completion; ?>%"></div>
                    </div>
                    <small style="font-size: 1rem;"><?php echo $payment_completion; ?>% Payment Completion</small>
                    <i class="fas fa-check-circle stat-icon"></i>
                </div>
            </div>
            <div class="card stat-card warning">
                <div class="card-body">
                    <div class="stat-title">Due Amount</div>
                    <div class="stat-value">LKR <?php echo number_format($total_due, 2); ?></div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i> Pending
                    </div>
                    <i class="fas fa-exclamation-circle stat-icon"></i>
                </div>
            </div>
        </div>

        <!-- Courses Section -->
        <div class="card">
            <div class="card-header">
                <h6>Enrolled Courses</h6>
                <a href="add_courses.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Duration</th>
                                <th>Fee</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($courses) > 0): ?>
                                <?php foreach ($courses as $course): 
                                    $due_amount = $course['course_fee'] - $course['amount_paid'];
                                    if ($due_amount <= 0) {
                                        $status_class = 'badge-success';
                                        $status_text = 'Paid';
                                    } elseif ($course['amount_paid'] > 0) {
                                        $status_class = 'badge-warning';
                                        $status_text = 'Partial';
                                    } else {
                                        $status_class = 'badge-danger';
                                        $status_text = 'Due';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['course_name']); ?></strong>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($course['course_description']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['course_duration']); ?></td>
                                        <td class="font-weight-bold">LKR <?php echo number_format($course['course_fee'], 2); ?></td>
                                        <td class="font-weight-bold">LKR <?php echo number_format($course['amount_paid'], 2); ?></td>
                                        <td class="font-weight-bold">LKR <?php echo number_format($due_amount, 2); ?></td>
                                        <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                        <td>
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">

                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-book-open fa-2x text-muted mb-2"></i>
                                            <h5 class="text-muted">No Courses Enrolled</h5>
                                            <a href="add_courses.php?id=<?php echo $student_id; ?>" class="btn btn-primary mt-2">
                                                <i class="fas fa-plus"></i> Add Course
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Grading Results Section -->
        <div class="card">
            <div class="card-header">
                <h6>Academic Results</h6>
            </div>
            <div class="card-body">
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <?php 
                        $hasResults = false;
                        for ($i = 1; $i <= 4; $i++) {
                            if (!empty($course["subject_$i"]) || !empty($course["subject_{$i}_result"])) {
                                $hasResults = true;
                                break;
                            }
                        }
                        ?>
                        <?php if ($hasResults): ?>
                            <div class="grade-card">
                                <div class="grade-header">
                                    <div class="grade-title"><?php echo htmlspecialchars($course['course_name']); ?></div>
                                    <?php 
                                    $totalScore = 0;
                                    $subjectCount = 0;
                                    for ($i = 1; $i <= 4; $i++) {
                                        if (isset($course["subject_{$i}_result"]) && is_numeric($course["subject_{$i}_result"])) {
                                            $totalScore += $course["subject_{$i}_result"];
                                            $subjectCount++;
                                        }
                                    }
                                    $averageScore = $subjectCount > 0 ? $totalScore / $subjectCount : 0;
                                    ?>
                                    <div class="grade-value">
                                        <?php echo number_format($averageScore, 1); ?>%
                                    </div>
                                </div>
                                <div class="grade-details">
                                    <?php for ($i = 1; $i <= 4; $i++): ?>
                                        <?php if (!empty($course["subject_$i"]) || !empty($course["subject_{$i}_result"])): ?>
                                            <div class="grade-item">
                                                <div class="grade-label"><?php echo htmlspecialchars($course["subject_$i"] ?? "Subject $i"); ?></div>
                                                <?php if (!empty($course["subject_{$i}_result"])): ?>
                                                    <?php 
                                                    $result = $course["subject_{$i}_result"];
                                                    if (is_numeric($result)) {
                                                        if ($result >= 80) $grade = 'A';
                                                        elseif ($result >= 60) $grade = 'B';
                                                        elseif ($result >= 40) $grade = 'C';
                                                        elseif ($result >= 30) $grade = 'D';
                                                        else $grade = 'F';
                                                    } else {
                                                        $grade = strtoupper(substr($result, 0, 1));
                                                    }
                                                    ?>
                                                    <div class="grade-value grade-<?php echo $grade; ?>">
                                                        <?php echo $result; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="grade-value text-muted">
                                                        --
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No academic results available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle course deletion with AJAX
        $(document).ready(function() {
            $('.delete-form').on('submit', function(e) {
                e.preventDefault();
                
                if (confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                    var form = $(this);
                    var formData = form.serialize();
                    
                    $.ajax({
                        type: 'POST',
                        url: form.attr('action'),
                        data: formData,
                        success: function(response) {
                            // Parse the JSON response
                            var result = JSON.parse(response);
                            
                            if (result.success) {
                                // Remove the table row
                                form.closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                    // Show success message
                                    alert('Course deleted successfully!');
                                    // Optionally, you can reload the page to reset IDs
                                    location.reload();
                                });
                            } else {
                                alert('Error: ' + result.message);
                            }
                        },
                        error: function() {
                            alert('Error deleting course. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>