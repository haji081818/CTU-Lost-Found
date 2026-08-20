<?php
$pageTitle = 'CTU - Danao Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

// ── Query Parameters ──────────────────────────────────────────
$typeFilter = $_GET['type']   ?? 'all';
$search     = trim($_GET['q'] ?? '');
$catFilter  = $_GET['cat']    ?? 'all';

// ── Stats ─────────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*)                                       AS total,
        SUM(type='lost'  AND status='active')          AS lost_active,
        SUM(type='found' AND status='active')          AS found_active,
        SUM(status='returned')                         AS returned
    FROM posts
")->fetch_assoc();

// ── Posts Query ───────────────────────────────────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if ($typeFilter === 'lost' || $typeFilter === 'found') {
    $where[]  = 'p.type = ?';
    $params[] = $typeFilter;
    $types   .= 's';
}
if ($catFilter !== 'all') {
    $where[]  = 'p.category = ?';
    $params[] = $catFilter;
    $types   .= 's';
}
if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)';
    $like      = "%$search%";
    $params[]  = $like;
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'sss';
}

$sql = "
    SELECT p.*, u.name AS poster_name, u.avatar AS poster_avatar
    FROM   posts p
    JOIN   users u ON u.id = p.user_id
    WHERE  " . implode(' AND ', $where) . "
    ORDER  BY p.created_at DESC
    LIMIT  60
";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Category icons map
$catIcons = [
    'Electronics'       => 'bi-phone',
    'Bags & Accessories'=> 'bi-bag',
    'Books & Documents' => 'bi-book',
    'Clothing'          => 'bi-gender-ambiguous',
    'Keys & Cards'      => 'bi-key',
    'Jewelry'           => 'bi-gem',
    'Sports Equipment'  => 'bi-dribbble',
    'Others'            => 'bi-box',
];
?>

<!-- ══════════════════════════════════════════
     HERO SECTION
══════════════════════════════════════════════ -->
<section class="hero-section">
    <div class="hero-bg-shapes" aria-hidden="true">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container-xl hero-inner">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="hero-eyebrow">
                    <span class="eyebrow-dot"></span>
                    Cebu Technological University — Danao Campus
                </div>
                <h1 class="hero-heading">
                    Find What's <span class="hero-highlight">Lost.</span><br>
                    Return What's <span class="hero-highlight found-hl">Found.</span>
                </h1>
                <p class="hero-sub">
                    A community-driven platform to help CTU Danao students recover
                    lost items and reconnect belongings with their owners.
                </p>
                <?php if (!isLoggedIn()): ?>
                <div class="hero-cta d-flex gap-3 flex-wrap">
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-hero-primary">
                        <i class="bi bi-person-plus me-2"></i>Get Started Free
                    </a>
                    <button type="button" class="btn btn-hero-ghost" id="browseItemsBtn">
                        Browse Items <i class="bi bi-arrow-down ms-1"></i>
                    </button>
                </div>
                <?php else: ?>
                <button class="btn btn-hero-primary" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-plus-lg me-2"></i>Post an Item
                </button>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <div class="hero-stats-grid">
                    <div class="hstat-card hstat-total">
                        <div class="hstat-icon"><i class="bi bi-collection-fill"></i></div>
                        <div class="hstat-num"><?= number_format($stats['total']) ?></div>
                        <div class="hstat-label">Total Posts</div>
                    </div>
                    <div class="hstat-card hstat-lost">
                        <div class="hstat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                        <div class="hstat-num"><?= number_format($stats['lost_active']) ?></div>
                        <div class="hstat-label">Still Lost</div>
                    </div>
                    <div class="hstat-card hstat-found">
                        <div class="hstat-icon"><i class="bi bi-hand-thumbs-up-fill"></i></div>
                        <div class="hstat-num"><?= number_format($stats['found_active']) ?></div>
                        <div class="hstat-label">Found Items</div>
                    </div>
                    <div class="hstat-card hstat-returned">
                        <div class="hstat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="hstat-num"><?= number_format($stats['returned']) ?></div>
                        <div class="hstat-label">Returned</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     FILTER / SEARCH BAR
