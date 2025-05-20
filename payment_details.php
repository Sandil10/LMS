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

// Fetch student and course details
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Fetch student university_id
    $student_sql = "SELECT university_id, name FROM students WHERE id = ?";
    $stmt = $conn->prepare($student_sql);

    // Check if the statement preparation was successful
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }

    $stmt->bind_param("i", $student_id);

    // Execute the query and check for success
    if (!$stmt->execute()) {
        die('Error executing query: ' . $stmt->error);
    }

    $student_result = $stmt->get_result();
    $student_data = $student_result->fetch_assoc();
    $stmt->close();

    if (!$student_data) {
        echo "No student found!";
        exit();
    }

    $university_id = $student_data['university_id'];
    $student_name = $student_data['name'];

    // Fetch course details
    $course_sql = "SELECT course_name, course_duration, course_fee, amount_paid FROM courses WHERE university_id = ?";
    $stmt = $conn->prepare($course_sql);

    // Check if the statement preparation was successful
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }

    $stmt->bind_param("s", $university_id);

    // Execute the query and check for success
    if (!$stmt->execute()) {
        die('Error executing query: ' . $stmt->error);
    }

    $course_result = $stmt->get_result();
    $course_data = $course_result->fetch_assoc();
    $stmt->close();

    if (!$course_data) {
        echo "No course found!";
        exit();
    }

    $course_fee = floatval(str_replace(',', '', $course_data['course_fee']));
    $amount_paid = floatval($course_data['amount_paid']);
    $due_amount = $course_fee - $amount_paid;

    // Handle new payment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paid_amount'])) {
        // Validate POST data
        if (isset($_POST['paid_amount']) && isset($_POST['payment_reference'])) {
            $paid_amount = floatval(str_replace(',', '', $_POST['paid_amount']));
            $payment_reference = $_POST['payment_reference'] ?? 'Manual Payment';

            // Update payment in courses table
            $update_sql = "UPDATE courses SET amount_paid = amount_paid + ?, payment_reference = ?, payment_date = CURRENT_DATE WHERE university_id = ?";
            $stmt = $conn->prepare($update_sql);

            // Check if the statement preparation was successful
            if ($stmt === false) {
                die('Error preparing statement: ' . $conn->error);
            }

            $stmt->bind_param("dss", $paid_amount, $payment_reference, $university_id);

            // Execute the query and check for success
            if (!$stmt->execute()) {
                die('Error executing query: ' . $stmt->error);
            }

            $stmt->close();

            // Redirect to view_student.php after successful update
            header("Location: view_student.php?id=$student_id");
            exit();
        } else {
            echo "Error: Payment details are missing.";
        }
    }

    // Fetch payment history
    $history_sql = "SELECT amount_paid, payment_reference, payment_date 
                    FROM courses 
                    WHERE university_id = ? 
                    ORDER BY payment_date DESC";

    $stmt = $conn->prepare($history_sql);

    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }

    $stmt->bind_param("s", $university_id);

    if (!$stmt->execute()) {
        die('Error executing query: ' . $stmt->error);
    }

    $payment_history_result = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details for Student</title>
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
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7ff;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px;
        }

        h1, h2, h3 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
            margin-bottom: 40px;
        }

        h2 {
            font-size: 1.8rem;
            margin-top: 40px;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 0.9em;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        table thead tr {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-align: left;
            font-weight: 600;
        }

        table th, table td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        table tbody tr {
            transition: var(--transition);
            background-color: white;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        table tbody tr:hover {
            background-color: #f1f3ff;
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .highlight {
            font-weight: 600;
            color: var(--primary-color);
        }

        .due-amount {
            color: var(--danger-color);
            font-weight: 600;
        }

        .paid-amount {
            color: var(--success-color);
            font-weight: 600;
        }

        .form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            background-color: white;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            transition: var(--transition);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalFadeIn 0.4s;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: var(--transition);
            cursor: pointer;
        }

        .close:hover {
            color: var(--danger-color);
            transform: rotate(90deg);
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background-color: rgba(75, 181, 67, 0.2);
            color: var(--success-color);
        }

        .badge-warning {
            background-color: rgba(240, 173, 78, 0.2);
            color: var(--warning-color);
        }

        .badge-danger {
            background-color: rgba(217, 83, 79, 0.2);
            color: var(--danger-color);
        }

        .badge-info {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--accent-color);
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 40px;
        }

        .mb-4 {
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Details for <?php echo htmlspecialchars($student_name); ?></h1>
        
        
        <div class="card">
        <?php
// Assuming you have the student ID stored in the 'id' parameter in the URL
$id = $_GET['id'] ?? ''; // Replace with the correct retrieval method if needed
?>
<div>
    <a href="view_student.php?id=<?php echo htmlspecialchars($id); ?>" 
       style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; margin-bottom: 10px;">
        Back
    </a>
</div>
            <h2>Course Payment Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Duration</th>
                        <th>Course Fee</th>
                        <th>Amount Paid</th>
                        <th>Due Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="highlight"><?php echo htmlspecialchars($course_data['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course_data['course_duration']); ?> months</td>
                        <td>LKR <?php echo number_format($course_fee, 2); ?></td>
                        <td class="paid-amount">LKR <?php echo number_format($amount_paid, 2); ?></td>
                        <td class="due-amount">LKR <?php echo number_format($due_amount, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Make a Payment</h2>
            <div class="form-container">
                <form method="POST" action="payment_details.php?id=<?php echo $student_id; ?>">
                    <div class="form-group">
                        <label for="paid_amount">Amount (LKR)</label>
                        <input type="number" class="form-control" name="paid_amount" id="paid_amount" placeholder="Enter amount to pay" required step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="payment_reference">Payment Reference</label>
                        <input type="text" class="form-control" name="payment_reference" id="payment_reference" placeholder="Enter payment reference" required>
                    </div>
                    <button type="submit" class="btn btn-block">
                        <i class="fas fa-money-bill-wave"></i> Submit Payment
                    </button>
                </form>
            </div>
        </div>

        <button id="viewHistoryBtn" class="btn btn-outline btn-block">
            <i class="fas fa-history"></i> View Payment History
        </button>

        <div id="paymentHistoryModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Payment History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($payment = $payment_history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td class="paid-amount">LKR <?php echo number_format($payment['amount_paid'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_reference']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("paymentHistoryModal");
        const btn = document.getElementById("viewHistoryBtn");
        const span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
            document.body.style.overflow = "hidden";
        }

        span.onclick = function() {
            modal.style.display = "none";
            document.body.style.overflow = "auto";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                document.body.style.overflow = "auto";
            }
        }

        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                row.style.transition = `all 0.3s ease ${index * 0.1}s`;
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>