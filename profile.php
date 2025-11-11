<?php
require_once 'config/database.php';

if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = "Profile";
$conn = getDBConnection();
$userId = $_SESSION['userId'];

// Get user's reviews
$reviewsQuery = "SELECT r.reviewId, r.reviewText, r.createdAt, m.movieId, m.title
                 FROM reviews r
                 JOIN movies m ON r.movieId = m.movieId
                 WHERE r.userId = ?
                 ORDER BY r.createdAt DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = [];
while ($row = $reviewsResult->fetch_assoc()) {
    $reviews[] = $row;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>My Profile</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
    </div>

    <div class="profile-section">
        <div class="profile-info">
            <h2>Account Information</h2>
            <div class="info-card">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
            </div>
        </div>

        <div class="reviews-section">
            <h2>My Reviews (<?php echo count($reviews); ?>)</h2>
            
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <a href="movie.php?id=<?php echo urlencode($review['movieId']); ?>" class="review-movie-link">
                                    <strong><?php echo htmlspecialchars($review['title']); ?></strong>
                                </a>
                                <span class="review-date"><?php echo date('F j, Y', strtotime($review['createdAt'])); ?></span>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($review['reviewText'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't written any reviews yet. <a href="index.php">Browse movies</a> to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
