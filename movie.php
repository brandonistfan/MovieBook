<?php
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$movieId = $_GET['id'];
$conn = getDBConnection();

// Get movie details
$movieQuery = "SELECT m.movie_id, m.title, m.year, m.runtime, m.genres,
               COALESCE(r.rating, 0) as rating, COALESCE(r.votes, 0) as votes
               FROM movies m
               LEFT JOIN ratings r ON m.movie_id = r.movie_id
               WHERE m.movie_id = ?";
$stmt = $conn->prepare($movieQuery);
$stmt->bind_param("s", $movieId);
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
                   JOIN people p ON md.person_id = p.person_id
                   WHERE md.movie_id = ?";
$stmt = $conn->prepare($directorsQuery);
$stmt->bind_param("s", $movieId);
$stmt->execute();
$directorsResult = $stmt->get_result();
$directors = [];
while ($row = $directorsResult->fetch_assoc()) {
    $directors[] = $row['name'];
}

// Get actors
$actorsQuery = "SELECT p.name FROM movie_actors ma
                JOIN people p ON ma.person_id = p.person_id
                WHERE ma.movie_id = ?
                LIMIT 10";
$stmt = $conn->prepare($actorsQuery);
$stmt->bind_param("s", $movieId);
$stmt->execute();
$actorsResult = $stmt->get_result();
$actors = [];
while ($row = $actorsResult->fetch_assoc()) {
    $actors[] = $row['name'];
}

// Get genres
$genresQuery = "SELECT genre FROM movie_genres WHERE movie_id = ?";
$stmt = $conn->prepare($genresQuery);
$stmt->bind_param("s", $movieId);
$stmt->execute();
$genresResult = $stmt->get_result();
$genres = [];
while ($row = $genresResult->fetch_assoc()) {
    $genres[] = $row['genre'];
}

// Get reviews
$reviewsQuery = "SELECT r.review_id, r.review_text, r.created_at, u.username
                 FROM reviews r
                 JOIN users u ON r.user_id = u.user_id
                 WHERE r.movie_id = ?
                 ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviewsQuery);
$stmt->bind_param("s", $movieId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = [];
while ($row = $reviewsResult->fetch_assoc()) {
    $reviews[] = $row;
}

// Check if user has already reviewed this movie
$userHasReviewed = false;
if (isset($_SESSION['user_id'])) {
    $checkQuery = "SELECT review_id FROM reviews WHERE movie_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("si", $movieId, $_SESSION['user_id']);
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
                
                <?php if ($movie['year']): ?>
                    <p class="movie-meta"><strong>Year:</strong> <?php echo htmlspecialchars($movie['year']); ?></p>
                <?php endif; ?>
                
                <?php if ($movie['runtime']): ?>
                    <p class="movie-meta"><strong>Runtime:</strong> <?php echo htmlspecialchars($movie['runtime']); ?> minutes</p>
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
                        <span class="rating-votes">(<?php echo number_format($movie['votes']); ?> votes)</span>
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
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (!$userHasReviewed): ?>
                    <div class="review-form-container">
                        <h3>Write a Review</h3>
                        <form action="submit_review.php" method="POST" class="review-form">
                            <input type="hidden" name="movie_id" value="<?php echo htmlspecialchars($movieId); ?>">
                            <textarea name="review_text" rows="5" placeholder="Share your thoughts about this movie..." required></textarea>
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
                                <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
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

