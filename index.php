<?php
require_once 'config/database.php';

$pageTitle = "Home";
$conn = getDBConnection();
$results = [];
$searchTerm = '';
$hasSearched = false;
$isSearchMode = false;

// Check if we have a search query
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchTerm = trim($_GET['q']);
    $hasSearched = true;
    $isSearchMode = true;
    
    $query = "SELECT m.movieId, m.title, m.description,
              GROUP_CONCAT(DISTINCT g.genreName ORDER BY g.genreName SEPARATOR ', ') AS genres,
              COALESCE(AVG(r.rating), 0) AS rating,
              COUNT(r.ratingId) AS votes
              FROM movies m
              LEFT JOIN movie_genres mg ON m.movieId = mg.movieId
              LEFT JOIN genres g ON mg.genreId = g.genreId
              LEFT JOIN ratings r ON m.movieId = r.movieId
              WHERE m.title LIKE ? OR m.description LIKE ? OR g.genreName LIKE ?
              GROUP BY m.movieId, m.title, m.description
              ORDER BY rating DESC, m.title ASC
              LIMIT 50";
    
    $searchPattern = '%' . $searchTerm . '%';
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $result->close();
    $stmt->close();
    $stmt = null; // Mark as closed so we don't try to close it again
} else {
    // Regular pagination mode
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    // Get total count of movies
    $countQuery = "SELECT COUNT(*) as total FROM movies";
    $countResult = $conn->query($countQuery);
    $totalMovies = $countResult->fetch_assoc()['total'];
    $countResult->close();
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
    // Note: $result and $stmt are closed at the end of the file
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Discover Movies</h1>
        <p>Browse our collection of movies and share your thoughts</p>
    </div>
    
    <div class="search-container">
        <form method="GET" action="index.php" class="search-form">
            <input type="text" name="q" placeholder="Search by title or genre..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($hasSearched): ?>
                <a href="index.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($isSearchMode): ?>
        <?php if ($hasSearched): ?>
            <?php if (!empty($results)): ?>
                <div class="search-results">
                    <h2>Found <?php echo count($results); ?> result<?php echo count($results) != 1 ? 's' : ''; ?></h2>
                    <div class="movies-grid">
                        <?php foreach ($results as $movie): ?>
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
                                            <div class="movie-rating no-rating">⭐ <?php echo number_format($movie['votes']); ?> ratings</div>
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
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h2>No results found</h2>
                    <p>Try searching with different keywords.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php elseif ($result && $result->num_rows > 0): ?>
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
                                <div class="movie-rating no-rating">⭐ <?php echo number_format($movie['votes']); ?> ratings</div>
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
        <?php if (isset($totalPages) && $totalPages > 1): ?>
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
// Only close statement if it exists and hasn't been closed yet
if (isset($stmt) && $stmt !== null) {
    try {
        $stmt->close();
    } catch (Error $e) {
        // Statement already closed, ignore
    }
}
$conn->close();
include 'includes/footer.php';
?>
