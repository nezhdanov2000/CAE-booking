<?php
require_once 'auth.php';
require_once 'db.php';

require_auth();

$query = "SELECT Course_ID, Course_name FROM Course ORDER BY Course_name";
$result = $conn->query($query);
$courses = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($courses);
?> 