<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data (store password as plain text)
    $name = $_POST['name'];
    $reg_number = $_POST['reg_number'];
    $university_id = $_POST['university_id'];
    $portal_status = $_POST['portal_status'];
    $proof_id = $_POST['proof_id'];
    $email = $_POST['email'];
    $batch_no = $_POST['batch_no'];
    $study_location = $_POST['study_location'];
    $password = $_POST['password']; // Storing as plain text
    
    // Handle file upload
    $profile_picture = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_ext; // Generate unique filename
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture = $filename; // Store only the filename in database
        }
    }

    // Insert student data into database
    $sql = "INSERT INTO students (name, reg_number, university_id, portal_status, proof_id, email, batch_no, study_location, profile_picture, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssss", $name, $reg_number, $university_id, $portal_status, $proof_id, $email, $batch_no, $study_location, $profile_picture, $password);
    
    if ($stmt->execute()) {
        $success = "Student added successfully!";
    } else {
        $error = "Error adding student: " . $conn->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Rest of your HTML head section remains the same -->
    <!-- ... -->
</head>
<body>
    <!-- Rest of your HTML body remains the same -->
    <!-- ... -->
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
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

        /* Form Container */
        .form-container {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 900px;
            margin: 0 auto;
            transition: var(--transition);
        }

        .form-container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: var(--secondary-color);
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 15px;
        }

        .form-container h2::after {
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

        /* Form Rows */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-control {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 1em;
        }

        .file-input {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 1px dashed #ccc;
            border-radius: var(--border-radius);
            color: #666;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .file-input-label i {
            margin-right: 10px;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 1.1em;
            font-weight: 500;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            margin-top: 10px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        .submit-btn i {
            margin-right: 10px;
        }

        /* Message Styling */
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
            .form-row {
                flex-direction: column;
                gap: 15px;
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
                padding: 30px;
            }
            
            .form-container {
                padding: 30px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 20px;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .form-container h2 {
                font-size: 1.5rem;
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
            <li><a href="addstudents.php" class="active">Add Students</a></li>
            <li><a href="manage_students.php">Manage Students</a></li>
            <li><a href="pwreset.php">Reset Password</a></li>
            <li><a href="index.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <h2>Add New Student</h2>
            
            <?php if (isset($success)): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_number">Registration Number</label>
                        <input type="text" class="form-control" name="reg_number" id="reg_number" required>
                    </div>
                </div>

                <div class="form-row">
    <div class="form-group">
        <label for="university_id">University ID</label>
        <input type="text" class="form-control" name="university_id" id="university_id" maxlength="50" required>
    </div>


                    <div class="form-group">
                        <label for="portal_status">Portal Status</label>
                        <select class="form-control" name="portal_status" id="portal_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="proof_id">Proof ID</label>
                        <input type="text" class="form-control" name="proof_id" id="proof_id" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="batch_no">Batch Number</label>
                        <input type="text" class="form-control" name="batch_no" id="batch_no" required>
                    </div>
                    <div class="form-group">
                        <label for="study_location">Study Location</label>
                        <input type="text" class="form-control" name="study_location" id="study_location" autocomplete="off" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="file-input">
                            <label class="file-input-label" for="profile_picture">
                                <i class="fas fa-camera"></i>
                                <span id="file-name">Choose a file</span>
                                <i class="fas fa-upload"></i>
                            </label>
                            <input type="file" name="profile_picture" id="profile_picture" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" name="password" id="password" autocomplete="new-password" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Add Student
                </button>
            </form>
        </div>
    </div>

    <script>
        // Update file name display when a file is selected
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose a file';
            document.getElementById('file-name').textContent = fileName;
        });
    </script>
</body>
</html> 