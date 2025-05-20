<?php
require 'config.php'; // Ensure correct database connection

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Debug: Log received data
    file_put_contents('debug_log.txt', "Received: ID=$student_id, Name=$name, Email=$email, Password=$password\n", FILE_APPEND);

    if (!$student_id || empty($name) || empty($email)) {
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit();
    }

    if (!empty($password)) {
        $query = "UPDATE students SET name=?, email=?, password=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $name, $email, $password, $student_id); // No hashing
    } else {
        $query = "UPDATE students SET name=?, email=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $name, $email, $student_id);
    }

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Update successful"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
?>

