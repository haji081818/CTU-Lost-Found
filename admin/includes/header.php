<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

// Admin guard
if (!isLoggedIn() || empty($_SESSION['is_admin'])) {
    redirect(BASE_URL . 'admin/login.php');
}

$adminName   = $_SESSION['user_name'] ?? 'Admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin — CTU Lost & Found' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Nunito:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'><rect width='16' height='16' rx='3' fill='%23FFAB00'/><path fill='%230F2D5C' d='M6.5 2a4.5 4.5 0 1 0 2.672 8.086l2.622 2.621a.75.75 0 1 0 1.06-1.06L10.233 9.026A4.5 4.5 0 0 0 6.5 2m0 1a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7'/></svg>">
    <style>
        :root {
            --primary:#0F2D5C; --primary-mid:#1A4080; --accent:#FFAB00;
            --sidebar-w:240px; --border:#E2E8F0; --bg:#F0F4F8;
            --text-dark:#1A202C; --text-mid:#4A5568; --text-light:#718096;
        }
        *{box-sizing:border-box}
        body{font-family:'Nunito',sans-serif;background:var(--bg);margin:0;display:flex;min-height:100vh}
        h1,h2,h3,h4,h5,h6,.btn,.navbar-brand{font-family:'Outfit',sans-serif}

        /* Sidebar */
        .admin-sidebar{
            width:var(--sidebar-w);flex-shrink:0;
            background:linear-gradient(180deg,#0a1f44 0%,var(--primary) 100%);
            display:flex;flex-direction:column;
            position:fixed;top:0;left:0;height:100vh;
            overflow-y:auto;z-index:200;
        }
        .sidebar-brand{
            padding:1.25rem 1.2rem;
            border-bottom:1px solid rgba(255,255,255,.1);
            display:flex;align-items:center;gap:.75rem;
        }
        .sidebar-brand-icon{
            width:36px;height:36px;background:var(--accent);
            border-radius:9px;display:flex;align-items:center;justify-content:center;
            font-size:1.1rem;color:var(--primary);flex-shrink:0;
        }
        .sidebar-brand-text{font-weight:800;font-size:.9rem;color:#fff;line-height:1.2}
        .sidebar-brand-sub{font-size:.6rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.8px}
        .sidebar-nav{padding:.75rem 0;flex:1}
        .nav-section-label{
            font-size:.62rem;font-weight:700;letter-spacing:1px;
            text-transform:uppercase;color:rgba(255,255,255,.35);
            padding:.6rem 1.2rem .3rem;
        }
        .sidebar-link{
            display:flex;align-items:center;gap:.65rem;
            padding:.55rem 1.2rem;font-size:.85rem;font-weight:600;
            color:rgba(255,255,255,.7);text-decoration:none;
            border-left:3px solid transparent;
            transition:all .15s ease;
        }
        .sidebar-link i{font-size:1rem;flex-shrink:0}
        .sidebar-link:hover{background:rgba(255,255,255,.08);color:#fff;text-decoration:none}
        .sidebar-link.active{
            background:rgba(255,171,0,.15);color:var(--accent);
            border-left-color:var(--accent);
        }
        .sidebar-footer{
            padding:1rem 1.2rem;border-top:1px solid rgba(255,255,255,.1);
        }
        .sidebar-admin{display:flex;align-items:center;gap:.6rem}
        .sidebar-admin-avatar{
            width:32px;height:32px;background:var(--accent);
            color:var(--primary);border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-weight:800;font-size:.85rem;flex-shrink:0;
        }
        .sidebar-admin-name{font-size:.8rem;font-weight:700;color:#fff;line-height:1}
        .sidebar-admin-role{font-size:.65rem;color:rgba(255,255,255,.5)}

        /* Main */
        .admin-main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh}
        .admin-topbar{
            background:#fff;border-bottom:1px solid var(--border);
            padding:.7rem 1.5rem;display:flex;align-items:center;
            justify-content:space-between;position:sticky;top:0;z-index:100;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        .admin-topbar h5{font-weight:800;color:var(--text-dark);margin:0;font-size:1rem}
        .admin-content{padding:1.5rem;flex:1}

        /* Stat cards */
        .stat-card{
            background:#fff;border-radius:14px;border:1px solid var(--border);
            padding:1.25rem;display:flex;align-items:center;gap:1rem;
        }
        .stat-icon{
            width:52px;height:52px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            font-size:1.4rem;flex-shrink:0;
        }
        .stat-num{font-family:'Outfit',sans-serif;font-size:1.8rem;font-weight:800;line-height:1;color:var(--text-dark)}
        .stat-label{font-size:.75rem;color:var(--text-light);font-weight:600;text-transform:uppercase;letter-spacing:.5px}

        /* Tables */
        .admin-card{background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden}
        .admin-card-header{
            padding:.85rem 1.25rem;border-bottom:1px solid var(--border);
            display:flex;align-items:center;justify-content:space-between;
        }
        .admin-card-title{font-family:'Outfit',sans-serif;font-weight:800;font-size:.95rem;color:var(--text-dark);margin:0}
        .table{margin:0}
        .table th{
            font-family:'Outfit',sans-serif;font-size:.72rem;font-weight:700;
            text-transform:uppercase;letter-spacing:.5px;
            color:var(--text-light);background:#FAFBFC;border-bottom:1px solid var(--border);
            padding:.65rem 1rem;
        }
        .table td{padding:.7rem 1rem;vertical-align:middle;font-size:.85rem;border-color:var(--border)}
        .table tbody tr:hover{background:#FAFBFC}

        /* Badges */
        .role-badge{
            padding:.2rem .6rem;border-radius:50px;font-size:.65rem;
            font-family:'Outfit',sans-serif;font-weight:700;text-transform:uppercase;
        }
        .role-admin{background:#FEF3C7;color:#92400E}
        .role-user{background:#EDF2F7;color:#4A5568}

        /* Search */
        .admin-search{position:relative}
        .admin-search i{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--text-light)}
        .admin-search input{padding-left:2rem;border-radius:8px;border:1.5px solid var(--border);font-size:.85rem}
        .admin-search input:focus{border-color:var(--primary-mid);box-shadow:0 0 0 3px rgba(15,45,92,.1);outline:none}

        /* Flash */
        .flash-wrap{padding:.75rem 1.5rem 0}

        /* Forms */
        .form-label{font-size:.82rem;font-weight:600;color:var(--text-mid)}
        .form-control,.form-select{border-radius:8px;border:1.5px solid var(--border);font-size:.88rem}
        .form-control:focus,.form-select:focus{border-color:var(--primary-mid);box-shadow:0 0 0 3px rgba(15,45,92,.1)}
        .btn{font-family:'Outfit',sans-serif;font-weight:600;border-radius:8px}
        .btn-primary{background:var(--primary);border-color:var(--primary)}
        .btn-primary:hover{background:var(--primary-mid);border-color:var(--primary-mid)}

        /* Avatar */
        .user-avatar-sm{
            width:34px;height:34px;border-radius:50%;
            background:linear-gradient(135deg,var(--primary),#2356B0);
            color:#fff;display:flex;align-items:center;justify-content:center;
            font-weight:800;font-size:.8rem;flex-shrink:0;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon"><i class="bi bi-search-heart"></i></div>
        <div>
            <div class="sidebar-brand-text">CTU Danao</div>
            <div class="sidebar-brand-sub">Admin Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="<?= BASE_URL ?>admin/dashboard.php" class="sidebar-link <?= $currentPage==='dashboard'?'active':'' ?>">
            <i class="bi bi-grid-fill"></i> Dashboard
        </a>

        <div class="nav-section-label mt-2">Users</div>
        <a href="<?= BASE_URL ?>admin/users.php" class="sidebar-link <?= $currentPage==='users'?'active':'' ?>">
            <i class="bi bi-people-fill"></i> All Users
        </a>

        <div class="nav-section-label mt-2">Posts</div>
        <a href="<?= BASE_URL ?>admin/posts.php" class="sidebar-link <?= $currentPage==='posts'?'active':'' ?>">
            <i class="bi bi-collection-fill"></i> All Posts
        </a>
        <a href="<?= BASE_URL ?>admin/claims.php" class="sidebar-link <?= $currentPage==='claims'?'active':'' ?>">
            <i class="bi bi-bell-fill"></i> All Claims
        </a>

        <div class="nav-section-label mt-2">Site</div>
        <a href="<?= BASE_URL ?>" class="sidebar-link" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> View Site
        </a>
        <a href="<?= BASE_URL ?>admin/logout.php" class="sidebar-link text-danger-emphasis">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-admin">
            <div class="sidebar-admin-avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
            <div>
                <div class="sidebar-admin-name"><?= e($adminName) ?></div>
                <div class="sidebar-admin-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- MAIN CONTENT -->
<div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
        <h5><i class="bi bi-<?= $topbarIcon ?? 'grid' ?> me-2 text-primary"></i><?= $topbarTitle ?? 'Dashboard' ?></h5>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark rounded-pill" style="font-size:.7rem">
                <i class="bi bi-shield-check me-1"></i>Admin
            </span>
            <a href="<?= BASE_URL ?>admin/logout.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-left me-1"></i>Logout
            </a>
        </div>
    </div>

    <!-- Flash -->
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-wrap">
        <div class="alert alert-<?= $flash['type']==='error'?'danger':e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="admin-content">
