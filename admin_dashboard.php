<?php
session_start();
include 'config.php';

// Ensure that only admins can access the dashboard
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Fetch student statistics
$sql_students = "SELECT COUNT(*) AS total_students FROM students";
$result_students = $conn->query($sql_students);
$students = $result_students->fetch_assoc();

// Fetch enrolled students count
$sql_enrolled = "SELECT COUNT(DISTINCT university_id) AS enrolled_students FROM courses";
$result_enrolled = $conn->query($sql_enrolled);
$enrolled = $result_enrolled->fetch_assoc();

// Fetch payment statistics
$sql_payments = "SELECT 
                COUNT(*) AS total_paid,
                SUM(CASE WHEN amount_paid >= course_fee THEN 1 ELSE 0 END) AS fully_paid,
                SUM(CASE WHEN amount_paid < course_fee AND amount_paid > 0 THEN 1 ELSE 0 END) AS partially_paid
                FROM courses";
$result_payments = $conn->query($sql_payments);
$payments = $result_payments->fetch_assoc();

// Fetch academic performance
$sql_performance = "SELECT 
                   SUM(CASE WHEN subject_1_result = 'A' OR subject_1_result = 'B' OR subject_1_result = 'C' THEN 1 ELSE 0 END) AS passed,
                   SUM(CASE WHEN subject_1_result = 'D' OR subject_1_result = 'F' THEN 1 ELSE 0 END) AS failed
                   FROM courses WHERE subject_1_result IS NOT NULL";
$result_performance = $conn->query($sql_performance);
$performance = $result_performance->fetch_assoc();

// Fetch admin details (for profile)
$sql_admin = "SELECT * FROM admin WHERE id = '" . $_SESSION['id'] . "'";
$result_admin = $conn->query($sql_admin);
$admin = $result_admin->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* Body */
        body {
            display: flex;
            background-color: #f8fafc;
            min-height: 100vh;
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
        .dashboard-content {
            margin-left: 270px;
            padding: 30px;
            flex: 1;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            color: #2C3E50;
            font-size: 28px;
            font-weight: 600;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #1ABC9C;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .admin-info h4 {
            color: #2C3E50;
            font-weight: 500;
        }

        .admin-info p {
            color: #7F8C8D;
            font-size: 14px;
        }

        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .card-students::before {
            background: #3498db;
        }

        .card-enrolled::before {
            background: #2ecc71;
        }

        .card-payments::before {
            background: #f39c12;
        }

        .card-performance::before {
            background: #e74c3c;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
        }

        .card-students .card-icon {
            background: #3498db;
        }

        .card-enrolled .card-icon {
            background: #2ecc71;
        }

        .card-payments .card-icon {
            background: #f39c12;
        }

        .card-performance .card-icon {
            background: #e74c3c;
        }

        .card-title {
            color: #7F8C8D;
            font-size: 16px;
            font-weight: 500;
        }

        .card-value {
            font-size: 28px;
            font-weight: 600;
            color: #2C3E50;
            margin: 10px 0;
        }

        .card-details {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .detail-item {
            text-align: center;
        }

        .detail-value {
            font-weight: 600;
            font-size: 18px;
        }

        .detail-label {
            font-size: 12px;
            color: #7F8C8D;
            margin-top: 5px;
        }

        /* Recent Activity */
        .recent-activity {
            margin-top: 40px;
        }

        .section-title {
            color: #2C3E50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .activity-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #1ABC9C;
            font-size: 18px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #2C3E50;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: #7F8C8D;
        }

        @media (max-width: 992px) {
            .dashboard-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .dashboard-content {
                margin-left: 0;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar (unchanged) -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="addstudents.php">Add Students</a></li>
            <li><a href="manage_students.php">Manage Students</a></li>
            <li><a href="pwreset.php">Reset Password</a></li>
            <li><a href="index.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="dashboard-content">
        <div class="dashboard-header">
            <h2>Admin Dashboard</h2>
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
                <div class="admin-info">
                    <h4><?php echo htmlspecialchars($admin['username']); ?></h4>
                    <p>Administrator</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="dashboard-cards">
            <!-- Total Students Card -->
            <div class="card card-students">
                <div class="card-header">
                    <h3 class="card-title">Total Students</h3>
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $students['total_students']; ?></div>
                <div class="card-details">
                    <!-- <div class="detail-item">
                        <div class="detail-value"><?php echo $enrolled['enrolled_students']; ?></div>
                        <div class="detail-label">Enrolled</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $students['total_students'] - $enrolled['enrolled_students']; ?></div>
                        <div class="detail-label">Not Enrolled</div>
                    </div> -->
                </div>
            </div>

            <!-- Enrollment Card -->
            <div class="card card-enrolled">
                <div class="card-header">
                    <h3 class="card-title">Course Enrollment</h3>
                    <div class="card-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $enrolled['enrolled_students']; ?></div>
                <div class="card-details">
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $payments['fully_paid']; ?></div>
                        <div class="detail-label">Fully Paid</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $payments['partially_paid']; ?></div>
                        <div class="detail-label">Partially Paid</div>
                    </div>
                </div>
            </div>

            <!-- Payments Card -->
            <div class="card card-payments">
                <div class="card-header">
                    <h3 class="card-title">Payment Status</h3>
                    <div class="card-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $payments['total_paid']; ?></div>
                <div class="card-details">
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $payments['fully_paid']; ?></div>
                        <div class="detail-label">Completed</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $payments['partially_paid']; ?></div>
                        <div class="detail-label">Pending</div>
                    </div>
                </div>
            </div>

            <!-- Academic Performance Card -->
            <div class="card card-performance">
                <div class="card-header">
                    <h3 class="card-title">Academic Performance</h3>
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo $performance['passed'] + $performance['failed']; ?></div>
                <div class="card-details">
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $performance['passed']; ?></div>
                        <div class="detail-label">Passed</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-value"><?php echo $performance['failed']; ?></div>
                        <div class="detail-label">Failed</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <h3 class="section-title">Recent Activity</h3>
            <div class="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">5 new students registered</div>
                        <div class="activity-time">Today, 10:45 AM</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-money-bill"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">3 payments received</div>
                        <div class="activity-time">Today, 09:30 AM</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">New course added - Web Development</div>
                        <div class="activity-time">Yesterday, 4:15 PM</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Exam results updated for 12 students</div>
                        <div class="activity-time">Yesterday, 2:00 PM</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>