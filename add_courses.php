<?php
session_start();
include 'config.php';

// Ensure only admins can access this page
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Retrieve university_id from students table
$university_id = null;
if (isset($_GET['id'])) {
    $student_id = intval($_GET['id']);
    $sql = "SELECT university_id FROM students WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        if ($student) {
            $university_id = $student['university_id'];
        } else {
            echo "No student found with the given ID.";
            exit();
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = htmlspecialchars($_POST['course_name']);
    $course_duration = htmlspecialchars($_POST['course_duration']);
    $course_description = htmlspecialchars($_POST['course_description']);

    // Retain university_id if set from GET (above), fallback to POST only if it's filled
    if (!empty($_POST['university_id'])) {
        $university_id = htmlspecialchars($_POST['university_id']);
    }

    // Get subjects and fee
    $subject_1 = isset($_POST['subject_1']) ? htmlspecialchars($_POST['subject_1']) : null;
    $subject_2 = isset($_POST['subject_2']) ? htmlspecialchars($_POST['subject_2']) : null;
    $subject_3 = isset($_POST['subject_3']) ? htmlspecialchars($_POST['subject_3']) : null;
    $subject_4 = isset($_POST['subject_4']) ? htmlspecialchars($_POST['subject_4']) : null;
    $course_fee = isset($_POST['course_fee']) ? htmlspecialchars($_POST['course_fee']) : null;

    // Initialize PDF file path as NULL (makes it optional)
    $target_file = NULL;

    // Only process file if one was uploaded
    if (!empty($_FILES["course_proposal_pdf"]["name"])) {
        $target_dir = "uploads/courses/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["course_proposal_pdf"]["name"], PATHINFO_EXTENSION));
        if ($file_extension != "pdf") {
            echo "Sorry, only PDF files are allowed.";
            exit();
        }

        $unique_filename = uniqid('course_', true) . '.pdf';
        $target_file = $target_dir . $unique_filename;

        if (!move_uploaded_file($_FILES["course_proposal_pdf"]["tmp_name"], $target_file)) {
            echo "Sorry, there was an error uploading your file.";
            exit();
        }
    }

    // Insert course data (PDF is now optional)
    $sql = "INSERT INTO courses (course_name, course_duration, course_description, course_proposal_pdf, university_id, subject_1, subject_2, subject_3, subject_4, course_fee) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssssss", $course_name, $course_duration, $course_description, $target_file, $university_id, $subject_1, $subject_2, $subject_3, $subject_4, $course_fee);
        if ($stmt->execute()) {
            header("Location: manage_students.php");
            exit();
        } else {
            // Clean up file if insert failed
            if ($target_file && file_exists($target_file)) {
                unlink($target_file);
            }
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course</title>
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

        .form-group {
            margin-bottom: 25px;
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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

        .btn {
            display: inline-block;
            padding: 15px 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 1.1em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }

        .btn i {
            margin-right: 10px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<body style="background-image: url('img/1012.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat;">

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

    <h2>Add New Course</h2>
    <form method="POST" enctype="multipart/form-data" onsubmit="removeFormattingBeforeSubmit()">
        <div class="grid-2">
            <div class="form-group">
                <label for="course_name">Course Name</label>
                <input type="text" class="form-control" name="course_name" id="course_name" required>
            </div>
            
            <div class="form-group">
                <label for="course_duration">Duration (months)</label>
                <input type="text" class="form-control" name="course_duration" id="course_duration" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="course_description">Course Description</label>
            <textarea class="form-control" name="course_description" id="course_description" required></textarea>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label for="subject_1">Subject 1</label>
                <input type="text" class="form-control" name="subject_1" id="subject_1" required>
            </div>
            
            <div class="form-group">
                <label for="subject_2">Subject 2</label>
                <input type="text" class="form-control" name="subject_2" id="subject_2" required>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="form-group">
                <label for="subject_3">Subject 3</label>
                <input type="text" class="form-control" name="subject_3" id="subject_3" required>
            </div>
            
            <div class="form-group">
                <label for="subject_4">Subject 4</label>
                <input type="text" class="form-control" name="subject_4" id="subject_4" required>
            </div>
        </div>
        
        <div class="form-group">
    <label for="university_id">University ID</label>
    <input type="text" class="form-control" name="university_id" id="university_id" 
           value="<?php echo isset($university_id) ? htmlspecialchars($university_id, ENT_QUOTES, 'UTF-8') : ''; ?>" 
           maxlength="50" required>
</div>


            <div class="form-group">
    <label for="course_fee">Course Fee (LKR)</label>
    <input type="text" class="form-control" name="course_fee" id="course_fee" required>
</div>

<script>
const courseFeeInput = document.getElementById('course_fee');

courseFeeInput.addEventListener('input', function(e) {
    const input = e.target;

    // Get raw cursor position before formatting
    const selectionStart = input.selectionStart;
    
    // Remove all non-digit characters
    let rawValue = input.value.replace(/,/g, '').replace(/\D/g, '');

    // Format the number
    let formatted = new Intl.NumberFormat('en-US').format(rawValue);

    // Set the new value
    input.value = formatted;

    // Adjust the cursor position
    let commasBefore = (input.value.slice(0, selectionStart).match(/,/g) || []).length;
    let newPos = selectionStart + (formatted.length - rawValue.length);

    // Cap cursor within the input length
    input.setSelectionRange(newPos, newPos);
});
</script>


        
        <div class="form-group">
            <label>Course Proposal (PDF only)</label>
            <div class="file-input">
                <label class="file-input-label" for="course_proposal_pdf">
                    <i class="fas fa-file-pdf"></i>
                    <span id="file-name">Choose a PDF file</span>
                    <i class="fas fa-upload"></i>
                </label>
        <input type="file" name="course_proposal_pdf" id="course_proposal_pdf" accept=".pdf">
            </div>
        </div>
        
        <button type="submit" class="btn">
            <i class="fas fa-plus-circle"></i> Add Course
        </button>
    </form>
</div>

<script>
    function formatFee(input) {
        let value = input.value.replace(/,/g, '').replace(/[^\d.]/g, '');

        if (!isNaN(value) && value !== '') {
            let number = parseFloat(value).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            input.value = number;
        }
    }

    function removeFormattingBeforeSubmit() {
        const feeInput = document.getElementById('course_fee');
        feeInput.value = feeInput.value.replace(/,/g, '');
    }

    document.getElementById('course_proposal_pdf').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose a PDF file';
        document.getElementById('file-name').textContent = fileName;
    });
</script>
</body>
</html>