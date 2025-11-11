<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movieId = $_POST['movie_id'] ?? '';
    $reviewText = trim($_POST['review_text'] ?? '');
    $userId = $_SESSION['user_id'];
    
    if (empty($movieId) || empty($reviewText)) {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    
    $conn = getDBConnection();
    
    // Check if user has already reviewed this movie
    $checkQuery = "SELECT review_id FROM reviews WHERE movie_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("si", $movieId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'You have already reviewed this movie.';
        header('Location: movie.php?id=' . urlencode($movieId));
        exit;
    }
    
    // Insert review
    $insertQuery = "INSERT INTO reviews (movie_id, user_id, review_text) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sis", $movieId, $userId, $reviewText);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Review submitted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to submit review. Please try again.';
    }
    
    $conn->close();
    header('Location: movie.php?id=' . urlencode($movieId));
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>

