<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "university_db");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch students based on university_id
$students = $conn->query("SELECT id, name, university_id FROM students");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $university_id = $_POST['university_id'];
    $course_name = $_POST['course_name'];
    $course_fee = $_POST['course_fee'];
    $subject1 = $_POST['subject1'];
    $subject2 = $_POST['subject2'];
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];

    // Fetch the student id based on the university_id
    $student_result = $conn->prepare("SELECT id FROM students WHERE university_id = ?");
    $student_result->bind_param("s", $university_id);
    $student_result->execute();
    $student_result = $student_result->get_result();
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
        $student_id = $student['id'];
    } else {
        echo "Student not found!";
        exit;
    }

    // Check if the course already exists
    $course_result = $conn->prepare("SELECT id FROM courses WHERE course_name = ?");
    $course_result->bind_param("s", $course_name);
    $course_result->execute();
    $course_result = $course_result->get_result();

    // If course doesn't exist, add it to the database
    if ($course_result->num_rows == 0) {
        $insert_course = $conn->prepare("INSERT INTO courses (course_name, subject1, subject2) VALUES (?, ?, ?)");
        $insert_course->bind_param("sss", $course_name, $subject1, $subject2);
        $insert_course->execute();
        $course_id = $conn->insert_id;
    } else {
        $course = $course_result->fetch_assoc();
        $course_id = $course['id'];
    }

    // Insert student-course enrollment
    $insert_enrollment = $conn->prepare("INSERT INTO student_courses (student_id, course_id, course_fee, from_date, to_date) VALUES (?, ?, ?, ?, ?)");
    $insert_enrollment->bind_param("iisss", $student_id, $course_id, $course_fee, $from_date, $to_date);
    $insert_enrollment->execute();

    // Calculate monthly fee
    $duration_in_months = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24 * 30);
    $monthly_fee = $course_fee / $duration_in_months;

    // Return calculated monthly fee
    echo json_encode(array('monthly_fee' => number_format($monthly_fee, 2)));
    exit; // End the script
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Course</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        form {
            width: 50%;
            margin: 0 auto;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        /* Loading icon style */
        .loading {
            display: none;
            text-align: center;
            margin: 10px 0;
        }

        .loading img {
            width: 50px;
        }

        /* Dashboard */
        .dashboard {
            margin-top: 30px;
            padding: 10px;
            background-color: #f4f4f4;
            border-radius: 5px;
        }
        .dashboard h3 {
            margin: 0;
        }
    </style>
</head>
<body>

    <h1>Add Course</h1>

    <form id="courseForm" method="POST">
        <label for="university_id">Select Student</label>
        <select name="university_id" required>
            <option value="">Select Student</option>
            <?php while ($row = $students->fetch_assoc()) { ?>
                <option value="<?php echo $row['university_id']; ?>"><?php echo $row['name']; ?></option>
            <?php } ?>
        </select>

        <label for="course_name">Course Name</label>
        <input type="text" name="course_name" required>

        <label for="subject1">Subject 1</label>
        <input type="text" name="subject1" required>

        <label for="subject2">Subject 2</label>
        <input type="text" name="subject2" required>

        <label for="course_fee">Course Fee (LKR)</label>
        <input type="number" name="course_fee" required>

        <label for="from_date">From Date</label>
        <input type="date" name="from_date" id="from_date" required>

        <label for="to_date">To Date</label>
        <input type="date" name="to_date" id="to_date" required>

        <button type="submit">Add Course</button>
    </form>

    <!-- Loading icon (will show while calculating the fee) -->
    <div class="loading" id="loading">
        <img src="loading.gif" alt="Loading...">
    </div>

    <!-- Dashboard for displaying monthly fee -->
    <div class="dashboard" id="dashboard" style="display: none;">
        <h3>Calculated Monthly Fee: <span id="monthly_fee"></span> LKR</h3>
    </div>

    <script>
        // When dates are selected, show loading icon and calculate the fee
        document.getElementById("courseForm").addEventListener("submit", function(event) {
            event.preventDefault();

            // Show loading icon
            document.getElementById("loading").style.display = "block";
            document.getElementById("dashboard").style.display = "none"; // Hide previous results

            // Gather the form data
            var formData = new FormData(this);

            // Make the AJAX request
            fetch('add_courses.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading icon and show calculated monthly fee
                document.getElementById("loading").style.display = "none";
                document.getElementById("dashboard").style.display = "block";
                document.getElementById("monthly_fee").innerText = data.monthly_fee;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById("loading").style.display = "none";
            });
        });
    </script>

</body>
</html>
