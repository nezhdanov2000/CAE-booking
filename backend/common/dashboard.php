<?php
require_once 'auth.php';

header('Content-Type: application/json');

require_auth();

$response = [
    'name' => $_SESSION['name'],
    'surname' => $_SESSION['surname'],
    'role' => $_SESSION['role']
];

echo json_encode($response);
?> 