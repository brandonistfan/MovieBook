<?php
require_once 'config/database.php';

$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($movieId <= 0) {
    header('Location: index.php');
    exit;
}

$conn = getDBConnection();

if (isset($_SESSION['userId']) && !isset($_SESSION['role'])) {
    $roleStmt = $conn->prepare("SELECT role FROM users WHERE userId = ?");
    $roleStmt->bind_param("i", $_SESSION['userId']);
    $roleStmt->execute();
    $roleResult = $roleStmt->get_result();
    if ($roleRow = $roleResult->fetch_assoc()) {
        $_SESSION['role'] = $roleRow['role'] ?? 'user';
    }
    $roleResult->close();
    $roleStmt->close();
}

// Get movie details
$movieQuery = "SELECT m.movieId, m.title, m.description, m.releaseYear, m.runtimeMinutes,
               COALESCE(AVG(r.rating), 0) AS rating,
               COUNT(DISTINCT r.ratingId) AS votes
               FROM movies m
               LEFT JOIN ratings r ON m.movieId = r.movieId
               WHERE m.movieId = ?
               GROUP BY m.movieId, m.title, m.description, m.releaseYear, m.runtimeMinutes";
$stmt = $conn->prepare($movieQuery);
if (!$stmt) {
    $conn->close();
    header('Location: index.php');
    exit;
}
$stmt->bind_param("i", $movieId);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: index.php');
    exit;
}
$movieResult = $stmt->get_result();
$movie = $movieResult->fetch_assoc();
$movieResult->close();
$stmt->close();

