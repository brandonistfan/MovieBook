<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movieId = isset($_POST['movieId']) ? (int)$_POST['movieId'] : 0;
    $reviewText = trim($_POST['reviewText'] ?? '');
    $userId = $_SESSION['userId'];
    
    if ($movieId <= 0 || empty($reviewText)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    
    $conn = getDBConnection();
    
    // Check if user has already reviewed this movie
    $checkQuery = "SELECT reviewId FROM reviews WHERE movieId = ? AND userId = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $movieId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $result->close();
        $stmt->close();
        $_SESSION['error'] = 'You have already reviewed this movie.';
        $conn->close();
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    $result->close();
    $stmt->close();
    
    // Insert review
    $insertQuery = "INSERT INTO reviews (movieId, userId, reviewText) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $movieId, $userId, $reviewText);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review submitted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to submit review. Please try again.';
    }
    $stmt->close();
    $conn->close();
    header('Location: movie.php?id=' . urlencode($movieId));
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
