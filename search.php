<?php
require_once 'config/database.php';

$pageTitle = "Search Movies";
$conn = getDBConnection();
$results = [];
$searchTerm = '';
$hasSearched = false;

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $searchTerm = trim($_GET['q']);
    $hasSearched = true;
    
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
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Search Movies</h1>
    </div>
    
    <div class="search-container">
        <form method="GET" action="search.php" class="search-form">
            <input type="text" name="q" placeholder="Search by title or genre..." 
                   value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input" required>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
    
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
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