if (!$movie) {
    $conn->close();
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
$directorsResult->close();
$stmt->close();

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
$actorsResult->close();
$stmt->close();

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
$genresResult->close();
$stmt->close();

$genreEmojis = [
    'Action' => '⚔️',
    'Adult' => '🔞',
    'Adventure' => '🧭',
    'Animation' => '🎨',
    'Biography' => '🧑‍💼',
    'Comedy' => '😂',
    'Crime' => '🕵️',
    'Documentary' => '🎥',
    'Drama' => '🎭',
    'Family' => '👨‍👩‍👧‍👦',
    'Fantasy' => '🧚',
    'Film-Noir' => '🌒',
    'Game-Show' => '🎲',
    'History' => '📜',
    'Horror' => '😱',
    'Music' => '🎵',
    'Musical' => '🎼',
    'Mystery' => '🕯️',
    'News' => '📰',
    'Reality-TV' => '📺',
    'Romance' => '💕',
    'Sci-Fi' => '🛸',
    'Short' => '⏱️',
    'Sport' => '🏅',
    'Talk-Show' => '🎙️',
    'Thriller' => '😨',
    'War' => '⚔️',
    'Western' => '🤠',
];
$firstGenre = $genres[0] ?? null;
$genreEmoji = $genreEmojis[$firstGenre] ?? '🎬';

// Get reviews with ratings
$reviewsQuery = "SELECT r.reviewId, r.reviewText, r.createdAt, u.username, r.userId, 
                 rat.rating
                 FROM reviews r
                 JOIN users u ON r.userId = u.userId
                 LEFT JOIN ratings rat ON r.movieId = rat.movieId AND r.userId = rat.userId
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
$reviewsResult->close();
$stmt->close();

// Check if user has already reviewed this movie
$userHasReviewed = false;
if (isset($_SESSION['userId'])) {
    $checkQuery = "SELECT reviewId FROM reviews WHERE movieId = ? AND userId = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $movieId, $_SESSION['userId']);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $userHasReviewed = $checkResult->num_rows > 0;
    $checkResult->close();
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="container">
    <div class="movie-detail">
        <div class="movie-header">
            <div class="movie-poster-large">
                <?php $posterLabel = ($firstGenre ?? 'Movie') . ' poster'; ?>
                <div class="poster-placeholder-large" role="img" aria-label="<?php echo htmlspecialchars($posterLabel); ?>">
                    <?php echo $genreEmoji; ?>
                </div>
            </div>
            <div class="movie-details">
                <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <div class="movie-meta-info">
                    <?php if (!empty($movie['releaseYear'])): ?>
                        <span class="meta-item"><strong>Year:</strong> <?php echo htmlspecialchars($movie['releaseYear']); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($movie['runtimeMinutes'])): ?>
                        <span class="meta-item"><strong>Runtime:</strong> <?php 
                            $hours = floor($movie['runtimeMinutes'] / 60);
                            $minutes = $movie['runtimeMinutes'] % 60;
                            if ($hours > 0) {
                                echo $hours . 'h ' . $minutes . 'm';
                            } else {
                                echo $minutes . 'm';
                            }
                        ?></span>
                    <?php endif; ?>
                </div>
                
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
                    <div class="movie-rating-large no-rating">⭐ <?php echo number_format($movie['votes']); ?> ratings</div>
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
                <?php if (($_SESSION['role'] ?? 'user') === 'restricted'): ?>
                    <div class="info-message">
                        Your account is restricted from creating reviews.
                    </div>
                <?php elseif (!$userHasReviewed): ?>
                    <div class="review-form-container">
                        <h3>Write a Review</h3>
                        <form action="submit_review.php" method="POST" class="review-form" id="review-form">
                            <input type="hidden" name="movieId" value="<?php echo htmlspecialchars($movieId); ?>">
                            
                            <div class="form-group">
                                <label for="rating">Your Rating *</label>
                                <div class="star-rating-input">
                                    <input type="hidden" name="rating" id="rating-value" value="0" required>
                                    <div class="star-rating-interactive" id="star-rating">
                                        <span class="star" data-rating="1">☆</span>
                                        <span class="star" data-rating="2">☆</span>
                                        <span class="star" data-rating="3">☆</span>
                                        <span class="star" data-rating="4">☆</span>
                                        <span class="star" data-rating="5">☆</span>
                                        <span class="star" data-rating="6">☆</span>
                                        <span class="star" data-rating="7">☆</span>
                                        <span class="star" data-rating="8">☆</span>
                                        <span class="star" data-rating="9">☆</span>
                                        <span class="star" data-rating="10">☆</span>
                                    </div>
                                    <div class="rating-display" id="rating-display">Click to rate</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reviewText">Your Review *</label>
                                <textarea name="reviewText" id="reviewText" rows="5" placeholder="Share your thoughts about this movie..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Review & Rating</button>
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
                        <div class="review-card" id="review-<?php echo $review['reviewId']; ?>">
                            <div class="review-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                    <?php if (!empty($review['rating'])): ?>
                                        <span class="user-rating">⭐ <?php echo htmlspecialchars($review['rating']); ?>/10</span>
                                    <?php endif; ?>
                                    <span class="review-date"><?php echo date('F j, Y', strtotime($review['createdAt'])); ?></span>
                                </div>
                                <?php
                                    $currentRole = $_SESSION['role'] ?? 'user';
                                    $isOwner = isset($_SESSION['userId']) && $_SESSION['userId'] == $review['userId'];
                                    $canEdit = isset($_SESSION['userId']) && $currentRole !== 'restricted' && $isOwner; // admin cannot edit others
                                    $canDelete = isset($_SESSION['userId']) && $currentRole !== 'restricted' && ($isOwner || $currentRole === 'admin');
                                ?>
                                <?php if ($canEdit || $canDelete): ?>
                                    <div class="review-actions">
                                        <?php if ($canEdit): ?>
                                            <button type="button" class="btn-edit" data-review-id="<?php echo $review['reviewId']; ?>">Edit</button>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <button type="button" class="btn-delete" data-review-id="<?php echo $review['reviewId']; ?>">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="review-text" id="review-text-<?php echo $review['reviewId']; ?>">
                                <?php echo nl2br(htmlspecialchars($review['reviewText'])); ?>
                            </div>
                            <div class="review-edit-form" id="review-edit-form-<?php echo $review['reviewId']; ?>" style="display: none;" data-original-text="<?php echo htmlspecialchars($review['reviewText'], ENT_QUOTES); ?>" data-original-rating="<?php echo htmlspecialchars($review['rating'] ?? 0); ?>">
                                <form action="edit_review.php" method="POST" class="review-edit-form-inner" data-review-id="<?php echo $review['reviewId']; ?>">
                                    <input type="hidden" name="reviewId" value="<?php echo $review['reviewId']; ?>">
                                    <input type="hidden" name="movieId" value="<?php echo htmlspecialchars($movieId); ?>">
                                    
                                    <div class="form-group">
                                        <label>Your Rating *</label>
                                        <div class="star-rating-input">
                                            <input type="hidden" name="rating" class="rating-value-edit" value="<?php echo htmlspecialchars($review['rating'] ?? 0); ?>" required>
                                            <div class="star-rating-interactive star-rating-edit" data-review-id="<?php echo $review['reviewId']; ?>">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                    <span class="star <?php echo ($i <= ($review['rating'] ?? 0)) ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>"><?php echo ($i <= ($review['rating'] ?? 0)) ? '★' : '☆'; ?></span>
                                                <?php endfor; ?>
                                            </div>
                                            <div class="rating-display rating-display-edit"><?php echo $review['rating'] ? $review['rating'] . ' / 10' : 'Click to rate'; ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Your Review *</label>
                                        <textarea name="reviewText" rows="5" required><?php echo htmlspecialchars($review['reviewText']); ?></textarea>
                                    </div>
                                    
                                    <div class="review-edit-actions">
                                        <button type="submit" class="btn btn-primary">Save</button>
                                        <button type="button" class="btn btn-cancel" data-review-id="<?php echo $review['reviewId']; ?>">Cancel</button>
                                    </div>
                                </form>
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
