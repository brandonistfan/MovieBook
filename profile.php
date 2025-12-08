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
$reviewsResult->close();
$stmt->close();

// Get user's ratings count
$ratingsQuery = "SELECT COUNT(*) as ratingCount FROM ratings WHERE userId = ?";
$stmt = $conn->prepare($ratingsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$ratingsResult = $stmt->get_result();
$ratingsData = $ratingsResult->fetch_assoc();
$ratingCount = $ratingsData['ratingCount'] ?? 0;
$ratingsResult->close();
$stmt->close();

$reviewCount = count($reviews);

include 'includes/header.php';
?>

<div class="container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
        </div>
        <div class="profile-info-header">
            <h1><?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($_SESSION['email']); ?></p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $reviewCount; ?></div>
                <div class="stat-label">Reviews</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $ratingCount; ?></div>
                <div class="stat-label">Ratings</div>
            </div>
        </div>
    </div>

    <!-- Account Information Section -->
    <div class="profile-section-card">
        <h2 class="section-title">Account Information</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Username</div>
                <div class="info-value"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value"><?php echo htmlspecialchars($_SESSION['email']); ?></div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="profile-section-card">
        <h2 class="section-title">My Reviews <span class="badge"><?php echo $reviewCount; ?></span></h2>
        
        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card profile-review-card">
                        <div class="review-header">
                            <div class="review-movie-info">
                                <a href="movie.php?id=<?php echo urlencode($review['movieId']); ?>" class="review-movie-link">
                                    <strong><?php echo htmlspecialchars($review['title']); ?></strong>
                                </a>
                                <span class="review-date"><?php echo date('F j, Y', strtotime($review['createdAt'])); ?></span>
                            </div>
                            <div class="review-actions">
                                <a href="movie.php?id=<?php echo urlencode($review['movieId']); ?>" class="btn-view-movie">View Movie</a>
                            </div>
                        </div>
                        <div class="review-text">
                            <?php echo nl2br(htmlspecialchars($review['reviewText'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📽️</div>
                <p>You haven't written any reviews yet.</p>
                <a href="index.php" class="btn btn-primary">Browse Movies</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
