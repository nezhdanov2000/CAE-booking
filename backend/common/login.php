<?php
require_once 'auth.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!$email || !$password || !$role) {
        if (is_fetch_request()) {
            send_json_error('Please fill in all fields.', 400);
        } else {
            echo 'Please fill in all fields.';
        }
        exit();
    }

    if ($role === 'student') {
        $stmt = $conn->prepare('SELECT Student_ID, Password, Name, Surname FROM Student WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hash, $name, $surname);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['role'] = 'student';
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;
                $stmt->close();
                if (is_fetch_request()) {
                    echo json_encode(['redirect' => '../../frontend/common/dashboard.html']);
                } else {
                    header('Location: ../../frontend/common/dashboard.html');
                }
                exit();
            }
        }
        $stmt->close();
        if (is_fetch_request()) {
            send_json_error('Invalid email or password.', 401);
        } else {
            echo 'Invalid email or password.';
        }
        exit();
    } elseif ($role === 'tutor') {
        $stmt = $conn->prepare('SELECT Tutor_ID, Password, Name, Surname FROM Tutor WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $hash, $name, $surname);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['role'] = 'tutor';
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;
                $stmt->close();
                if (is_fetch_request()) {
                    echo json_encode(['redirect' => '../../frontend/common/dashboard.html']);
                } else {
                    header('Location: ../../frontend/common/dashboard.html');
                }
                exit();
            }
        }
        $stmt->close();
        if (is_fetch_request()) {
            send_json_error('Invalid email or password.', 401);
        } else {
            echo 'Invalid email or password.';
        }
        exit();
    } else {
        if (is_fetch_request()) {
            send_json_error('Invalid role.', 400);
        } else {
            echo 'Invalid role.';
        }
        exit();
    }
}

$conn->close();
?> 