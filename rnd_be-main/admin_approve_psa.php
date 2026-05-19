<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if(!isset($data->id) || !isset($data->is_approved)) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

include "inc/db.php";

$id = $conn->real_escape_string($data->id);
$is_approved = $conn->real_escape_string($data->is_approved);

$sql = "UPDATE psa_projects SET is_approved = '$is_approved' WHERE id = '$id'";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["status" => "success", "message" => "PSA status updated"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update PSA status"]);
}

$conn->close();
?>