══════════════════════════════════════════════ -->
<div class="filter-strip" id="feed">
    <div class="container-xl">
        <div class="d-flex flex-wrap align-items-center gap-2">

            <!-- Type Pills -->
            <div class="filter-pills d-flex gap-2 flex-wrap">
                <a href="?type=all<?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="fpill <?= $typeFilter==='all'?'fpill-active':'' ?>">
                    All Items
                </a>
                <a href="?type=lost<?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="fpill fpill-lost <?= $typeFilter==='lost'?'fpill-active-lost':'' ?>">
                    <i class="bi bi-exclamation-circle me-1"></i>Lost
                </a>
                <a href="?type=found<?= $search ? '&q='.urlencode($search) : '' ?>"
                   class="fpill fpill-found <?= $typeFilter==='found'?'fpill-active-found':'' ?>">
                    <i class="bi bi-hand-thumbs-up me-1"></i>Found
                </a>
            </div>

            <!-- Divider -->
            <div class="filter-divider d-none d-md-block"></div>

            <!-- Category Dropdown -->
            <div class="dropdown cat-dropdown">
                <button class="fpill dropdown-toggle <?= $catFilter!=='all'?'fpill-cat-active':'' ?>"
                        data-bs-toggle="dropdown">
                    <i class="bi bi-tag me-1"></i>
                    <?= $catFilter !== 'all' ? e($catFilter) : 'Category' ?>
                </button>
                <ul class="dropdown-menu shadow-sm border-0 rounded-3">
                    <li><a class="dropdown-item <?= $catFilter==='all'?'active':'' ?>"
                           href="?type=<?= e($typeFilter) ?>&q=<?= e($search) ?>">All Categories</a></li>
                    <?php foreach (array_keys($catIcons) as $cat): ?>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $catFilter===$cat?'active':'' ?>"
                           href="?type=<?= e($typeFilter) ?>&cat=<?= urlencode($cat) ?>&q=<?= e($search) ?>">
                            <i class="bi <?= $catIcons[$cat] ?> text-muted" style="font-size:.8rem;"></i>
                            <?= e($cat) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Spacer -->
            <div class="ms-auto"></div>

            <!-- Live Search -->
            <form action="" method="get" class="search-form">
                <?php if ($typeFilter !== 'all'): ?>
                    <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
                <?php endif; ?>
                <?php if ($catFilter !== 'all'): ?>
                    <input type="hidden" name="cat" value="<?= e($catFilter) ?>">
                <?php endif; ?>
                <div class="search-input-wrap">
                    <i class="bi bi-search search-ico"></i>
                    <input type="text" name="q" id="liveSearch"
                           class="form-control search-input"
                           placeholder="Search items, location…"
                           value="<?= e($search) ?>"
                           autocomplete="off">
                    <div id="searchSpinner" class="spinner-border spinner-border-sm text-primary me-2 search-spinner" role="status" style="display:none">
                        <span class="visually-hidden">Searching...</span>
                    </div>
                    <?php if ($search): ?>
                    <a href="?type=<?= e($typeFilter) ?>" class="search-clear">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Active search label -->
        <?php if ($search): ?>
        <div class="search-label mt-2">
            Showing results for <strong>"<?= e($search) ?>"</strong>
            <span class="result-count ms-1"><?= count($posts) ?> item<?= count($posts)!==1?'s':'' ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════
     MAIN FEED
