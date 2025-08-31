<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auth.php';
require_once 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';

    if (!$name || !$surname || !$password || !$email || !$role) {
        if (is_fetch_request()) {
            send_json_error('Please fill in all fields.', 400);
        } else {
            echo 'Please fill in all fields.';
        }
        exit();
    }

    $stmt = $conn->prepare('SELECT 1 FROM Student WHERE Email = ? UNION SELECT 1 FROM Tutor WHERE Email = ?');
    $stmt->bind_param('ss', $email, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        if (is_fetch_request()) {
            send_json_error('User with this email already exists.', 409);
        } else {
            echo 'User with this email already exists.';
        }
        exit();
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    if ($role === 'student') {
        $stmt = $conn->prepare('INSERT INTO Student (Name, Surname, Password, Email) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $surname, $hashed_password, $email);
        $success = $stmt->execute();
        $stmt->close();
    } elseif ($role === 'tutor') {
        $stmt = $conn->prepare('INSERT INTO Tutor (Name, Surname, Password, Email) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $surname, $hashed_password, $email);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        if (is_fetch_request()) {
            send_json_error('Invalid role.', 400);
        } else {
            echo 'Invalid role.';
        }
        exit();
    }

    if ($success) {
        if (is_fetch_request()) {
            send_json_success(null, '✅ Registration successful!');
        } else {
            echo '✅ Registration successful!';
        }
    } else {
        if (is_fetch_request()) {
            send_json_error('❌ Error during registration.', 500);
        } else {
            echo '❌ Error during registration.';
        }
    }
    exit();
}
?> 