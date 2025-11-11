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
$query = "SELECT m.movie_id, m.title, m.year, m.runtime, m.genres, 
          COALESCE(r.rating, 0) as rating, COALESCE(r.votes, 0) as votes
          FROM movies m
          LEFT JOIN ratings r ON m.movie_id = r.movie_id
          ORDER BY r.rating DESC, m.title ASC
          LIMIT $perPage OFFSET $offset";

$result = $conn->query($query);

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
                    <a href="movie.php?id=<?php echo urlencode($movie['movie_id']); ?>">
                        <div class="movie-poster">
                            <div class="poster-placeholder">🎬</div>
                        </div>
                        <div class="movie-info">
                            <h3 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <?php if ($movie['year']): ?>
                                <span class="movie-year"><?php echo htmlspecialchars($movie['year']); ?></span>
                            <?php endif; ?>
                            <?php if ($movie['rating'] > 0): ?>
                                <div class="movie-rating">
                                    <span class="rating-value">⭐ <?php echo number_format($movie['rating'], 1); ?></span>
                                    <span class="rating-votes">(<?php echo number_format($movie['votes']); ?> votes)</span>
                                </div>
                            <?php else: ?>
                                <div class="movie-rating no-rating">No ratings yet</div>
                            <?php endif; ?>
                            <?php if ($movie['runtime']): ?>
                                <span class="movie-runtime">⏱ <?php echo htmlspecialchars($movie['runtime']); ?> min</span>
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
$conn->close();
include 'includes/footer.php';
?>

