<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

include('inc/db.php');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing project ID']);
    exit;
}
$id = intval($data['id']);
$archive = isset($data['archive']) ? intval($data['archive']) : 1;

$stmt = $conn->prepare('UPDATE student_projects SET archive = ? WHERE id = ?');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('ii', $archive, $id);
if ($stmt->execute()) {
    $status = $archive === 1 ? 'archived' : 'unarchived';
    echo json_encode(['success' => true, 'message' => "Project $status successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
