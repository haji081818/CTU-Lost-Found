<?php
$pageTitle = 'Item Detail — CTU Lost & Found';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Item not found.'); redirect(BASE_URL); }

$stmt = $conn->prepare("
    SELECT p.*, u.name AS poster_name
    FROM   posts p
    JOIN   users u ON u.id = p.user_id
    WHERE  p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { setFlash('error','Item not found.'); redirect(BASE_URL); }

$pageTitle = e($post['title']) . ' — CTU Lost & Found';
$isOwner   = $loggedIn && $_SESSION['user_id'] == $post['user_id'];
$imgSrc    = $post['image'] ? UPLOAD_URL . e($post['image']) : null;

// Claims for this post
$claimsStmt = $conn->prepare("
    SELECT c.*, u.name AS claimant_name
    FROM   claims c
    JOIN   users u ON u.id = c.claimant_id
    WHERE  c.post_id = ?
    ORDER  BY c.created_at DESC
");
$claimsStmt->bind_param('i', $id);
$claimsStmt->execute();
$claims = $claimsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Feature 6: Get messages for approved claims
$claimMessages = [];
$messagingAvailable = false;

// Check if messages table exists
$messagesTableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
$messagingAvailable = $messagesTableCheck && $messagesTableCheck->num_rows > 0;

if ($messagingAvailable) {
    require_once __DIR__ . '/includes/messaging.php';
    
    // Check if current user has a claim on this post
    $userClaim = null;
    if ($loggedIn && !$isOwner) {
        foreach ($claims as $c) {
            if ($c['claimant_id'] == $_SESSION['user_id']) { 
                $userClaim = $c; 
                break; 
            }
        }
    }

    if ($loggedIn && $isOwner) {
        foreach ($claims as $claim) {
            if ($claim['status'] === 'approved' || $claim['status'] === 'pending') {
                $claimMessages[$claim['id']] = getClaimMessages($claim['id'], $_SESSION['user_id']);
            }
        }
    } elseif ($loggedIn && $userClaim) {
        if ($userClaim['status'] === 'approved' || $userClaim['status'] === 'pending') {
            $claimMessages[$userClaim['id']] = getClaimMessages($userClaim['id'], $_SESSION['user_id']);
        }
    }
}
$userClaim = null;
if ($loggedIn && !$isOwner) {
    foreach ($claims as $c) {
        if ($c['claimant_id'] == $_SESSION['user_id']) { $userClaim = $c; break; }
    }
}

// Similar items (same category, same type)
$simStmt = $conn->prepare("
    SELECT p.*, u.name AS poster_name
    FROM   posts p JOIN users u ON u.id = p.user_id
    WHERE  p.category = ? AND p.type = ? AND p.id <> ? AND p.status = 'active'
    ORDER  BY p.created_at DESC LIMIT 4
");
$simStmt->bind_param('ssi', $post['category'], $post['type'], $id);
$simStmt->execute();
$similar = $simStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Smart Match Radar for lost items
$smartMatches = [];
if ($post['type'] === 'lost') {
    // Check if post_matches table exists
    $matchesTableCheck = $conn->query("SHOW TABLES LIKE 'post_matches'");
    if ($matchesTableCheck && $matchesTableCheck->num_rows > 0) {
        // Get existing matches for this lost post
        $matchesStmt = $conn->prepare("
            SELECT pm.*, 
                   p.title AS found_title, 
                   p.category AS found_category,
                   p.location AS found_location,
                   p.location_zone AS found_zone,
                   p.image AS found_image,
                   u.name AS finder_name
            FROM   post_matches pm
            JOIN   posts p ON pm.found_post_id = p.id
            JOIN   users u ON p.user_id = u.id
            WHERE  pm.lost_post_id = ? AND pm.status = 'pending'
            ORDER  BY pm.match_score DESC LIMIT 5
        ");
        $matchesStmt->bind_param('i', $id);
        $matchesStmt->execute();
        $smartMatches = $matchesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Sightings for lost items
$sightings = [];
$sightingMessages = [];
$sightingAvailable = false;

// Check if sightings table exists
$sightingsTableCheck = $conn->query("SHOW TABLES LIKE 'sightings'");
$sightingAvailable = $sightingsTableCheck && $sightingsTableCheck->num_rows > 0;

if ($sightingAvailable && $post['type'] === 'lost') {
    // Get sightings for this post
    $sightingsStmt = $conn->prepare("
        SELECT s.*, 
               u.name AS reporter_name,
               u.avatar AS reporter_avatar
        FROM   sightings s
        JOIN   users u ON u.id = s.reporter_id
        WHERE  s.post_id = ?
        ORDER  BY s.created_at DESC
    ");
    $sightingsStmt->bind_param('i', $id);
    $sightingsStmt->execute();
    $sightings = $sightingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get sighting messages if user is owner or reporter
    if ($loggedIn) {
        foreach ($sightings as $sighting) {
            if ($isOwner || $sighting['reporter_id'] == $_SESSION['user_id']) {
                $sightingMessages[$sighting['id']] = getSightingMessages($sighting['id'], $_SESSION['user_id']);
            }
        }
    }
}
?>

<div class="container-xl py-4">
    <a href="<?= BASE_URL ?>" class="d-inline-flex align-items-center gap-2 text-muted mb-3 small fw-semibold"
       style="text-decoration:none">
        <i class="bi bi-arrow-left"></i> Back to Feed
    </a>

    <div class="row g-4">
        <!-- Image + Detail -->
        <div class="col-lg-7">
            <?php if ($imgSrc): ?>
                <img src="<?= $imgSrc ?>" alt="<?= e($post['title']) ?>" class="item-detail-img mb-3">
            <?php else: ?>
                <div class="item-detail-img mb-3 d-flex align-items-center justify-content-center"
                     style="background:#F0F4F8;border-radius:14px;height:320px">
                    <i class="bi bi-image text-muted" style="font-size:4rem;opacity:.25"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-5">
            <div class="detail-card">
                <!-- Type badge -->
                <div class="detail-type-badge <?= e($post['type']) ?>">
                    <?= $post['type']==='lost'
                        ? '<i class="bi bi-exclamation-circle-fill me-1"></i>Lost Item'
                        : '<i class="bi bi-hand-thumbs-up-fill me-1"></i>Found Item' ?>
                </div>

                <!-- Status -->
                <?php if ($post['status'] !== 'active'): ?>
                <div class="mb-2">
                    <span class="badge <?= $post['status']==='claimed' ? 'bg-purple' : 'bg-primary' ?>"
                          style="<?= $post['status']==='claimed' ? 'background:#6B46C1' : '' ?>; border-radius:50px; padding:.35rem .8rem">
                        <?= $post['status'] === 'claimed' ? '✓ Claimed' : '✓ Returned' ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <!-- Feature 4: Office Custody Badge -->
                <?php if (!empty($post['custody_office'])): ?>
                <div class="mb-2">
                    <span class="badge bg-success" style="border-radius:50px; padding:.35rem .8rem">
                        <i class="bi bi-building-check me-1"></i>In Campus Custody
                    </span>
                </div>
                <?php endif; ?>

                <h1 class="detail-title"><?= e($post['title']) ?></h1>

                <div class="detail-meta-item">
                    <i class="bi bi-tag-fill icon"></i>
                    <span><?= e($post['category']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-geo-alt-fill icon"></i>
                    <span><?= e($post['location']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-clock-fill icon"></i>
                    <span><?= timeAgo($post['created_at']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-person-fill icon"></i>
                    <span>Posted by <strong><?= e($post['poster_name']) ?></strong></span>
                </div>
                <?php if (!empty($post['contact_number'])): ?>
                <div class="detail-meta-item">
                    <i class="bi bi-telephone-fill icon"></i>
                    <span>Contact: <a href="tel:<?= e($post['contact_number']) ?>"><strong><?= e($post['contact_number']) ?></strong></a></span>
                </div>
                <?php else: ?>
                <div class="detail-meta-item">
                    <i class="bi bi-telephone-fill icon"></i>
                    <span class="text-muted">No contact number provided</span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($post['custody_office'])): ?>
                <div class="detail-meta-item bg-success bg-opacity-10 p-2 rounded">
                    <i class="bi bi-building-check icon text-success"></i>
                    <div>
                        <strong class="text-success">In Campus Custody</strong>
                        <div class="small text-muted">
                            <?= e(ucwords(str_replace('_', ' ', $post['custody_office']))) ?>
                            <?php if (!empty($post['custody_reference'])): ?>
                                <br>Ref: <strong><?= e($post['custody_reference']) ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <hr>
                <p class="text-muted" style="font-size:.9rem;line-height:1.7"><?= nl2br(e($post['description'])) ?></p>

                <!-- Owner actions -->
                <?php if ($isOwner && $post['status'] === 'active'): ?>
                <hr>
                <div class="d-flex gap-2 flex-wrap">
                    <form action="actions/update_status.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                        <input type="hidden" name="status" value="returned">
                        <button type="submit" class="btn btn-sm btn-outline-primary"
                                onclick="return confirm('Mark this item as returned?')">
                            <i class="bi bi-check-circle me-1"></i>Mark Returned
                        </button>
                    </form>
                    <form action="actions/delete_post.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this post?')">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Feature 7: Poster Generation & Share -->
                <hr>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>generate_poster.php?id=<?= $post['id'] ?>&format=html" 
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-file-earmark-text me-1"></i>View Poster
                    </a>
                    <a href="<?= BASE_URL ?>generate_poster.php?id=<?= $post['id'] ?>&format=pdf" 
                       class="btn btn-sm btn-outline-secondary" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                    </a>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareNoticeLink()">
                        <i class="bi bi-share me-1"></i>Share Notice Link
                    </button>
                </div>

                <!-- Claim button (non-owner, found item) -->
                <?php if ($loggedIn && !$isOwner && $post['type']==='found'): ?>
                <hr>
                <?php if ($userClaim): ?>
                    <div class="alert alert-info py-2 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        You submitted a claim — status: <strong><?= e($userClaim['status']) ?></strong>
                    </div>
                    
                    <!-- Feature 6: Messaging for claimants -->
                    <?php if ($messagingAvailable && ($userClaim['status'] === 'approved' || $userClaim['status'] === 'pending')): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="small"><i class="bi bi-chat-dots me-1"></i>Messages with Owner</strong>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleMessaging(<?= $userClaim['id'] ?>)">
                                <i class="bi bi-chat"></i> <?= isset($claimMessages[$userClaim['id']]) && !empty($claimMessages[$userClaim['id']]) ? 'View Chat' : 'Start Chat' ?>
                            </button>
                        </div>
                        <div id="messaging-<?= $userClaim['id'] ?>" class="messaging-container" style="display:none;">
                            <div class="message-thread">
                                <?php if (isset($claimMessages[$userClaim['id']]) && !empty($claimMessages[$userClaim['id']])): ?>
                                    <?php foreach ($claimMessages[$userClaim['id']] as $msg): ?>
                                    <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received' ?>">
                                        <div class="message-header">
                                            <strong><?= e($msg['sender_name']) ?></strong>
                                            <span class="message-time"><?= timeAgo($msg['created_at']) ?></span>
                                        </div>
                                        <div class="message-content"><?= e($msg['message']) ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-3">
                                        <i class="bi bi-chat-dots display-4 mb-2"></i>
                                        <p>No messages yet. Start the conversation!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form class="message-form" onsubmit="sendMessage(event, <?= $userClaim['id'] ?>)">
                                <?= csrfField() ?>
                                <input type="hidden" name="claim_id" value="<?= $userClaim['id'] ?>">
                                <div class="input-group">
                                    <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php elseif ($post['status']==='active'): ?>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#claimModal">
                        <i class="bi bi-hand-index me-2"></i>Claim This Item
                    </button>
                <?php else: ?>
                    <div class="alert alert-secondary py-2 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        This item is no longer available for claims.
                    </div>
                <?php endif; ?>
                <?php elseif (!$loggedIn && $post['type']==='found' && $post['status']==='active'): ?>
                <hr>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log in to Claim
                </a>
                <?php endif; ?>
                
                <!-- Sighting button (non-owner, lost item) -->
                <?php if ($loggedIn && !$isOwner && $post['type']==='lost' && $post['status']==='active'): ?>
                <hr>
                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#sightingModal">
                    <i class="bi bi-binoculars me-2"></i>I Found / Spotted This Item!
                </button>
                <?php elseif (!$loggedIn && $post['type']==='lost' && $post['status']==='active'): ?>
                <hr>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-success w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log in to Report Sighting
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Smart Match Radar (lost items only) -->
    <?php if ($post['type'] === 'lost' && !empty($smartMatches)): ?>
    <div class="row mt-4">
        <div class="col-lg-7">
            <h5 class="fw-bold mb-3"><i class="bi bi-radar me-2 text-primary"></i>Smart Match Radar</h5>
            <div class="alert alert-info py-2 mb-3 small">
                <i class="bi bi-info-circle me-1"></i>
                Found items that might match your lost item based on category, location, and keywords.
            </div>
            <div class="row g-2">
                <?php foreach ($smartMatches as $match): ?>
                <div class="col-sm-6 col-md-4">
                    <div class="match-card">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($match['found_image']): ?>
                                <img src="<?= UPLOAD_URL . e($match['found_image']) ?>" class="match-thumb" alt="">
                            <?php else: ?>
                                <div class="match-thumb-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="match-item-details flex-grow-1">
                                <div class="match-item-label text-muted">Match Score</div>
                                <span class="match-score"><?= number_format($match['match_score']) ?>%</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong class="small text-truncate d-block"><?= e($match['found_title']) ?></strong>
                            <div class="text-muted" style="font-size:.72rem">
                                <i class="bi bi-geo-alt"></i> <?= e($match['found_location']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.7rem">
                                <i class="bi bi-person"></i> <?= e($match['finder_name']) ?>
                            </div>
                        </div>
                        <div class="match-actions mt-2">
                            <a href="item.php?id=<?= $match['found_post_id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                <i class="bi bi-eye"></i> View
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sightings (lost items only) -->
    <?php if ($post['type'] === 'lost' && $sightingAvailable && count($sightings) > 0): ?>
    <div class="row mt-4">
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-binoculars me-2 text-primary"></i>Community Sighting & Recovery Reports (<?= count($sightings) ?>)</h5>
                <?php if ($loggedIn && !$isOwner && $post['status'] === 'active'): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#sightingModal">
                    <i class="bi bi-plus me-1"></i>Report Sighting
                </button>
                <?php endif; ?>
            </div>
            <?php foreach ($sightings as $sighting): ?>
            <div class="sighting-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <strong class="small"><?= e($sighting['reporter_name']) ?></strong>
                        <span class="text-muted small ms-2"><?= timeAgo($sighting['created_at']) ?></span>
                    </div>
                    <span class="sighting-status-pill <?= e($sighting['status']) ?>">
                        <?php 
                        $sightingTypeLabels = [
                            'possession' => '🎒 In Possession',
                            'custody' => '🏢 Surrendered to Campus Office',
                            'spotted' => '👁️ Spotted at Location'
                        ];
                        echo e($sightingTypeLabels[$sighting['sighting_type']] ?? ucfirst($sighting['sighting_type']));
                        ?>
                        [<?= strtoupper(e($sighting['status'])) ?>]
                    </span>
                </div>
                
                <?php if ($sighting['sighting_type'] === 'custody' && !empty($sighting['custody_office'])): ?>
                <div class="bg-success bg-opacity-10 p-2 rounded mb-2 small">
                    <strong class="text-success">📍 Location / Custody Info:</strong> 
                    <?= e($sighting['location_details']) ?>
                    <br>
                    <strong class="text-success">Campus Office:</strong> 
                    <?= e(ucwords(str_replace('_', ' ', $sighting['custody_office']))) ?>
                </div>
                <?php else: ?>
                <div class="bg-light p-2 rounded mb-2 small">
                    <strong class="text-primary">📍 Location Info:</strong> 
                    <?= e($sighting['location_details']) ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-2">
                    <strong class="small text-primary">💬 Message:</strong>
                    <p class="text-muted small mb-0"><?= e($sighting['message']) ?></p>
                </div>
                
                <?php if (!empty($sighting['proof_image'])): ?>
                <div class="mb-2">
                    <strong class="small text-primary">Proof Image:</strong>
                    <a href="<?= UPLOAD_URL . e($sighting['proof_image']) ?>" target="_blank" class="small">
                        <i class="bi bi-image"></i> View Proof
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($isOwner && $sighting['status'] === 'active' && $post['status'] === 'active'): ?>
                <div class="d-flex gap-2">
                    <form action="actions/sighting_action.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $sighting['id'] ?>">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check me-1"></i>Confirm Retrieved
                        </button>
                    </form>
                    <form action="actions/sighting_action.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $sighting['id'] ?>">
                        <input type="hidden" name="action" value="dismiss">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x me-1"></i>Dismiss
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Sighting Messaging -->
                <?php if ($loggedIn && ($isOwner || $sighting['reporter_id'] == $_SESSION['user_id'])): ?>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small"><i class="bi bi-chat-dots me-1"></i>Chat with <?= $isOwner ? e($sighting['reporter_name']) : 'Owner' ?></strong>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleSightingMessaging(<?= $sighting['id'] ?>)">
                            <i class="bi bi-chat"></i> <?= isset($sightingMessages[$sighting['id']]) && !empty($sightingMessages[$sighting['id']]) ? 'View Chat' : 'Start Chat' ?>
                        </button>
                    </div>
                    <div id="sighting-messaging-<?= $sighting['id'] ?>" class="messaging-container" style="display:none;">
                        <div class="message-thread">
                            <?php if (isset($sightingMessages[$sighting['id']]) && !empty($sightingMessages[$sighting['id']])): ?>
                                <?php foreach ($sightingMessages[$sighting['id']] as $msg): ?>
                                <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received' ?>">
                                    <div class="message-header">
                                        <strong><?= e($msg['sender_name']) ?></strong>
                                        <span class="message-time"><?= timeAgo($msg['created_at']) ?></span>
                                    </div>
                                    <div class="message-content"><?= e($msg['message']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted small py-3">
                                    <i class="bi bi-chat-dots display-4 mb-2"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form class="message-form" onsubmit="sendSightingMessage(event, <?= $sighting['id'] ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="sighting_id" value="<?= $sighting['id'] ?>">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Claims (owner view) -->
    <?php if ($isOwner && count($claims) > 0): ?>
    <div class="row mt-4">
        <div class="col-lg-7">
            <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>Claims (<?= count($claims) ?>)</h5>
            <?php foreach ($claims as $claim): ?>
            <div class="claim-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <strong class="small"><?= e($claim['claimant_name']) ?></strong>
                    <span class="claim-status-pill <?= e($claim['status']) ?>"><?= ucfirst($claim['status']) ?></span>
                </div>
                <p class="text-muted small mb-2"><?= e($claim['description']) ?></p>
                
                <?php if (!empty($claim['verification_answer'])): ?>
                <div class="bg-light p-2 rounded mb-2 small">
                    <strong class="text-primary">Verification Answer:</strong> 
                    <span class="text-muted"><?= e($claim['verification_answer']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($claim['proof_image'])): ?>
                <div class="mb-2">
                    <strong class="small text-primary">Proof Image:</strong>
                    <a href="<?= UPLOAD_URL . e($claim['proof_image']) ?>" target="_blank" class="small">
                        <i class="bi bi-image"></i> View Proof
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($claim['status'] === 'pending' && $post['status'] === 'active'): ?>
                <div class="d-flex gap-2">
                    <form action="actions/claim_action.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $claim['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-check me-1"></i>Approve
                        </button>
                    </form>
                    <form action="actions/claim_action.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $claim['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x me-1"></i>Reject
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Feature 6: Messaging for approved/pending claims -->
                <?php if ($messagingAvailable && ($claim['status'] === 'approved' || $claim['status'] === 'pending')): ?>
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong class="small"><i class="bi bi-chat-dots me-1"></i>Messages</strong>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleMessaging(<?= $claim['id'] ?>)">
                            <i class="bi bi-chat"></i> <?= isset($claimMessages[$claim['id']]) && !empty($claimMessages[$claim['id']]) ? 'View Chat' : 'Start Chat' ?>
                        </button>
                    </div>
                    <div id="messaging-<?= $claim['id'] ?>" class="messaging-container" style="display:none;">
                        <div class="message-thread">
                            <?php if (isset($claimMessages[$claim['id']]) && !empty($claimMessages[$claim['id']])): ?>
                                <?php foreach ($claimMessages[$claim['id']] as $msg): ?>
                                <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'message-sent' : 'message-received' ?>">
                                    <div class="message-header">
                                        <strong><?= e($msg['sender_name']) ?></strong>
                                        <span class="message-time"><?= timeAgo($msg['created_at']) ?></span>
                                    </div>
                                    <div class="message-content"><?= e($msg['message']) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted small py-3">
                                    <i class="bi bi-chat-dots display-4 mb-2"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <form class="message-form" onsubmit="sendMessage(event, <?= $claim['id'] ?>)">
                            <?= csrfField() ?>
                            <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Similar Items -->
    <?php if ($similar): ?>
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-grid me-2 text-primary"></i>Similar <?= ucfirst(e($post['type'])) ?> Items
            </h5>
            <div class="row g-2">
                <?php foreach ($similar as $sim): ?>
                <div class="col-sm-6 col-md-3">
                    <a href="item.php?id=<?= $sim['id'] ?>" class="similar-card d-flex text-decoration-none">
                        <?php if ($sim['image']): ?>
                            <img src="<?= UPLOAD_URL . e($sim['image']) ?>" class="similar-thumb" alt="">
                        <?php else: ?>
                            <div class="similar-thumb d-flex align-items-center justify-content-center"
                                 style="background:#F0F4F8;font-size:1.4rem;color:#CBD5E0">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                        <div style="min-width:0">
                            <div class="fw-semibold small text-truncate"><?= e($sim['title']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= e($sim['location']) ?></div>
                            <div class="text-muted" style="font-size:.7rem"><?= timeAgo($sim['created_at']) ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Feature 6: Messaging functionality
function toggleMessaging(claimId) {
    const container = document.getElementById('messaging-' + claimId);
    if (container.style.display === 'none') {
        container.style.display = 'block';
        // Scroll to bottom of messages
        const thread = container.querySelector('.message-thread');
        thread.scrollTop = thread.scrollHeight;
    } else {
        container.style.display = 'none';
    }
}

function sendMessage(event, claimId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('actions/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the new message
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error sending message: ' + error);
    });
}

// Sighting messaging functionality
function toggleSightingMessaging(sightingId) {
    const container = document.getElementById('sighting-messaging-' + sightingId);
    if (container.style.display === 'none') {
        container.style.display = 'block';
        // Scroll to bottom of messages
        const thread = container.querySelector('.message-thread');
        thread.scrollTop = thread.scrollHeight;
    } else {
        container.style.display = 'none';
    }
}

function sendSightingMessage(event, sightingId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('actions/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the new message
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error sending message: ' + error);
    });
}

// Sighting modal toggle function
function toggleCustodyOffice(select) {
    const custodyGroup = document.getElementById('custody-office-group');
    const custodySelect = custodyGroup.querySelector('select');
    
    if (select.value === 'custody') {
        custodyGroup.style.display = 'block';
        custodySelect.required = true;
    } else {
        custodyGroup.style.display = 'none';
        custodySelect.required = false;
        custodySelect.value = '';
    }
}

// Share notice link functionality
function shareNoticeLink() {
    const noticeUrl = window.location.href;
    const isLostItem = document.querySelector('.detail-type-badge.lost') !== null;
    const shareText = isLostItem ? 'Help find this lost item!' : 'Found item - check if it\'s yours!';
    
    // Try to use the Web Share API if available
    if (navigator.share) {
        navigator.share({
            title: 'CTU Lost & Found Item',
            text: shareText,
            url: noticeUrl
        }).catch((error) => {
            console.log('Error sharing:', error);
            // Fallback to clipboard copy
            copyToClipboard(noticeUrl);
        });
    } else {
        // Fallback to clipboard copy
        copyToClipboard(noticeUrl);
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Link copied to clipboard! Share it in CTU Facebook and Messenger class groups.');
    }).catch((error) => {
        console.error('Failed to copy: ', error);
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Link copied to clipboard! Share it in CTU Facebook and Messenger class groups.');
        } catch (err) {
            alert('Failed to copy link. Please copy the URL manually.');
        }
        document.body.removeChild(textArea);
    });
}
</script>

<!-- Claim Modal -->
<?php if ($loggedIn && !$isOwner && $post['type']==='found' && $post['status']==='active' && !$userClaim): ?>
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-hand-index me-2 text-primary"></i>Submit a Claim
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Describe the item to prove ownership. The poster will review your claim.
                </p>
                <?php if (!empty($post['verification_question'])): ?>
                <div class="alert alert-warning py-2 mb-3 small">
                    <i class="bi bi-shield-fill-check me-1"></i>
                    <strong>Verification Required:</strong> <?= e($post['verification_question']) ?>
                </div>
                <?php endif; ?>
                <form action="actions/submit_claim.php" method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="e.g. My bag has a white patch on the front pocket, inside has my name written…"
                                  required></textarea>
                    </div>
                    <?php if (!empty($post['verification_question'])): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Verification Answer <span class="text-danger">*</span></label>
                        <input type="text" name="verification_answer" class="form-control" 
                               placeholder="Answer the verification question..." required>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proof Image (Optional)</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*">
                        <div class="form-text small">Upload purchase receipt, old photo, or other proof of ownership</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Claim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sighting Modal -->
<?php if ($loggedIn && !$isOwner && $post['type']==='lost' && $post['status']==='active'): ?>
<div class="modal fade" id="sightingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-binoculars me-2 text-success"></i>Report Sighting
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Help the owner recover their item by reporting where you found or spotted it.
                </p>
                <form action="actions/submit_sighting.php" method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sighting Type <span class="text-danger">*</span></label>
                        <select name="sighting_type" class="form-select" required onchange="toggleCustodyOffice(this)">
                            <option value="">Select type...</option>
                            <option value="possession">🎒 I have it in my possession</option>
                            <option value="custody">🏢 I surrendered it to a campus office</option>
                            <option value="spotted">👁️ I spotted it at a location</option>
                        </select>
                    </div>
                    <div class="mb-3" id="custody-office-group" style="display:none;">
                        <label class="form-label fw-semibold">Campus Office <span class="text-danger">*</span></label>
                        <select name="custody_office" class="form-select">
                            <option value="">Select office...</option>
                            <option value="guardhouse_main">Main Campus Guardhouse</option>
                            <option value="sas_office">Student Affairs & Services (SAS)</option>
                            <option value="ssg_office">SSG Office</option>
                            <option value="library">Library Circulation Desk</option>
                            <option value="deans_office">Dean's Office</option>
                            <option value="other">Other Office</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location Details <span class="text-danger">*</span></label>
                        <textarea name="location_details" class="form-control" rows="3"
                                  placeholder="e.g. Tech Wing, Room 102, near the entrance"
                                  required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message to Owner <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="3"
                                  placeholder="e.g. Hi! I found your calculator and turned it over to the SAS Office. You can claim it there with your ID."
                                  required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proof Image (Optional)</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*">
                        <div class="form-text small">Upload a photo of the item or location</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-send me-1"></i>Submit Sighting
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>