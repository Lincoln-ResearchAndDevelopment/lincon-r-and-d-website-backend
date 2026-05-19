<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "inc/db.php";

$sql = "SELECT * FROM psa_projects ORDER BY created_at DESC";

$result = $conn->query($sql);
$projects = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $projects]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}

$conn->close();
?>
