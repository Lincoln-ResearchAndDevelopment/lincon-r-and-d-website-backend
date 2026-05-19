<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "inc/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentid = isset($_POST['studentid']) ? $conn->real_escape_string($_POST['studentid']) : '';
    $student_name = isset($_POST['student_name']) ? $conn->real_escape_string($_POST['student_name']) : '';
    $department = isset($_POST['department']) ? $conn->real_escape_string($_POST['department']) : '';
    $title = isset($_POST['title']) ? $conn->real_escape_string($_POST['title']) : '';
    $project_link = isset($_POST['project_link']) ? $conn->real_escape_string($_POST['project_link']) : '';
    $supervisor_name = isset($_POST['supervisor_name']) ? $conn->real_escape_string($_POST['supervisor_name']) : '';
    
    // File upload handling
    $report_file = "";
    if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] == 0) {
        $target_dir = "psadocs/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $filename = uniqid() . "_" . basename($_FILES["report_file"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
            $report_file = $target_file;
        }
    }
    
    if (empty($title) || empty($studentid) || empty($student_name)) {
        echo json_encode(["status" => "error", "message" => "Required fields missing"]);
        exit;
    }

    $sql = "INSERT INTO psa_projects (studentid, student_name, department, title, report_file, project_link, supervisor_name) 
            VALUES ('$studentid', '$student_name', '$department', '$title', '$report_file', '$project_link', '$supervisor_name')";
            
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["status" => "success", "message" => "PSA Project submitted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error submitting project: " . $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
$conn->close();
?>
