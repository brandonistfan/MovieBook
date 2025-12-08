<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = isset($_POST['reviewId']) ? (int)$_POST['reviewId'] : 0;
    $userId = $_SESSION['userId'];
    
    if ($reviewId <= 0) {
        $_SESSION['error'] = 'Invalid review ID.';
        header('Location: index.php');
        exit;
    }
    
    $conn = getDBConnection();
    
    // Get the movieId before deleting (for redirect)
    $getMovieQuery = "SELECT movieId FROM reviews WHERE reviewId = ? AND userId = ?";
    $stmt = $conn->prepare($getMovieQuery);
    $stmt->bind_param("ii", $reviewId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $result->close();
        $stmt->close();
        $_SESSION['error'] = 'You do not have permission to delete this review.';
        $conn->close();
        header('Location: index.php');
        exit;
    }
    
    $review = $result->fetch_assoc();
    $movieId = $review['movieId'];
    $result->close();
    $stmt->close();
    
    // Delete review
    $deleteQuery = "DELETE FROM reviews WHERE reviewId = ? AND userId = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $reviewId, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete review. Please try again.';
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

