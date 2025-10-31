<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli("localhost", "root", "", "royivaiy_rnd",);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>