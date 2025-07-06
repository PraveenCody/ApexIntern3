<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pagination logic
$postsPerPage = 6; // Number of posts per page (matches your 2-column layout)
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1; // Ensure page is at least 1
$offset = ($currentPage - 1) * $postsPerPage;

// Get total number of posts for the current user
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalPosts = $stmt->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

// Adjust current page if it exceeds total pages
if ($totalPages > 0 && $currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $postsPerPage;
}

// Get posts for current page
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, $postsPerPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();
renderHeader('My Posts');
?>
<style>
/* General Styles */
body {
    font-family: 'Arial', sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 0;
    color: #333;
    background-color: #f5f5f5;
}

.container {
    width: 80%;
    margin: 0 auto;
    padding: 20px;
}

/* Header Styles */
header {
    background: #333;
    color: #fff;
    padding: 20px 0;
    margin-bottom: 20px;
}

header h1 {
    margin: 0;
}

/* Search Form */
.search-form {
    margin: 20px 0;
}

.search-form input {
    padding: 8px;
    width: 300px;
    border: 1px solid #ddd;
}

.search-form button {
    padding: 8px 15px;
    background: #333;
    color: #fff;
    border: none;
    cursor: pointer;
}

/* Posts List */
.posts-list {
    margin: 20px 0;
}

.post {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.post h3 {
    margin-top: 0;
    color: #333;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    margin: 20px 0;
}

.pagination a {
    padding: 8px 16px;
    margin: 0 4px;
    text-decoration: none;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.pagination a.active {
    background: #333;
    color: #fff;
    border: 1px solid #333;
}

.pagination a:hover:not(.active) {
    background: #ddd;
}

/* Footer */
footer {
    text-align: center;
    padding: 20px 0;
    margin-top: 20px;
    background: #333;
    color: #fff;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Posts</h1>
    <a href="create.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> New Post
    </a>
</div>

<!-- Search Form with Bootstrap Styling -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="search.php" method="GET" class="search-form row g-3 align-items-center">
            <div class="col-md-9">
                <input type="text" name="query" class="form-control form-control-lg" placeholder="Search your posts..." required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="alert alert-info">No posts found. Create your first post!</div>
<?php else: ?>
    <div class="row">
        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                        <div class="card-text post-content mb-3">
                            <?php echo nl2br(htmlspecialchars(substr($post['content'], 0, 200))); ?>
                            <?php if (strlen($post['content']) > 200): ?>...<?php endif; ?>
                        </div>
                        <small class="text-muted">
                            Posted on: <?php echo date('M j, Y g:i a', strtotime($post['created_at'])); ?>
                        </small>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-flex justify-content-between">
                            <a href="edit.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="delete.php?id=<?php echo $post['id']; ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('Are you sure you want to delete this post?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                
                <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i === $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                
                <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php renderFooter(); ?>