<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = isset($_POST['reviewId']) ? (int)$_POST['reviewId'] : 0;
    $movieId = isset($_POST['movieId']) ? (int)$_POST['movieId'] : 0;
    $reviewText = trim($_POST['reviewText'] ?? '');
    $userId = $_SESSION['userId'];
    
    if ($reviewId <= 0 || $movieId <= 0 || empty($reviewText)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    
    $conn = getDBConnection();
    
    // Verify that the review belongs to the current user
    $checkQuery = "SELECT reviewId, movieId FROM reviews WHERE reviewId = ? AND userId = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $reviewId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $result->close();
        $stmt->close();
        $_SESSION['error'] = 'You do not have permission to edit this review.';
        $conn->close();
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    $result->close();
    $stmt->close();
    
    // Update review
    $updateQuery = "UPDATE reviews SET reviewText = ? WHERE reviewId = ? AND userId = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sii", $reviewText, $reviewId, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update review. Please try again.';
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

