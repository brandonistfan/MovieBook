<?php
require_once 'config/database.php';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movieId <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

// Get movie details
$movieQuery = "SELECT m.movieId, m.title, m.description,
               COALESCE(AVG(r.rating), 0) AS rating,
               COUNT(r.ratingId) AS votes
               FROM movies m
               LEFT JOIN ratings r ON m.movieId = r.movieId
               WHERE m.movieId = ?
               GROUP BY m.movieId, m.title, m.description";
$stmt = $conn->prepare($movieQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movieResult = $stmt->get_result();
$movie = $movieResult->fetch_assoc();

if (!$movie) {
    header('Location: index.php');
    exit;
}

$pageTitle = $movie['title'];

// Get directors
$directorsQuery = "SELECT p.name FROM movie_directors md
                   JOIN people p ON md.personId = p.personId
                   WHERE md.movieId = ?";
$stmt = $conn->prepare($directorsQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$directorsResult = $stmt->get_result();
$directors = [];
while ($row = $directorsResult->fetch_assoc()) {
    $directors[] = $row['name'];
}

// Get actors
$actorsQuery = "SELECT p.name FROM movie_actors ma
                JOIN people p ON ma.personId = p.personId
                WHERE ma.movieId = ?
                LIMIT 10";
$stmt = $conn->prepare($actorsQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$actorsResult = $stmt->get_result();
$actors = [];
while ($row = $actorsResult->fetch_assoc()) {
    $actors[] = $row['name'];
}

// Get genres
$genresQuery = "SELECT g.genreName AS genre FROM movie_genres mg
                JOIN genres g ON mg.genreId = g.genreId
                WHERE mg.movieId = ?";
$stmt = $conn->prepare($genresQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$genresResult = $stmt->get_result();
$genres = [];
while ($row = $genresResult->fetch_assoc()) {
    $genres[] = $row['genre'];
}

// Get reviews
$reviewsQuery = "SELECT r.reviewId, r.reviewText, r.createdAt, u.username
                 FROM reviews r
                 JOIN users u ON r.userId = u.userId
                 WHERE r.movieId = ?
                 ORDER BY r.createdAt DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = [];
while ($row = $reviewsResult->fetch_assoc()) {
$reviews[] = $row;
}

// Check if user has already reviewed this movie
$userHasReviewed = false;
if (isset($_SESSION['userId'])) {
    $checkQuery = "SELECT reviewId FROM reviews WHERE movieId = ? AND userId = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $movieId, $_SESSION['userId']);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $userHasReviewed = $checkResult->num_rows > 0;
}

include 'includes/header.php';
?>

<div class="container">
    <div class="movie-detail">
        <div class="movie-header">
            <div class="movie-poster-large">
                <div class="poster-placeholder-large">🎬</div>
            </div>
            <div class="movie-details">
                <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <?php if (!empty($movie['description'])): ?>
                    <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($directors)): ?>
                    <p class="movie-meta"><strong>Director<?php echo count($directors) > 1 ? 's' : ''; ?>:</strong> 
                        <?php echo htmlspecialchars(implode(', ', $directors)); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($actors)): ?>
                    <p class="movie-meta"><strong>Cast:</strong> 
                        <?php echo htmlspecialchars(implode(', ', $actors)); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($genres)): ?>
                    <div class="movie-genres">
                        <?php foreach ($genres as $genre): ?>
                            <span class="genre-tag"><?php echo htmlspecialchars($genre); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($movie['rating'] > 0): ?>
                    <div class="movie-rating-large">
                        <span class="rating-value-large">⭐ <?php echo number_format($movie['rating'], 1); ?></span>
                        <span class="rating-votes">(<?php echo number_format($movie['votes']); ?> ratings)</span>
                    </div>
                <?php else: ?>
                    <div class="movie-rating-large no-rating">No ratings yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Review Section -->
        <div class="reviews-section">
            <h2>Reviews</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['userId'])): ?>
                <?php if (!$userHasReviewed): ?>
                    <div class="review-form-container">
                        <h3>Write a Review</h3>
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="movieId" value="<?php echo htmlspecialchars($movieId); ?>">
                            <textarea name="reviewText" rows="5" placeholder="Share your thoughts about this movie..." required></textarea>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="info-message">
                        You have already reviewed this movie.
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="info-message">
                    <a href="login.php">Login</a> to write a review.
                </div>
            <?php endif; ?>

            <div class="reviews-list">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                <span class="review-date"><?php echo date('F j, Y', strtotime($review['createdAt'])); ?></span>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($review['reviewText'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No reviews yet. Be the first to review this movie!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
