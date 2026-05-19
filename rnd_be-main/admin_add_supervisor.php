<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "inc/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['name']) && !empty($data['name'])) {
        $name = $conn->real_escape_string($data['name']);
        
        $sql = "INSERT INTO supervisors (name) VALUES ('$name')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Supervisor added successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error adding supervisor: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Supervisor name is required"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
$conn->close();
?>
