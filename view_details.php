<?php
session_start();
include 'config.php';

// Ensure only admins can access
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Fetch the student's university_id
    $student_sql = "SELECT university_id FROM students WHERE id = ?";
    $university_id = null;
    if ($stmt = $conn->prepare($student_sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $university_id = $row['university_id'];
        }
        $stmt->close();
    }

    // Fetch the student's courses based on university_id
    $courses_sql = "SELECT * FROM courses WHERE university_id = ?";
    $courses = [];
    if ($stmt = $conn->prepare($courses_sql)) {
        $stmt->bind_param("i", $university_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $stmt->close();
    }

    // Fetch payment information based on student_id
    $payments_sql = "SELECT * FROM payments WHERE student_id = ?";
    $payments = [];
    if ($stmt = $conn->prepare($payments_sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        $stmt->close();
    }

    // Fetch grading information based on student_id
    $grading_sql = "SELECT * FROM grades WHERE student_id = ?";
    $grades = [];
    if ($stmt = $conn->prepare($grading_sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        $stmt->close();
    }
} else {
    echo "No student ID provided!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('img/uni.jpg');
            background-size: cover;
            background-position: center;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #2C3E50;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #1ABC9C;
            color: white;
        }
        td {
            background-color: #f9f9f9;
        }
        .section {
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Student Details</h2>

    <!-- Courses Information Table -->
    <div class="section">
        <h3>Course Information</h3>
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Duration</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['course_duration']) ?></td>
                            <td><?= htmlspecialchars($course['course_description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No courses found for this student.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Payment Information Table -->
    <div class="section">
        <h3>Payment Information</h3>
        <table>
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payments) > 0): ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['payment_date']) ?></td>
                            <td><?= htmlspecialchars($payment['amount']) ?></td>
                            <td><?= htmlspecialchars($payment['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No payment information found for this student.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Grading Information Table -->
    <div class="section">
        <h3>Grading Information</h3>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($grades) > 0): ?>
                    <?php foreach ($grades as $grade): ?>
                        <tr>
                            <td><?= htmlspecialchars($grade['subject']) ?></td>
                            <td><?= htmlspecialchars($grade['grade']) ?></td>
                            <td><?= htmlspecialchars($grade['remarks']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No grading information found for this student.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
