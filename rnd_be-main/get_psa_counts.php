<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include "inc/db.php";

$departments = [
    "Computer Software Engineering",
    "English and Mass Communication",
    "Physcology",
    "Foundation in Nursing",
    "Business Administration"
];

$counts = [];

foreach ($departments as $dept) {
    $sql = "SELECT COUNT(*) as total FROM psa_projects WHERE department = '$dept' AND is_approved = 1";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $counts[$dept] = (int)$row['total'];
}

// Also get grand total
$sql_total = "SELECT COUNT(*) as total FROM psa_projects WHERE is_approved = 1";
$result_total = $conn->query($sql_total);
$row_total = $result_total->fetch_assoc();
$grand_total = (int)$row_total['total'];

echo json_encode([
    "status" => "success",
    "counts" => $counts,
    "grand_total" => $grand_total
]);

$conn->close();
?>
