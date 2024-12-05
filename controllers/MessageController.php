<?php
    require_once(__DIR__ . '/connection.php');
    session_start();
    $recipient_options = ["1", "2", "3", "4"];

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $title = $_POST['title'];
        $recipient_id = $_POST['recipient'];
        $message = $_POST['message'];

        $has_error = false;

        // File upload handling
        $upload_dir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $uploaded_file_path = null;

        if (!empty($_FILES['attachment']['name'])) {
            $file_name = $_FILES['attachment']['name'];
            $file_tmp_name = $_FILES['attachment']['tmp_name'];
            $file_size = $_FILES['attachment']['size'];
            $file_error = $_FILES['attachment']['error'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'png', 'pdf', 'doc', 'docx'];

            if ($file_error === UPLOAD_ERR_OK) {
                if (!in_array($file_ext, $allowed_exts)) {
                    $_SESSION['error'][] = "Invalid file type. Allowed types: jpg, png, pdf, doc, docx.";
                    $has_error = true;
                } elseif ($file_size > 5 * 1024 * 1024) {
                    $_SESSION['error'][] = "File size exceeds the 5 MB limit.";
                    $has_error = true;
                } else {
                    $unique_file_name = $recipient_id . "_" . uniqid() . "_" . $file_name;
                    $uploaded_file_path = $upload_dir . $unique_file_name;
                    if (!move_uploaded_file($file_tmp_name, $uploaded_file_path)) {
                        $_SESSION['error'][] = "Failed to upload file.";
                        $has_error = true;
                    }
                }
            } else {
                $_SESSION['error'][] = "Error uploading file.";
                $has_error = true;
            }
        }

        if ($title == "") {
            $_SESSION['error'][] = "Title should not be empty!";
            $has_error = true;
        } else if (strlen($title) < 5 || strlen($title) > 32) {
            $_SESSION['error'][] = "Title length should be between 5 and 32 characters";
            $has_error = true;
        }

        if (!in_array((string)$recipient_id, $recipient_options)) {
            $_SESSION['error'][] = "Please pick a valid recipient!";
            $has_error = true;
        }

        if ($message == "") {
            $_SESSION['error'][] = "Message should not be empty!";
            $has_error = true;
        } else if (strlen($message) < 5 || strlen($message) > 256) {
            $_SESSION['error'][] = "Message length should be between 5 and 256 characters";
            $has_error = true;
        }

        if ($has_error) {
            header("Location: ../send.php");
            die;
        } else {
            $user_id = $_SESSION['user_id'];

            // (`id`, `sender_id`, `recipient_id`, `title`, `message`, `send_at`)
            $query = "INSERT INTO communications (id, sender_id, recipient_id, title, message, attachment, send_at) 
                  VALUES (NULL, $user_id, $recipient_id, '$title', '$message', '$uploaded_file_path', NOW());";
            if ($connection->query($query) === TRUE) {
                $_SESSION['success'][] = "Message has been sent!";
            } else {
                $_SESSION['error'][] = "Error: " . $sql . " | " . $conn->error;
            }

            header("Location: ../send.php");
            $connection->close();
        }
    }
