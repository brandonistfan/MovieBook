<?php
require_once 'config/database.php';

$pageTitle = "Home";
$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count of movies
$countQuery = "SELECT COUNT(*) as total FROM movies";
$countResult = $conn->query($countQuery);
$totalMovies = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalMovies / $perPage);

// Get movies with ratings
$query = "SELECT m.movieId, m.title, m.description,
          GROUP_CONCAT(DISTINCT g.genreName ORDER BY g.genreName SEPARATOR ', ') AS genres,
          COALESCE(AVG(r.rating), 0) AS rating,
          COUNT(r.ratingId) AS votes
          FROM movies m
          LEFT JOIN movie_genres mg ON m.movieId = mg.movieId
          LEFT JOIN genres g ON mg.genreId = g.genreId
          LEFT JOIN ratings r ON m.movieId = r.movieId
          GROUP BY m.movieId, m.title, m.description
          ORDER BY rating DESC, m.title ASC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Discover Movies</h1>
        <p>Browse our collection of movies and share your thoughts</p>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="movies-grid">
            <?php while ($movie = $result->fetch_assoc()): ?>
                <div class="movie-card">
                    <a href="movie.php?id=<?php echo urlencode($movie['movieId']); ?>">
                        <div class="movie-poster">
                            <div class="poster-placeholder">🎬</div>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <?php if (!empty($movie['genres'])): ?>
                                <div class="movie-genres">
                                    <?php foreach (explode(', ', $movie['genres']) as $genre): ?>
                                        <?php if (!empty($genre)): ?>
                                            <span class="genre-tag"><?php echo htmlspecialchars($genre); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($movie['rating'] > 0): ?>
                                <div class="movie-rating">
                                    <span class="rating-value">⭐ <?php echo number_format($movie['rating'], 1); ?></span>
                                    <span class="rating-votes">(<?php echo number_format($movie['votes']); ?> ratings)</span>
                                </div>
                            <?php else: ?>
                                <div class="movie-rating no-rating">No ratings yet</div>
                            <?php endif; ?>
                            <?php if (!empty($movie['description'])): ?>
                                <p class="movie-description">
                                    <?php
                                        $desc = $movie['description'];
                                        $snippet = strlen($desc) > 140 ? substr($desc, 0, 137) . '...' : $desc;
                                        echo htmlspecialchars($snippet);
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">← Previous</a>
                <?php endif; ?>
                
                <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <h2>No movies found</h2>
            <p>The database is empty. Please populate it with your IMDB dataset.</p>
        </div>
    <?php endif; ?>
</div>

<?php
if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
include 'includes/footer.php';
?>
