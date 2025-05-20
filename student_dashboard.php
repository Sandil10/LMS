php
Copy
<?php
session_start();
include 'config.php';

// Ensure the user is logged in and has a valid student ID
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student details based on student_id
$student = $conn->query("SELECT * FROM students WHERE id='$student_id'")->fetch_assoc();

// Ensure that the student exists
if ($student === null) {
    echo "Student not found!";
    exit();
}

// Fetch payment details based on university_id from students table
$university_id = $student['university_id'];

// Fetch course details based on university_id
$query = "SELECT * FROM courses WHERE university_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $university_id);
$stmt->execute();
$course_details_result = $stmt->get_result();

// Check if course details exist
if ($course_details_result->num_rows > 0) {
    $courses = $course_details_result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate summary statistics and ensure due_amount is correct
    $total_courses = count($courses);
    $total_fees = 0;
    $total_paid = 0;
    $total_due = 0;
    
    // Calculate correct due amounts for each course
    foreach ($courses as &$course) {
        $course['due_amount'] = $course['course_fee'] - $course['amount_paid'];
        $total_fees += $course['course_fee'];
        $total_paid += $course['amount_paid'];
        $total_due += $course['due_amount'];
        
        // Calculate overall status for each course based on subject results
        $failed_subjects = 0;
        for ($i = 1; $i <= 4; $i++) {
            if (isset($course["subject_{$i}_result"]) && $course["subject_{$i}_result"] < 40) {
                $failed_subjects++;
            }
        }
        $course['overall_status'] = ($failed_subjects == 0) ? 'pass' : 'fail';
    }
    unset($course); // Break the reference
    
    $payment_completion = ($total_fees > 0) ? round(($total_paid / $total_fees) * 100) : 0;
} else {
    $courses = [];
    $total_courses = 0;
    $total_fees = 0;
    $total_paid = 0;
    $total_due = 0;
    $payment_completion = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0078d4;
            --secondary-color: #50e6ff;
            --dark-color: #1a1a1a;
            --light-color: #f8f9fa;
            --success-color: #4bb543;
            --warning-color: #ffcc00;
            --danger-color: #ff3333;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--dark-color);
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%);
            color: white;
            padding: 25px 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 100;
        }

        .sidebar .profile {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .profile img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .sidebar .profile h3 {
            margin-top: 10px;
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar .profile p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
        }

        .sidebar .student-details {
            margin-top: 20px;
        }

        .sidebar .detail-item {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            background-color: rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .sidebar .detail-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .detail-item strong {
            display: block;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 3px;
        }

        .sidebar .detail-item span {
            font-size: 14px;
            font-weight: 500;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            margin-top: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            flex-grow: 1;
            background-color: #f5f5f5;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .header .date {
            font-size: 14px;
            color: #666;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 20px;
        }

        .stat-card .icon.courses {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card .icon.fees {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card .icon.paid {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card .icon.due {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .stat-card .trend {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #666;
        }

        .stat-card .trend.up {
            color: var(--success-color);
        }

        .stat-card .trend.down {
            color: var(--danger-color);
        }

        .progress-container {
            margin-top: 10px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
            color: #666;
        }

        .progress-bar {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
            transition: width 0.6s ease;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin: 30px 0 20px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .courses-table, .results-table {
            width: 100%;
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .courses-table table, .results-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .courses-table th, .results-table th {
            background-color: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .courses-table td, .results-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .courses-table tr:last-child td, .results-table tr:last-child td {
            border-bottom: none;
        }

        .courses-table tr:hover td, .results-table tr:hover td {
            background-color: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.paid {
            background-color: rgba(75, 181, 67, 0.1);
            color: var(--success-color);
        }

        .status-badge.partial {
            background-color: rgba(255, 204, 0, 0.1);
            color: var(--warning-color);
        }

        .status-badge.due {
            background-color: rgba(255, 51, 51, 0.1);
            color: var(--danger-color);
        }

        .status-badge.pass {
            background-color: rgba(75, 181, 67, 0.1);
            color: var(--success-color);
        }

        .status-badge.fail {
            background-color: rgba(255, 51, 51, 0.1);
            color: var(--danger-color);
        }

        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-size: 12px;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-btn:hover {
            background-color: #005a9e;
        }

        .action-btn.outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .action-btn.outline:hover {
            background-color: rgba(0, 120, 212, 0.1);
        }

        .action-btn.view-results {
            background-color: #4CAF50;
            color: white;
        }

        .action-btn.view-results:hover {
            background-color: #45a049;
        }

        .action-btn.download {
            background-color: #9c27b0;
            color: white;
        }

        .action-btn.download:hover {
            background-color: #7b1fa2;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .grade {
            font-weight: 600;
        }

        .grade.A { color: #4CAF50; }
        .grade.B { color: #8BC34A; }
        .grade.C { color: #FFC107; }
        .grade.D { color: #FF9800; }
        .grade.E { color: #FF5722; }
        .grade.F { color: #F44336; }

        @media (max-width: 992px) {
            .sidebar {
                width: 240px;
            }
            .main-content {
                margin-left: 240px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
            .courses-table td, .results-table td {
                padding: 10px 5px;
                font-size: 12px;
            }
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile">
            <img src="uploads/<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture">
            <h3><?php echo htmlspecialchars($student['name']); ?></h3>
            <p>Student Portal</p>
        </div>

        <!-- Student Details -->
        <div class="student-details">
            <div class="detail-item">
                <strong>REGISTRATION NUMBER</strong>
                <span><?php echo htmlspecialchars($student['reg_number']); ?></span>
            </div>
            <div class="detail-item">
                <strong>UNIVERSITY ID</strong>
                <span><?php echo htmlspecialchars($student['university_id']); ?></span>
            </div>
            <div class="detail-item">
                <strong>STATUS</strong>
                <span><?php echo htmlspecialchars($student['portal_status']); ?></span>
            </div>
            <div class="detail-item">
                <strong>EMAIL</strong>
                <span><?php echo htmlspecialchars($student['email']); ?></span>
            </div>
            <div class="detail-item">
                <strong>BATCH NUMBER</strong>
                <span><?php echo htmlspecialchars($student['batch_no']); ?></span>
            </div>
            <div class="detail-item">
                <strong>STUDY LOCATION</strong>
                <span><?php echo htmlspecialchars($student['study_location']); ?></span>
            </div>
            
            <button class="logout-btn" onclick="window.location.href='index.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
            <!-- <button class="logout-btn" onclick="window.location.href='editstudent.php'">
                <i class="fas fa-sign-out-alt"></i> Edit Details
            </button> -->
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo htmlspecialchars($student['name']); ?></h1>
            <div class="date">
                <i class="far fa-calendar-alt"></i> <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="icon courses">
                    <i class="fas fa-book-open"></i>
                </div>
                <h3>Enrolled Courses</h3>
                <div class="value"><?php echo $total_courses; ?></div>
                <div class="trend up">
                <option value="<?php echo $student['portal_status']; ?>" selected style="font-size: 16px; color: darkgreen; font-weight: bold;"><?php echo ucfirst($student['portal_status']); ?></option>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon fees">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3>Total Course Fees</h3>
                <div class="value">LKR <span class="currency"><?php echo number_format($total_fees, 2); ?></span></div>
                <div class="trend">
                    All courses
                </div>
            </div>

            <div class="stat-card">
                <div class="icon paid">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Amount Paid</h3>
                <div class="value">LKR <span class="currency"><?php echo number_format($total_paid, 2); ?></span></div>
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Payment Completion</span>
                        <span><?php echo $payment_completion; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $payment_completion; ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="icon due">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>Due Amount</h3>
                <div class="value">LKR <span class="currency"><?php echo number_format($total_due, 2); ?></span></div>
                <div class="trend down">
                    <!-- <i class="fas fa-arrow-down"></i> Pending -->
                </div>
            </div>
        </div>

        <!-- Courses Section -->
        <h2 class="section-title">
            <i class="fas fa-clipboard-list"></i> Your Enrolled Courses
        </h2>
        <div class="courses-table">
    <table>
        <thead>
            <tr>
                <th>Course Name</th>
                <th>Duration</th>
                <th>Course Fee</th>
                <th>Amount Paid</th>
                <th>Due Amount</th>
                <th>Status</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($courses) > 0): ?>
                <?php foreach ($courses as $course): 
                    // Calculate due amount
                    $due_amount = $course['course_fee'] - $course['amount_paid'];
                    
                    // Determine payment status
                    if ($due_amount <= 0) {
                        $status_class = 'paid';
                        $status_text = 'Paid';
                    } elseif ($course['amount_paid'] > 0) {
                        $status_class = 'partial';
                        $status_text = 'Partial';
                    } else {
                        $status_class = 'due';
                        $status_text = 'Due';
                    }
                ?>
                <tr>
    <td>
        <strong><?php echo htmlspecialchars($course['course_name']); ?></strong><br>
        <small><?php echo htmlspecialchars($course['course_description']); ?></small>
    </td>
    <td><?php echo htmlspecialchars($course['course_duration']); ?></td>
    <td class="currency">LKR <?php echo number_format($course['course_fee'], 2); ?></td>
    <td class="currency">LKR <?php echo number_format($course['amount_paid'], 2); ?></td>
    <td class="currency">LKR <?php echo number_format($due_amount, 2); ?></td>
    <td>
        <span class="status-badge <?php echo $status_class; ?>">
            <?php echo $status_text; ?>
        </span>
    </td>
    <td>
    <?php 
    // Check if payment date exists and is valid (not 0000-00-00 or similar)
    if (!empty($course['payment_date']) && $course['payment_date'] != '0000-00-00') {
        echo date('M j, Y', strtotime($course['payment_date']));
    } else {
        echo ($status_text == 'Due') ? 'Not Paid' : '--';
    }
    ?>
</td>
</tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">
                        No courses enrolled yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

        <!-- Results Section -->
       <!-- Results Section -->
<!-- Results Section -->
<h2 class="section-title" id="results-section">
    <i class="fas fa-chart-line"></i> Your Academic Performance
</h2>

<div class="results-container">
    <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
            <?php if (isset($course['subject_1']) || isset($course['subject_1_result'])): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                        <?php if (!empty($course['course_proposal_pdf'])): ?>
                            <a href="uploads/courses/<?php echo htmlspecialchars($course['course_proposal_pdf']); ?>" 
                               class="download-btn" download>
                                <i class="fas fa-file-pdf"></i> Download Syllabus
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="results-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <?php 
                                    $subject_name = isset($course["subject_$i"]) ? $course["subject_$i"] : null;
                                    $score = isset($course["subject_{$i}_result"]) ? $course["subject_{$i}_result"] : null;
                                    
                                    if (!empty($subject_name) || $score !== null): 
                                        // Determine status and grade color
                                        $status = 'pending';
                                        $display_score = '--';
                                        $grade_class = '';
                                        $progress_color = '';
                                        $progress_width = 0;
                                        
                                        if ($score !== null) {
                                            $display_score = $score;
                                            if (strtoupper($score) === 'N/A') {
                                                $status = 'pending';
                                                $grade_class = 'na';
                                                $progress_color = 'transparent';
                                            } elseif (is_numeric($score)) {
                                                $progress_width = $score;
                                                if ($score >= 75) {
                                                    $grade_class = 'A';
                                                    $progress_color = 'var(--grade-A)';
                                                    $status = 'pass';
                                                } elseif ($score >= 65) {
                                                    $grade_class = 'B';
                                                    $progress_color = 'var(--grade-B)';
                                                    $status = 'pass';
                                                } elseif ($score >= 50) {
                                                    $grade_class = 'C';
                                                    $progress_color = 'var(--grade-C)';
                                                    $status = 'pass';
                                                } elseif ($score >= 35) {
                                                    $grade_class = 'D';
                                                    $progress_color = 'var(--grade-D)';
                                                    $status = 'pass';
                                                } else {
                                                    $grade_class = 'F';
                                                    $progress_color = 'var(--grade-F)';
                                                    $status = 'fail';
                                                }
                                            } else {
                                                // Handle letter grades
                                                $grade_class = strtoupper($score);
                                                if ($grade_class === 'A') {
                                                    $progress_width = 90;
                                                    $progress_color = 'var(--grade-A)';
                                                    $status = 'pass';
                                                } elseif ($grade_class === 'B') {
                                                    $progress_width = 75;
                                                    $progress_color = 'var(--grade-B)';
                                                    $status = 'pass';
                                                } elseif ($grade_class === 'C') {
                                                    $progress_width = 60;
                                                    $progress_color = 'var(--grade-C)';
                                                    $status = 'pass';
                                                } elseif ($grade_class === 'D') {
                                                    $progress_width = 45;
                                                    $progress_color = 'var(--grade-D)';
                                                    $status = 'pass';
                                                } elseif ($grade_class === 'E') {
                                                    $progress_width = 30;
                                                    $progress_color = 'var(--grade-E)';
                                                    $status = 'fail';
                                                } elseif ($grade_class === 'F') {
                                                    $progress_width = 15;
                                                    $progress_color = 'var(--grade-F)';
                                                    $status = 'fail';
                                                } else {
                                                    $status = 'pending';
                                                    $progress_color = 'transparent';
                                                }
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars(!empty($subject_name) ? $subject_name : "Subject $i"); ?></strong>
                                            </td>
                                            <td>
                                                <span class="score-display <?php echo strtolower($grade_class); ?>">
                                                    <?php echo $display_score; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status; ?>">
                                                    <?php 
                                                    if ($status === 'pass') echo 'Passed';
                                                    elseif ($status === 'fail') echo 'Failed';
                                                    elseif ($status === 'pending') echo 'Pending';
                                                    else echo 'pending';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress-container">
                                                    <div class="progress-bar">
                                                        <div class="progress-fill grade-<?php echo strtolower($grade_class); ?>" 
                                                             style="width: <?php echo $progress_width; ?>%; 
                                                                    background: <?php echo $progress_color; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="course-summary">
                        <?php 
                        $passed_subjects = 0;
                        $failed_subjects = 0;
                        $pending_subjects = 0;
                        $total_subjects = 0;
                        
                        for ($i = 1; $i <= 4; $i++) {
                            if (isset($course["subject_{$i}_result"])) {
                                $total_subjects++;
                                $score = $course["subject_{$i}_result"];
                                
                                if (strtoupper($score) === 'N/A') {
                                    $pending_subjects++;
                                } elseif (is_numeric($score)) {
                                    if ($score < 35) {
                                        $failed_subjects++;
                                    } else {
                                        $passed_subjects++;
                                    }
                                } else {
                                    if (strtoupper($score) === 'F') {
                                        $failed_subjects++;
                                    } else {
                                        $passed_subjects++;
                                    }
                                }
                            }
                        }
                        
                        $graded_subjects = $total_subjects - $pending_subjects;
                        $completion_percentage = ($graded_subjects > 0) ? round(($passed_subjects / $graded_subjects) * 100) : 0;
                        
                        if ($pending_subjects > 0 && $graded_subjects == 0) {
                            $overall_status = 'pending';
                        } else {
                            $overall_status = ($failed_subjects > 0) ? 'fail' : 'pass';
                        }
                        ?>
                        
                        <div class="summary-card <?php echo $overall_status; ?>">
                            <div class="summary-icon">
                                <?php if ($overall_status == 'pass'): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php elseif ($overall_status == 'fail'): ?>
                                    <i class="fas fa-times-circle"></i>
                                <?php else: ?>
                                    <i class="fas fa-user-times"></i>
                                <?php endif; ?>
                            </div>
                            <div class="summary-details">
                                <h4>Overall Performance</h4>
                                <div class="progress-container">
                                    <div class="progress-label">
                                        <span>
                                            Results: 
                                            <?php echo $passed_subjects; ?> passed, 
                                            <?php echo $failed_subjects; ?> failed,
                                            <?php echo $pending_subjects; ?> pending
                                        </span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                                    </div>
                                </div>
                                <div class="status-message">
                                    <?php 
                                    if ($pending_subjects > 0) {
                                        if ($passed_subjects > 0 || $failed_subjects > 0) {
                                            echo "$passed_subjects subject(s) passed, ";
                                            if ($failed_subjects > 0) {
                                                echo "$failed_subjects failed, ";
                                            }
                                            echo "$pending_subjects pending results";
                                        } else {
                                            echo "All subjects pending results";
                                        }
                                    } else {
                                        if ($overall_status == 'pass') {
                                            echo "Excellent! All subjects passed";
                                        } else {
                                            echo "$failed_subjects subject(s) failed (score below 35 or grade F)";
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php 
        $has_results = false;
        foreach ($courses as $course) {
            if (isset($course['subject_1']) || isset($course['subject_1_result'])) {
                $has_results = true;
                break;
            }
        }
        ?>
        
        <?php if (!$has_results): ?>
            <div class="no-results">
                <i class="fas fa-book-open"></i>
                <h3>No subject results available yet</h3>
                <p>Your academic results will appear here once they are published.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-courses">
            <i class="fas fa-graduation-cap"></i>
            <h3>No courses enrolled yet</h3>
            <p>You haven't enrolled in any courses. Visit the courses page to get started.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    :root {
        --grade-A: #4CAF50;  /* Green */
        --grade-B: #8BC34A;  /* Light Green */
        --grade-C: #FFC107;  /* Amber */
        --grade-D: #FF9800;  /* Orange */
        --grade-E: #FF5722;  /* Deep Orange */
        --grade-F: #F44336;  /* Red */
        --grade-NA: #9E9E9E; /* Grey */
    }

    .progress-fill.grade-a { background: var(--grade-A); }
    .progress-fill.grade-b { background: var(--grade-B); }
    .progress-fill.grade-c { background: var(--grade-C); }
    .progress-fill.grade-d { background: var(--grade-D); }
    .progress-fill.grade-e { background: var(--grade-E); }
    .progress-fill.grade-f { background: var(--grade-F); }
    .progress-fill.grade-na { background: transparent; }

    .score-display.a { color: var(--grade-A); }
    .score-display.b { color: var(--grade-B); }
    .score-display.c { color: var(--grade-C); }
    .score-display.d { color: var(--grade-D); }
    .score-display.e { color: var(--grade-E); }
    .score-display.f { color: var(--grade-F); }
    .score-display.na { color: var(--grade-NA); }

    /* Keep all your existing styles from before */
    .results-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .course-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    /* ... rest of your existing CSS ... */
</style>
<style>
    .results-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }
    
    .course-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .course-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }
    
    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #0078d4 0%, #50e6ff 100%);
        color: white;
    }
    
    .course-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .download-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .download-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .results-table {
        width: 100%;
        padding: 20px;
    }
    
    .results-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .results-table th {
        background-color: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: #555;
    }
    
    .results-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    
    .results-table tr:last-child td {
        border-bottom: none;
    }
    
    .results-table tr:hover td {
        background-color: #f9f9f9;
    }
    
    .score-display {
        font-weight: 600;
        color: #333;
    }
    
    .score-display.pending {
        color: #999;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.pass {
        background-color: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
    }
    
    .status-badge.fail {
        background-color: rgba(244, 67, 54, 0.1);
        color: #F44336;
    }
    
    .status-badge.pending {
        background-color: rgba(158, 158, 158, 0.1);
        color: #9E9E9E;
    }
    
    .progress-container {
        width: 100%;
    }
    
    .progress-bar {
        height: 8px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, #0078d4, #50e6ff);
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 12px;
        color: #666;
    }
    
    .course-summary {
        padding: 0 20px 20px;
    }
    
    .summary-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: 10px;
        background: #f9f9f9;
    }
    
    .summary-card.pass {
        background: rgba(76, 175, 80, 0.1);
        border-left: 4px solid #4CAF50;
    }
    
    .summary-card.partial {
        background: rgba(255, 193, 7, 0.1);
        border-left: 4px solid #FFC107;
    }
    
    .summary-card.fail {
        background: rgba(244, 67, 54, 0.1);
        border-left: 4px solid #F44336;
    }
    
    .summary-icon {
        font-size: 24px;
    }
    
    .summary-card.pass .summary-icon {
        color: #4CAF50;
    }
    
    .summary-card.partial .summary-icon {
        color: #FFC107;
    }
    
    .summary-card.fail .summary-icon {
        color: #F44336;
    }
    
    .summary-details {
        flex: 1;
    }
    
    .summary-details h4 {
        margin: 0 0 5px 0;
        font-size: 15px;
    }
    
    .status-message {
        font-size: 13px;
        margin-top: 5px;
    }
    
    .summary-card.pass .status-message {
        color: #4CAF50;
    }
    
    .summary-card.partial .status-message {
        color: #FFC107;
    }
    
    .summary-card.fail .status-message {
        color: #F44336;
    }
    
    .no-results, .no-courses {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .no-results i, .no-courses i {
        font-size: 48px;
        color: #0078d4;
        margin-bottom: 15px;
    }
    
    .no-results h3, .no-courses h3 {
        margin: 0 0 10px 0;
        color: #333;
    }
    
    .no-results p, .no-courses p {
        color: #666;
        margin: 0;
    }
    
    @media (max-width: 768px) {
        .course-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .results-table {
            overflow-x: auto;
        }
    }
</style>