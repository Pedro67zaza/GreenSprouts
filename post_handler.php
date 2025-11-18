<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if(!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userId = $_SESSION['userId'];
$caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';

// Validate image upload
if(!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload an image']);
    exit();
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file type
if(!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, GIF, or WebP']);
    exit();
}

// Validate file size
if($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
    exit();
}

// Create upload directory if it doesn't exist
$uploadDir = 'Images/posts/';
if(!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = uniqid('post_', true) . '.' . $extension;
$uploadPath = $uploadDir . $fileName;

// Move uploaded file
if(!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    exit();
}

// Insert post into database (removed postLikes column since we don't need it)
$query = $connect->prepare("INSERT INTO posts (userId, postImage, postCaption, createdAt) VALUES (?, ?, ?, NOW())");
$query->bind_param("iss", $userId, $uploadPath, $caption);

if($query->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Post created successfully',
        'postId' => $connect->insert_id
    ]);
} else {
    // Delete uploaded file if database insert fails
    unlink($uploadPath);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $connect->error]);
}

$query->close();
$connect->close();
?>