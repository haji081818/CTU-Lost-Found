<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$loggedIn = isLoggedIn();
$userName = $loggedIn ? ($_SESSION['user_name'] ?? 'User') : '';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><rect width='16' height='16' rx='3' fill='%23FFAB00'/><path fill='%230F2D5C' d='M6.5 2a4.5 4.5 0 1 0 2.672 8.086l2.622 2.621a.75.75 0 1 0 1.06-1.06L10.233 9.026A4.5 4.5 0 0 0 6.5 2m0 1a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7M5.5 5a.5.5 0 0 0 0 1h.585l-.431.588a.5.5 0 0 0 .346.846V8.5a.5.5 0 0 0 1 0V7.434a.5.5 0 0 0 .346-.846L6.915 6H7.5a.5.5 0 0 0 0-1z'/></svg>">
   <?php if (in_array($currentPage, ['login', 'register'])): ?>
<style>
    body { display: block; }
    .site-footer { display: none; }
    .main-content { padding-bottom: 0; margin-bottom: 0; }
</style>
<?php endif; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'CTU Danao Lost & Found' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Nunito:wght@400;500;600&display=swap" rel="stylesheet"> 
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark ctu-navbar sticky-top">
    <div class="container-xl">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
            <div class="brand-icon">
                <i class="bi bi-search-heart"></i>
            </div>
            <div>
                <span class="brand-title">CTU Danao</span>
                <span class="brand-sub d-block">Lost &amp; Found</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <!-- Center Search -->
            <div class="mx-auto d-none d-lg-block nav-search-wrap">
                <form action="<?= BASE_URL ?>" method="get" class="d-flex">
                    <div class="input-group nav-search">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="Search lost or found items..."
                               value="<?= e($_GET['q'] ?? '') ?>">
                    </div>
                </form>
            </div>

            <!-- Right nav -->
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <?php if ($loggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link nav-icon-btn <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= BASE_URL ?>">
                            <i class="bi bi-house"></i>
                            <span class="d-lg-none ms-2">Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-icon-btn <?= $currentPage === 'my-posts' ? 'active' : '' ?>" href="<?= BASE_URL ?>my-posts.php" title="My Posts">
                            <i class="bi bi-collection"></i>
                            <span class="d-lg-none ms-2">My Posts</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-icon-btn <?= $currentPage === 'claims' ? 'active' : '' ?>" href="<?= BASE_URL ?>claims.php" title="Manage Claims">
                            <i class="bi bi-bell"></i>
                            <span class="d-lg-none ms-2">Claims</span>
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-post" data-bs-toggle="modal" data-bs-target="#postModal">
                            <i class="bi bi-plus-lg"></i> Post Item
                        </button>
                    </li>
                    <li class="nav-item ms-lg-1 dropdown">
                        <a class="nav-link dropdown-toggle user-avatar-btn" href="#" data-bs-toggle="dropdown">
                            <?php
$currentUser = null;
if ($loggedIn) {
    $avatarStmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $avatarStmt->bind_param('i', $_SESSION['user_id']);
    $avatarStmt->execute();
    $currentUser = $avatarStmt->get_result()->fetch_assoc();
}
?>
<?php if (!empty($currentUser['avatar'])): ?>
    <img src="<?= UPLOAD_URL . e($currentUser['avatar']) ?>"
         alt="Avatar"
         style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px;">
<?php else: ?>
    <div class="user-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
<?php endif; ?>
                            <span class="d-lg-none ms-2"><?= e($userName) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text fw-semibold text-primary"><?= e($userName) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php"><i class="bi bi-person-fill me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>my-posts.php"><i class="bi bi-collection me-2"></i>My Posts</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>claims.php"><i class="bi bi-bell me-2"></i>Claims</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>login.php">Log In</a></li>
                    <li class="nav-item"><a class="btn btn-post ms-2" href="<?= BASE_URL ?>register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Toast Notification -->
<?php if ($flash): ?>
<div id="flashToast" class="flash-toast flash-toast-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>">
    <div class="flash-toast-icon">
        <?php if ($flash['type'] === 'success'): ?>
            <i class="bi bi-check-circle-fill"></i>
        <?php elseif ($flash['type'] === 'error'): ?>
            <i class="bi bi-x-circle-fill"></i>
        <?php elseif ($flash['type'] === 'warning'): ?>
            <i class="bi bi-exclamation-triangle-fill"></i>
        <?php else: ?>
            <i class="bi bi-info-circle-fill"></i>
        <?php endif; ?>
    </div>
    <span class="flash-toast-msg"><?= e($flash['msg']) ?></span>
    <button class="flash-toast-close" onclick="document.getElementById('flashToast').remove()">&times;</button>
</div>
<?php endif; ?>

<!-- Post Item Modal -->
<?php if ($loggedIn): ?>
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="postModalLabel">
                    <i class="bi bi-plus-circle me-2 text-primary"></i>Post an Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>actions/post_item.php" method="post" enctype="multipart/form-data" id="postForm">
                    <!-- Type Toggle -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">I want to report a:</label>
                        <div class="type-toggle d-flex gap-2">
                            <input type="radio" class="btn-check" name="type" id="typeLost" value="lost" required checked>
                            <label class="btn btn-outline-danger flex-fill" for="typeLost">
                                <i class="bi bi-exclamation-triangle me-1"></i> Lost Item
                            </label>
                            <input type="radio" class="btn-check" name="type" id="typeFound" value="found">
                            <label class="btn btn-outline-success flex-fill" for="typeFound">
                                <i class="bi bi-hand-thumbs-up me-1"></i> Found Item
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Item Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Black Samsung Galaxy A54" required maxlength="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>Select...</option>
                                <option>Electronics</option>
                                <option>Bags & Accessories</option>
                                <option>Books & Documents</option>
                                <option>Clothing</option>
                                <option>Keys & Cards</option>
                                <option>Jewelry</option>
                                <option>Sports Equipment</option>
                                <option>Others</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe the item in detail — color, brand, distinguishing marks..." required maxlength="1000"></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Library, Canteen, Room 302" required maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" placeholder="09XXXXXXXXX" maxlength="20">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Item Photo</label>
                            <input type="file" name="image" class="form-control" accept="image/*" id="imageInput">
                        </div>
                        <div class="col-12" id="imagePreviewWrap" style="display:none;">
                            <img id="imagePreview" src="" alt="Preview" class="img-thumbnail" style="max-height:160px;">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-1"></i> Submit Post
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<main class="main-content">