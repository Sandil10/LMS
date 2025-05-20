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

    // Fetch the student details from the database
    $sql = "SELECT * FROM students WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();

        if (!$student) {
            echo "Student not found!";
            exit();
        }
    } else {
        echo "Error fetching student details!";
        exit();
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
    <title>View Student Details</title>
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
            max-width: 800px;
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

        h2 {
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 2rem;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }

        h2::after {
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

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .profile-img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .student-info {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary-color);
            flex: 1;
        }

        .info-value {
            flex: 2;
            text-align: right;
            font-weight: 400;
            color: var(--dark-color);
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 40px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: var(--dark-color);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            flex: 1;
        }

        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            background: var(--primary-color);
            color: white;
        }

        .action-btn:hover .btn-icon {
            color: white;
        }

        .btn-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary-color);
            transition: var(--transition);
        }

        .btn-text {
            font-weight: 500;
            font-size: 1.1rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .profile-img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
<body style="background-image: url('img/1011.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="container">
    <div>
        <a href="manage_students.php" style="display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; margin-bottom: 10px;">Back</a>
    </div>

    <h2>Student Profile</h2>
    
    <div class="profile-section">
        <img src="uploads/<?php echo $student['profile_picture']; ?>" alt="Profile Picture" class="profile-img">
        
        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Portal Status:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['portal_status']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">University ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['university_id']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span>
            </div>
        </div>
  
        
        <div class="action-buttons">
            <a href="add_courses.php?id=<?php echo $student['id']; ?>" class="action-btn">
                <i class="fas fa-book btn-icon"></i>
                <span class="btn-text">Add Courses</span>
            </a>
            <a href="payment_details.php?id=<?php echo $student['id']; ?>" class="action-btn">
                <i class="fas fa-money-bill-wave btn-icon"></i>
                <span class="btn-text">Payment Details</span>
            </a>
            <a href="student_grading.php?id=<?php echo $student['id']; ?>" class="action-btn">
                <i class="fas fa-graduation-cap btn-icon"></i>
                <span class="btn-text">Student Grading</span>
            </a>
        </div>
    </div>
</body>
</html>