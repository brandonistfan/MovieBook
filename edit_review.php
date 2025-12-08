<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['role'])) {
    $tmpConn = getDBConnection();
    $tmpStmt = $tmpConn->prepare("SELECT role FROM users WHERE userId = ?");
    $tmpStmt->bind_param("i", $_SESSION['userId']);
    $tmpStmt->execute();
    $tmpRes = $tmpStmt->get_result();
    if ($row = $tmpRes->fetch_assoc()) {
        $_SESSION['role'] = $row['role'] ?? 'user';
    }
    $tmpRes->close();
    $tmpStmt->close();
    $tmpConn->close();
}

// Block restricted users from editing reviews
if (($_SESSION['role'] ?? 'user') === 'restricted') {
    $_SESSION['error'] = 'Your account is restricted from editing reviews.';
    header('Location: movie.php?id=' . urlencode($_POST['movieId'] ?? 0));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reviewId = isset($_POST['reviewId']) ? (int)$_POST['reviewId'] : 0;
    $movieId = isset($_POST['movieId']) ? (int)$_POST['movieId'] : 0;
    $reviewText = trim($_POST['reviewText'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $userId = $_SESSION['userId'];
    
    if ($reviewId <= 0 || $movieId <= 0 || empty($reviewText)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    
    if ($rating < 1 || $rating > 10) {
        $_SESSION['error'] = 'Please select a valid rating (1-10 stars).';
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
        // Update rating
        $updateRatingQuery = "UPDATE ratings SET rating = ? WHERE movieId = ? AND userId = ?";
        $ratingStmt = $conn->prepare($updateRatingQuery);
        $ratingStmt->bind_param("iii", $rating, $movieId, $userId);
        
        if ($ratingStmt->execute()) {
            $_SESSION['success'] = 'Review and rating updated successfully!';
        } else {
            $_SESSION['success'] = 'Review updated, but rating update failed.';
        }
        $ratingStmt->close();
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

