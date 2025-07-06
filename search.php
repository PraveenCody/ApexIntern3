<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';


$postsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$offset = ($currentPage - 1) * $postsPerPage;

$sql = "SELECT * FROM posts 
        WHERE (title LIKE :query OR content LIKE :query)
        AND user_id = :user_id";

$queryParams = [
    ':query' => '%' . $query . '%',
    ':user_id' => $_SESSION['user_id']
];

if (!empty($_GET['date_range'])) {
    $dateFilter = '';
    switch ($_GET['date_range']) {
        case 'day': $dateFilter = 'INTERVAL 1 DAY'; break;
        case 'week': $dateFilter = 'INTERVAL 1 WEEK'; break;
        case 'month': $dateFilter = 'INTERVAL 1 MONTH'; break;
        case 'year': $dateFilter = 'INTERVAL 1 YEAR'; break;
    }
    $sql .= " AND created_at >= NOW() - $dateFilter";
}


$sort = $_GET['sort'] ?? 'relevance';
switch ($sort) {
    case 'newest': $sql .= " ORDER BY created_at DESC"; break;
    case 'oldest': $sql .= " ORDER BY created_at ASC"; break;
    default: $sql .= " ORDER BY 
        CASE WHEN title LIKE :query THEN 0 ELSE 1 END,
        CASE WHEN content LIKE :query THEN 1 ELSE 2 END,
        created_at DESC";
}


$countSql = str_replace('*', 'COUNT(*) as total', $sql);
$countStmt = $pdo->prepare($countSql);
foreach ($queryParams as $key => $value) {
    $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$countStmt->execute();
$totalPosts = $countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

if ($totalPages > 0 && $currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $postsPerPage;
}


$sql .= " LIMIT :limit OFFSET :offset";
$queryParams[':limit'] = $postsPerPage;
$queryParams[':offset'] = $offset;


$stmt = $pdo->prepare($sql);
foreach ($queryParams as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query string for pagination links
$queryString = http_build_query([
    'query' => $query,
    'date_range' => $_GET['date_range'] ?? '',
    'sort' => $sort,
    'per_page' => $postsPerPage
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - BlogApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .search-header {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .post-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .highlight {
            background-color: #fff3cd;
            padding: 0 2px;
            border-radius: 3px;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #212529;
                color: #f8f9fa;
            }
            .card {
                background-color: #2c3034;
                color: #f8f9fa;
            }
            .text-muted {
                color: #adb5bd !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">BlogApp</a>
            <form action="search.php" method="GET" class="d-flex">
                <input class="form-control me-2" type="search" name="query" placeholder="Search posts..." 
                       value="<?php echo htmlspecialchars($query); ?>" required>
                <button class="btn btn-outline-light" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="search-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="bi bi-search me-2"></i>Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Posts
                </a>
            </div>
            
            <!-- Search Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-funnel"></i> Filters</h5>
                    <form method="GET" id="search-filters">
                        <input type="hidden" name="query" value="<?php echo htmlspecialchars($query); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date Range</label>
                                <select name="date_range" class="form-select">
                                    <option value="">Any time</option>
                                    <option value="day" <?= ($_GET['date_range'] ?? '') == 'day' ? 'selected' : '' ?>>Last 24 hours</option>
                                    <option value="week" <?= ($_GET['date_range'] ?? '') == 'week' ? 'selected' : '' ?>>Last week</option>
                                    <option value="month" <?= ($_GET['date_range'] ?? '') == 'month' ? 'selected' : '' ?>>Last month</option>
                                    <option value="year" <?= ($_GET['date_range'] ?? '') == 'year' ? 'selected' : '' ?>>Last year</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sort By</label>
                                <select name="sort" class="form-select">
                                    <option value="relevance" <?= ($sort == 'relevance') ? 'selected' : '' ?>>Relevance</option>
                                    <option value="newest" <?= ($sort == 'newest') ? 'selected' : '' ?>>Newest first</option>
                                    <option value="oldest" <?= ($sort == 'oldest') ? 'selected' : '' ?>>Oldest first</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Items per page</label>
                                <select name="per_page" class="form-select">
                                    <option value="3" <?= $postsPerPage == 3 ? 'selected' : '' ?>>3</option>
                                    <option value="6" <?= $postsPerPage == 6 ? 'selected' : '' ?>>6</option>
                                    <option value="12" <?= $postsPerPage == 12 ? 'selected' : '' ?>>12</option>
                                    <option value="24" <?= $postsPerPage == 24 ? 'selected' : '' ?>>24</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Apply Filters</button>
                    </form>
                </div>
            </div>

            <?php if (count($results) > 0): ?>
                <div class="alert alert-info">
                    Found <?php echo $totalPosts; ?> result<?php echo $totalPosts != 1 ? 's' : '' ?>.
                    <?php if ($totalPosts > $postsPerPage): ?>
                        Showing <?php echo ($offset + 1).'-'.min($offset + $postsPerPage, $totalPosts); ?>.
                    <?php endif; ?>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($results as $post): ?>
                        <div class="col">
                            <div class="card h-100 post-card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                    <?php 
                                    // Highlight search term in title
                                    echo preg_replace(
                                        '/('.preg_quote($query, '/').')/i',  // Added missing parenthesis here
                                        '<span class="highlight">$0</span>', 
                                        htmlspecialchars($post['title'])
                                    );  // Added semicolon here
                                    ?>
                                    </h5>
                                    <div class="card-text mb-3">
                                        <?php 
                                        // Highlight search term in content preview
                                        $contentPreview = substr($post['content'], 0, 200);
                                        echo nl2br(preg_replace(
                                            '/('.preg_quote($query, '/').')/i',  // Added missing parenthesis here
                                            '<span class="highlight">$0</span>', 
                                            htmlspecialchars($contentPreview)
                                        ));  // Fixed parenthesis and added semicolon
                                        ?>
                                        <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-clock"></i> <?php echo date('M j, Y g:i a', strtotime($post['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between">
                                        <a href="view_post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Enhanced Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <!-- First Page -->
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=1&<?= $queryString ?>" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            
                            <!-- Previous Page -->
                            <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $currentPage-1 ?>&<?= $queryString ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            
                            if ($endPage - $startPage < 4 && $startPage > 1) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= $queryString ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Next Page -->
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $currentPage+1 ?>&<?= $queryString ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            
                            <!-- Last Page -->
                            <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $totalPages ?>&<?= $queryString ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    <h4 class="mt-2">No posts found</h4>
                    <p class="mb-0">Try different search terms or create a new post</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> BlogApp. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>