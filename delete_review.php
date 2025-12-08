<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = isset($_POST['reviewId']) ? (int)$_POST['reviewId'] : 0;
    $currentUserId = $_SESSION['userId'];

    if (!isset($_SESSION['role'])) {
        $tmpConn = getDBConnection();
        $tmpStmt = $tmpConn->prepare("SELECT role FROM users WHERE userId = ?");
        $tmpStmt->bind_param("i", $currentUserId);
        $tmpStmt->execute();
        $tmpRes = $tmpStmt->get_result();
        if ($row = $tmpRes->fetch_assoc()) {
            $_SESSION['role'] = $row['role'] ?? 'user';
        }
        $tmpRes->close();
        $tmpStmt->close();
        $tmpConn->close();
    }
    $currentRole = $_SESSION['role'] ?? 'user';

    // Block restricted users from deleting
    if ($currentRole === 'restricted') {
        $_SESSION['error'] = 'Your account is restricted from deleting reviews.';
        header('Location: index.php');
        exit;
    }
    
    if ($reviewId <= 0) {
        $_SESSION['error'] = 'Invalid review ID.';
        header('Location: index.php');
        exit;
    }
    
    $conn = getDBConnection();
    
    // Admin can delete any review; user can only delete their own
    if ($currentRole === 'admin') {
        $getMovieQuery = "SELECT movieId, userId FROM reviews WHERE reviewId = ?";
        $stmt = $conn->prepare($getMovieQuery);
        $stmt->bind_param("i", $reviewId);
    } else {
        $getMovieQuery = "SELECT movieId, userId FROM reviews WHERE reviewId = ? AND userId = ?";
        $stmt = $conn->prepare($getMovieQuery);
        $stmt->bind_param("ii", $reviewId, $currentUserId);
    }

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
    $reviewOwnerId = $review['userId'];
    $result->close();
    $stmt->close();
    
    // Delete associated rating first (rating tied to original review owner)
    $deleteRatingQuery = "DELETE FROM ratings WHERE movieId = ? AND userId = ?";
    $ratingStmt = $conn->prepare($deleteRatingQuery);
    $ratingStmt->bind_param("ii", $movieId, $reviewOwnerId);
    $ratingStmt->execute();
    $ratingStmt->close();
    
    // Delete review (admin can delete any; user only their own)
    if ($currentRole === 'admin') {
        $deleteQuery = "DELETE FROM reviews WHERE reviewId = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $reviewId);
    } else {
        $deleteQuery = "DELETE FROM reviews WHERE reviewId = ? AND userId = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $reviewId, $currentUserId);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review and rating deleted successfully!';
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

