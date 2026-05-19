<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "inc/db.php";

$sql = "SELECT * FROM supervisors ORDER BY name ASC";
$result = $conn->query($sql);

$supervisors = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $supervisors[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $supervisors]);
} else {
    echo json_encode(["status" => "success", "data" => []]);
}

$conn->close();
?>
