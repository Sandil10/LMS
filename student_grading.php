<?php
// Start session to manage user login
session_start();
include 'config.php'; // Your database configuration file

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$course_name = $course_duration = '';
$subject_1 = $subject_2 = $subject_3 = $subject_4 = '';
$subject_1_result = $subject_2_result = $subject_3_result = $subject_4_result = '';
$message = '';

// Ensure the student id is passed in the URL
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // Fetch student university_id using the student's id
    $student_sql = "SELECT university_id FROM students WHERE id = ?";
    $stmt = $conn->prepare($student_sql);
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }
    $stmt->bind_param("i", $student_id);
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

    // Now, get the course details including results based on university_id
    $course_sql = "SELECT course_name, course_duration, 
                  subject_1, subject_1_result, 
                  subject_2, subject_2_result, 
                  subject_3, subject_3_result, 
                  subject_4, subject_4_result 
                  FROM courses WHERE university_id = ?";
    $stmt = $conn->prepare($course_sql);
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }
    $stmt->bind_param("s", $university_id); // Use "s" for VARCHAR
    if (!$stmt->execute()) {
        die('Error executing query: ' . $stmt->error);
    }
    $course_result = $stmt->get_result();
    $course_data = $course_result->fetch_assoc();
    $stmt->close();

    if (!$course_data) {
        echo "No course found for this university!";
        exit();
    }

    // Extract the course details and subjects
    $course_name = $course_data['course_name'];
    $course_duration = $course_data['course_duration'];
    $subject_1 = $course_data['subject_1'];
    $subject_2 = $course_data['subject_2'];
    $subject_3 = $course_data['subject_3'];
    $subject_4 = $course_data['subject_4'];
    
    // Extract the results
    $subject_1_result = $course_data['subject_1_result'] ?? '';
    $subject_2_result = $course_data['subject_2_result'] ?? '';
    $subject_3_result = $course_data['subject_3_result'] ?? '';
    $subject_4_result = $course_data['subject_4_result'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the results from the form
    $subject_1_result = $_POST['subject_1_result'];
    $subject_2_result = $_POST['subject_2_result'];
    $subject_3_result = $_POST['subject_3_result'];
    $subject_4_result = $_POST['subject_4_result'];

    // Update the results in the courses table
    $update_sql = "UPDATE courses SET 
                    subject_1_result = ?, 
                    subject_2_result = ?, 
                    subject_3_result = ?, 
                    subject_4_result = ? 
                    WHERE university_id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt === false) {
        die('Error preparing statement: ' . $conn->error);
    }
    $stmt->bind_param("sssss", $subject_1_result, $subject_2_result, $subject_3_result, $subject_4_result, $university_id); // Use "s" for VARCHAR
    if ($stmt->execute()) {
        // Redirect to view_student.php after successful update
        header("Location: view_student.php?id=$student_id");
        exit();
    } else {
        $message = "Error updating exam results: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Grading Information</title>
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
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-image: linear-gradient(135deg, #f5f7ff 0%, #e8ecfe 100%);
        }

        .container {
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 40px;
            margin: 20px;
            transition: var(--transition);
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        h1 {
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 2rem;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        h2 {
            color: var(--secondary-color);
            margin: 30px 0 20px;
            font-weight: 500;
            font-size: 1.5rem;
        }

        .course-info {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary-color);
            margin-right: 10px;
            min-width: 120px;
        }

        .info-value {
            font-weight: 400;
        }

        .grading-system {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
        }

        .grading-system h3 {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .grade-scale {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            text-align: center;
        }

        .grade-item {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 6px;
        }

        .grading-form {
            margin-top: 30px;
        }

        .form-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .form-table thead tr {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .form-table th, .form-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-table tbody tr {
            transition: var(--transition);
            background-color: white;
        }

        .form-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .form-table tbody tr:hover {
            background-color: #f1f3ff;
        }

        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            background-color: white;
            transition: var(--transition);
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
        }

        .btn i {
            margin-right: 10px;
        }

        .btn-view {
            background-color: var(--accent-color);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }

        .btn-view:hover {
            background-color: #3ab7d8;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 201, 240, 0.4);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        .message {
            padding: 15px;
            margin-top: 20px;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
            transition: var(--transition);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            width: 80%;
            max-width: 600px;
            padding: 30px;
            animation: modalFadeIn 0.4s;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translate(-50%, -60%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .modal-title {
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 1.5rem;
        }

        .close-modal {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger-color);
            transform: rotate(90deg);
        }

        .modal-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .modal-table th, .modal-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .modal-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .modal-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .grade-scale {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .button-group {
                flex-direction: column;
                gap: 15px;
            }
            
            .modal-content {
                width: 90%;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .grade-scale {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<body style="background-image: url('img/1013.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <div class="container">
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
        <h1>Student Grading Information</h1>
        
        <div class="course-info">
            <h2>Course Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Course Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($course_name); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Duration:</span>
                    <span class="info-value"><?php echo htmlspecialchars($course_duration); ?> months</span>
                </div>
            </div>
        </div>
        
        <div class="grading-system">
            <h3>Grading Scale</h3>
            <div class="grade-scale">
                <div class="grade-item">A (75-100)</div>
                <div class="grade-item">B (65-74)</div>
                <div class="grade-item">C (54-64)</div>
                <div class="grade-item">D (35-53)</div>
                <div class="grade-item">F (0-34)</div>
            </div>
        </div>
        
        <div class="grading-form">
            <h2>Enter Exam Results</h2>
            <form action="" method="POST">
                <table class="form-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($subject_1); ?></td>
                            <td>
                                <select class="form-select" name="subject_1_result">
                                    <option value="N/A" <?php echo ($subject_1_result == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                                    <option value="A" <?php echo ($subject_1_result == 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo ($subject_1_result == 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo ($subject_1_result == 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo ($subject_1_result == 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="F" <?php echo ($subject_1_result == 'F') ? 'selected' : ''; ?>>F</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo htmlspecialchars($subject_2); ?></td>
                            <td>
                                <select class="form-select" name="subject_2_result">
                                    <option value="N/A" <?php echo ($subject_2_result == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                                    <option value="A" <?php echo ($subject_2_result == 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo ($subject_2_result == 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo ($subject_2_result == 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo ($subject_2_result == 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="F" <?php echo ($subject_2_result == 'F') ? 'selected' : ''; ?>>F</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo htmlspecialchars($subject_3); ?></td>
                            <td>
                                <select class="form-select" name="subject_3_result">
                                    <option value="N/A" <?php echo ($subject_3_result == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                                    <option value="A" <?php echo ($subject_3_result == 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo ($subject_3_result == 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo ($subject_3_result == 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo ($subject_3_result == 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="F" <?php echo ($subject_3_result == 'F') ? 'selected' : ''; ?>>F</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><?php echo htmlspecialchars($subject_4); ?></td>
                            <td>
                                <select class="form-select" name="subject_4_result">
                                    <option value="N/A" <?php echo ($subject_4_result == 'N/A') ? 'selected' : ''; ?>>N/A</option>
                                    <option value="A" <?php echo ($subject_4_result == 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo ($subject_4_result == 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo ($subject_4_result == 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo ($subject_4_result == 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="F" <?php echo ($subject_4_result == 'F') ? 'selected' : ''; ?>>F</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="button-group">
                    <button type="button" class="btn btn-view" onclick="openModal()">
                        <i class="fas fa-eye"></i> View Results
                    </button>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Submit Results
                    </button>
                </div>
            </form>
            
            <?php if (isset($message)): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="resultsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Submitted Results</h3>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (!empty($course_data)): ?>
                    <h4><?php echo htmlspecialchars($course_name); ?></h4>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($subject_1); ?></td>
                                <td><?php echo htmlspecialchars($subject_1_result ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo htmlspecialchars($subject_2); ?></td>
                                <td><?php echo htmlspecialchars($subject_2_result ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo htmlspecialchars($subject_3); ?></td>
                                <td><?php echo htmlspecialchars($subject_3_result ?: 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo htmlspecialchars($subject_4); ?></td>
                                <td><?php echo htmlspecialchars($subject_4_result ?: 'N/A'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No results available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Open modal with course results
        function openModal() {
            document.getElementById('resultsModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Close modal
        function closeModal() {
            document.getElementById('resultsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('resultsModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>