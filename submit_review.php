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

// Block restricted users from creating reviews
if (($_SESSION['role'] ?? 'user') === 'restricted') {
    $_SESSION['error'] = 'Your account is restricted from creating reviews.';
    header('Location: movie.php?id=' . urlencode($_POST['movieId'] ?? 0));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movieId = isset($_POST['movieId']) ? (int)$_POST['movieId'] : 0;
    $reviewText = trim($_POST['reviewText'] ?? '');
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $userId = $_SESSION['userId'];
    
    if ($movieId <= 0 || empty($reviewText)) {
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
    
    // Use stored procedure to insert review and rating together
    $stmt = $conn->prepare("CALL add_review(?, ?, ?, ?)");
    $stmt->bind_param("iisi", $userId, $movieId, $reviewText, $rating);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review and rating submitted successfully!';
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
