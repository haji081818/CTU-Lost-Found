<?php
/**
 * Messaging Demo Page
 * This page demonstrates the Secure In-App Messaging functionality
 */

$pageTitle = 'Messaging Demo — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

// Check if messaging tables exist
$messagesTableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
$messagingAvailable = $messagesTableCheck && $messagesTableCheck->num_rows > 0;
?>

<div class="container-xl py-4">
    <h1 class="section-heading mb-4">
        <i class="bi bi-chat-dots me-2"></i>Secure In-App Messaging
    </h1>

    <?php if ($messagingAvailable): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Messaging System Available!</strong> The messaging tables are installed and ready to use.
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>How Messaging Works</h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-0">
                            <li class="mb-2">User A posts a <strong>found item</strong></li>
                            <li class="mb-2">User B submits a <strong>claim</strong> on the item</li>
                            <li class="mb-2">User A <strong>approves</strong> the claim</li>
                            <li class="mb-2">A private <strong>message thread</strong> is automatically created between User A and User B</li>
                            <li class="mb-2">Both parties can <strong>send messages</strong> to coordinate item handover</li>
                            <li>Messaging is <strong>private</strong> - only the claimant and poster can see the messages</li>
                        </ol>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people me-2"></i>Where to Find Messaging</h5>
                    </div>
                    <div class="card-body">
                        <p>The messaging interface appears on item detail pages when:</p>
                        <ul>
                            <li>You are the <strong>post owner</strong> and someone has claimed your item</li>
                            <li>You are the <strong>claimant</strong> and your claim has been approved or is pending</li>
                            <li>The claim status is <strong>"approved"</strong> or <strong>"pending"</strong></li>
                        </ul>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Look for the "Messages" section</strong> under the claims list on any item page where you're involved in a claim.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Demo</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">To test the messaging system:</p>
                        <ol class="small">
                            <li class="mb-2">Post a found item</li>
                            <li class="mb-2">Use a different account to claim it</li>
                            <li class="mb-2">Approve the claim as the owner</li>
                            <li class="mb-2">Visit the item page</li>
                            <li>You'll see the messaging interface!</li>
                        </ol>
                        
                        <a href="<?= BASE_URL ?>" class="btn btn-primary btn-sm mt-3">
                            <i class="bi bi-house me-1"></i>Go to Feed
                        </a>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Security Features</h5>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>✅ Private 1-on-1 messaging</li>
                            <li>✅ Only claimant and poster can participate</li>
                            <li>✅ Read/unread status tracking</li>
                            <li>✅ CSRF protection on all messages</li>
                            <li>✅ Admin can moderate if needed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Messaging System Not Available</strong>
        </div>

        <div class="card">
            <div class="card-body">
                <p>The messaging feature requires the <code>messages</code> table to be created in your database.</p>
                
                <h6 class="mt-3">To enable messaging:</h6>
                <ol>
                    <li>Run the updated <code>database.sql</code> file</li>
                    <li>The script will automatically create the <code>messages</code> table</li>
                    <li>Messaging will become available automatically</li>
                </ol>

                <div class="alert alert-info mt-3">
                    <strong>SQL Command:</strong><br>
                    <code>CREATE TABLE IF NOT EXISTS messages (...)</code>
                </div>

                <a href="<?= BASE_URL ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-house me-1"></i>Return to Feed
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
