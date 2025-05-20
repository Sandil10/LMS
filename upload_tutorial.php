<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = $_POST['subject'];
    $file = $_FILES['tutorial_file'];
    $file_path = "uploads/" . basename($file["name"]);
    
    if (move_uploaded_file($file["tmp_name"], $file_path)) {
        $conn->query("INSERT INTO tutorials (subject, file_path, uploaded_by) VALUES ('$subject', '$file_path', ".$_SESSION['admin'].")");
        echo "Tutorial uploaded successfully!";
    } else {
        echo "File upload failed!";
    }
}
?>

<form method="post" enctype="multipart/form-data">
    Subject: <input type="text" name="subject" required>
    File: <input type="file" name="tutorial_file" required>
    <button type="submit">Upload</button>
</form>
