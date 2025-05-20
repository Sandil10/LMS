<?php
session_start();
include 'config.php';

// Admin verification
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Access denied!'); window.location.href='index.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    
    try {
        // Collect and validate input
        $fields = [
            'name' => trim($_POST['name']),
            'reg_number' => trim($_POST['reg_number']),
            'university_id' => trim($_POST['university_id']),
            'portal_status' => trim($_POST['portal_status']),
            'proof_id' => trim($_POST['proof_id']),
            'email' => trim($_POST['email']),
            'batch_no' => trim($_POST['batch_no']),
            'study_location' => trim($_POST['study_location'])
        ];
        
        // Check empty fields
        foreach ($fields as $field => $value) {
            if (empty($value)) {
                throw new Exception("All fields are required");
            }
        }
        
        if (empty($_POST['password'])) {
            throw new Exception("Password is required");
        }
        
        // Validate email
        if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check for duplicates
        $duplicate_check = $conn->prepare("
            SELECT 
                IF(reg_number = ?, 'Registration Number', NULL) AS reg_dup,
                IF(university_id = ?, 'University ID', NULL) AS uni_dup,
                IF(proof_id = ?, 'Proof ID', NULL) AS proof_dup,
                IF(email = ?, 'Email', NULL) AS email_dup
            FROM students
            WHERE reg_number = ? OR university_id = ? OR proof_id = ? OR email = ?
            LIMIT 1
        ");
        $duplicate_check->bind_param("ssssssss", 
            $fields['reg_number'], $fields['university_id'], $fields['proof_id'], $fields['email'],
            $fields['reg_number'], $fields['university_id'], $fields['proof_id'], $fields['email']
        );
        $duplicate_check->execute();
        $dup_result = $duplicate_check->get_result()->fetch_assoc();
        
        $duplicate_fields = array_filter($dup_result);
        if (!empty($duplicate_fields)) {
            $error_msg = "Duplicate value found for: " . implode(', ', $duplicate_fields);
            echo "<script>
                alertError('$error_msg');
                function alertError(msg) {
                    var alertBox = document.createElement('div');
                    alertBox.style.position = 'fixed';
                    alertBox.style.top = '20px';
                    alertBox.style.left = '50%';
                    alertBox.style.transform = 'translateX(-50%)';
                    alertBox.style.padding = '15px 25px';
                    alertBox.style.backgroundColor = '#ff4444';
                    alertBox.style.color = 'white';
                    alertBox.style.borderRadius = '5px';
                    alertBox.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                    alertBox.style.zIndex = '9999';
                    alertBox.style.fontFamily = 'Arial, sans-serif';
                    alertBox.style.fontSize = '16px';
                    alertBox.style.display = 'flex';
                    alertBox.style.alignItems = 'center';
                    alertBox.innerHTML = msg;
                    
                    // Add close button
                    var closeBtn = document.createElement('span');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.marginLeft = '15px';
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.style.fontSize = '20px';
                    closeBtn.onclick = function() {
                        document.body.removeChild(alertBox);
                    };
                    alertBox.appendChild(closeBtn);
                    
                    document.body.appendChild(alertBox);
                    
                    // Auto-close after 5 seconds
                    setTimeout(function() {
                        if (document.body.contains(alertBox)) {
                            document.body.removeChild(alertBox);
                        }
                    }, 5000);
                }
                window.history.back();
            </script>";
            exit();
        }

        // File upload handling
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Profile picture upload error");
        }

        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Only JPG, JPEG, PNG, GIF allowed");
        }

        $upload_dir = "uploads/";
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception("Cannot create upload directory");
        }

        $new_filename = uniqid('stu_', true) . '.' . $file_ext;
        $target_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
            throw new Exception("Failed to move uploaded file");
        }

        // Insert record
        $stmt = $conn->prepare("
            INSERT INTO students (
                name, reg_number, university_id, portal_status, 
                proof_id, email, batch_no, study_location, 
                profile_picture, password
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param(
            "ssssssssss", 
            $fields['name'], $fields['reg_number'], $fields['university_id'], $fields['portal_status'],
            $fields['proof_id'], $fields['email'], $fields['batch_no'], $fields['study_location'],
            $new_filename, $password_hash
        );

        if (!$stmt->execute()) {
            if ($conn->errno == 1062) { // MySQL duplicate entry error
                preg_match("/Duplicate entry '(.+)' for key '(.+)'/", $conn->error, $matches);
                $duplicate_value = $matches[1] ?? '';
                $duplicate_key = $matches[2] ?? '';
                
                $error_msg = "Database rejected duplicate $duplicate_key: $duplicate_value";
                echo "<script>
                    alertError('$error_msg');
                    window.history.back();
                </script>";
                exit();
            }
            throw new Exception("Database error: " . $stmt->error);
        }

        $conn->commit();
        echo "<script>
            alertSuccess('Student registered successfully!');
            function alertSuccess(msg) {
                var alertBox = document.createElement('div');
                alertBox.style.position = 'fixed';
                alertBox.style.top = '20px';
                alertBox.style.left = '50%';
                alertBox.style.transform = 'translateX(-50%)';
                alertBox.style.padding = '15px 25px';
                alertBox.style.backgroundColor = '#4CAF50';
                alertBox.style.color = 'white';
                alertBox.style.borderRadius = '5px';
                alertBox.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                alertBox.style.zIndex = '9999';
                alertBox.style.fontFamily = 'Arial, sans-serif';
                alertBox.style.fontSize = '16px';
                alertBox.style.display = 'flex';
                alertBox.style.alignItems = 'center';
                alertBox.innerHTML = msg;
                
                // Add close button
                var closeBtn = document.createElement('span');
                closeBtn.innerHTML = '&times;';
                closeBtn.style.marginLeft = '15px';
                closeBtn.style.cursor = 'pointer';
                closeBtn.style.fontSize = '20px';
                closeBtn.onclick = function() {
                    document.body.removeChild(alertBox);
                    window.location.href = 'admin_dashboard.php';
                };
                alertBox.appendChild(closeBtn);
                
                document.body.appendChild(alertBox);
                
                // Auto-redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = 'admin_dashboard.php';
                }, 3000);
            }
        </script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        
        // Clean up uploaded file if exists
        if (isset($target_path) && file_exists($target_path)) {
            unlink($target_path);
        }
        
        echo "<script>
            alertError('Error: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit();
    } finally {
        if (isset($duplicate_check)) $duplicate_check->close();
        if (isset($stmt)) $stmt->close();
    }
}
?>