══════════════════════════════════════════════ -->
<div class="container-xl mt-4 pb-5">
    <div class="row g-4">

        <!-- Feed Column -->
        <div class="col-lg-9">
            <?php if (empty($posts)): ?>
            <!-- Empty State -->
            <div class="empty-state-modern">
                <div class="es-icon">
                    <?php if ($typeFilter === 'lost'): ?>
                        <i class="bi bi-search"></i>
                    <?php elseif ($typeFilter === 'found'): ?>
                        <i class="bi bi-hand-thumbs-up"></i>
                    <?php else: ?>
                        <i class="bi bi-inbox"></i>
                    <?php endif; ?>
                </div>
                <h4>No items found</h4>
                <p>
                    <?= $search
                        ? 'No results for "' . e($search) . '". Try different keywords.'
                        : 'No posts yet. Be the first to report a lost or found item!' ?>
                </p>
                <?php if ($loggedIn): ?>
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-plus-lg me-2"></i>Post an Item
                </button>
                <?php else: ?>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary mt-2">Log In to Post</a>
                <?php endif; ?>
            </div>
            <?php else: ?>

            <!-- Feed Label -->
            <div class="feed-label d-flex align-items-center justify-content-between mb-3">
                <span class="feed-label-text">
                    <?php if ($typeFilter === 'lost'): ?>
                        <i class="bi bi-exclamation-circle text-danger me-2"></i>Lost Items
                    <?php elseif ($typeFilter === 'found'): ?>
                        <i class="bi bi-hand-thumbs-up text-success me-2"></i>Found Items
                    <?php else: ?>
                        <i class="bi bi-grid me-2"></i>All Items
                    <?php endif; ?>
                </span>
                <span class="feed-count"><?= count($posts) ?> post<?= count($posts)!==1?'s':'' ?></span>
            </div>

            <!-- Card Grid -->
            <div class="row g-3" id="cardGrid">
                <?php foreach ($posts as $post):
                    $isOwner    = $loggedIn && $_SESSION['user_id'] == $post['user_id'];
                    $imgSrc     = $post['image'] ? UPLOAD_URL . e($post['image']) : null;
                    $catIcon    = $catIcons[$post['category']] ?? 'bi-box';
                ?>
                <div class="col-sm-6 col-xl-4 card-item"
                     data-title="<?= e(strtolower($post['title'])) ?>"
                     data-desc="<?= e(strtolower($post['description'])) ?>"
                     data-loc="<?= e(strtolower($post['location'])) ?>">

                    <a href="<?= BASE_URL ?>item.php?id=<?= $post['id'] ?>" class="item-card-link">
                    <article class="item-card-modern <?= $post['status'] !== 'active' ? 'is-'.e($post['status']) : '' ?>">

                        <!-- Image / Placeholder -->
                        <div class="icm-img-wrap">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= e($post['title']) ?>" class="icm-img" loading="lazy">
                            <?php else: ?>
                                <div class="icm-img-placeholder">
                                    <i class="bi <?= $catIcon ?>"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Type badge -->
                            <div class="icm-type-badge <?= e($post['type']) ?>">
                                <?= $post['type'] === 'lost'
                                    ? '<i class="bi bi-exclamation-circle-fill me-1"></i>Lost'
                                    : '<i class="bi bi-hand-thumbs-up-fill me-1"></i>Found' ?>
                            </div>

                            <!-- Status badge if not active -->
                            <?php if ($post['status'] !== 'active'): ?>
                            <div class="icm-status-badge status-<?= e($post['status']) ?>">
                                <?= $post['status'] === 'claimed'
                                    ? '<i class="bi bi-person-check-fill me-1"></i>Claimed'
                                    : '<i class="bi bi-check-circle-fill me-1"></i>Returned' ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="icm-body">
                            <div class="icm-cat-row">
                                <span class="icm-cat-tag">
                                    <i class="bi <?= $catIcon ?> me-1"></i><?= e($post['category']) ?>
                                </span>
                                <span class="icm-time"><?= timeAgo($post['created_at']) ?></span>
                            </div>

                            <h3 class="icm-title"><?= e($post['title']) ?></h3>

                            <p class="icm-desc"><?= e($post['description']) ?></p>

                            <div class="icm-footer">
                                <div class="icm-location">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <?= e($post['location']) ?>
                                </div>
                                <div class="icm-poster">
                                    <div class="icm-avatar" style="overflow: hidden;">
                                    <?php if (!empty($post['poster_avatar'])): ?>
                                     <!-- Actual User Profile Picture -->
                                    <img src="<?= UPLOAD_URL . e($post['poster_avatar']) ?>"
                                    alt="<?= e($post['poster_name']) ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                    <span style="display: none;"><?= strtoupper(substr($post['poster_name'], 0, 1)) ?></span>
                                        <?php else: ?>
                                        <!-- Fallback Letter Initial if user has no picture -->
                                    <span><?= strtoupper(substr($post['poster_name'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                </div>
                        <span><?= e(explode(' ', $post['poster_name'])[0]) ?></span>
                        </div>
                            </div>
                        </div>

                    </article>
                    </a>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- No live-search results -->
            <div id="noResults">
                <div class="empty-state-modern">
                    <div class="es-icon"><i class="bi bi-search"></i></div>
                    <h4>No matching items</h4>
                    <p>Try different keywords.</p>
                </div>
            </div>

            <?php endif; ?>
        </div><!-- /feed col -->

        <!-- Sidebar -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="sidebar-sticky">

                <!-- Quick Post -->
                <?php if ($loggedIn): ?>
                
                <?php else: ?>
                <div class="sidebar-card text-center py-3">
                    <div class="sb-auth-icon"><i class="bi bi-search-heart"></i></div>
                    <h6 class="fw-bold mt-2 mb-1">Join CTU Lost & Found</h6>
                    <p class="text-muted small mb-3">Post and track lost or found items on campus.</p>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-sm w-100 mb-2">Create Account</a>
                    <a href="<?= BASE_URL ?>login.php"    class="btn btn-outline-secondary btn-sm w-100">Log In</a>
                </div>
                <?php endif; ?>

                <!-- Stats Card -->
                <div class="sidebar-card mt-3">
                    <h6 class="sb-section-title">Campus Overview</h6>
                    <div class="sb-stat-row">
                        <div class="sb-stat-dot dot-lost"></div>
                        <span class="sb-stat-label">Lost (active)</span>
                        <span class="sb-stat-val"><?= number_format($stats['lost_active']) ?></span>
                    </div>
                    <div class="sb-stat-row">
                        <div class="sb-stat-dot dot-found"></div>
                        <span class="sb-stat-label">Found (active)</span>
                        <span class="sb-stat-val"><?= number_format($stats['found_active']) ?></span>
                    </div>
                    <div class="sb-stat-row">
                        <div class="sb-stat-dot dot-returned"></div>
                        <span class="sb-stat-label">Returned</span>
                        <span class="sb-stat-val"><?= number_format($stats['returned']) ?></span>
                    </div>
                    <?php
                    $total = max(1, $stats['total']);
                    $returnedPct = round($stats['returned']/$total*100);
                    ?>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Return rate</span>
                            <strong><?= $returnedPct ?>%</strong>
                        </div>
                        <div class="sb-progress">
                            <div class="sb-progress-fill" style="width:<?= $returnedPct ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="sidebar-card mt-3">
                    <h6 class="sb-section-title">Browse by Category</h6>
                    <div class="sb-cat-list">
                        <?php foreach ($catIcons as $cat => $icon): ?>
                        <a href="?cat=<?= urlencode($cat) ?>&type=<?= e($typeFilter) ?>"
                           class="sb-cat-item <?= $catFilter===$cat?'active':'' ?>">
                            <i class="bi <?= $icon ?>"></i>
                            <span><?= e($cat) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tips -->
                <div class="sidebar-card sidebar-tips mt-3">
                    <h6 class="sb-section-title">
                        <i class="bi bi-lightbulb-fill me-2 text-warning"></i>Tips
                    </h6>
                    <ul class="tips-list">
                        <li>Include clear photos for faster matching</li>
                        <li>Mention exact location where item was lost/found</li>
                        <li>Update your post status once resolved</li>
                    </ul>
                </div>

            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
