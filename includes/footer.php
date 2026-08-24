
</main><!-- end main-content -->

<footer class="site-footer mt-auto py-5" style="background: #091B3A; color: #A0AEC0;">
    <div class="container-xl">
        <div class="row g-4">
            <!-- Brand & Campus Description -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #FFAB00; border-radius: 10px; color: #0F2D5C; font-weight: bold;">
                        <i class="bi bi-search-heart" style="font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <h6 class="text-white mb-0 fw-bold">CTU Danao Campus</h6>
                        <small style="color: #FFD166;">Lost &amp; Found System</small>
                    </div>
                </div>
                <p class="small mb-3" style="line-height: 1.7;">
                    A centralized university platform assisting Cebu Technological University — Danao Campus students, faculty, and personnel in reporting, searching, and safely reclaiming lost possessions.
                </p>
                <div class="d-flex gap-2">
                    <span class="badge rounded-pill bg-primary bg-opacity-25 text-light border border-primary border-opacity-25 px-3 py-2">
                        <i class="bi bi-geo-alt me-1"></i> Sabang, Danao City, Cebu
                    </span>
                </div>
            </div>

            <!-- Quick Navigation Links -->
            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">Navigation</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="index.php" class="text-decoration-none" style="color: #CBD5E0;"><i class="bi bi-chevron-right me-1 small"></i> Home Feed</a></li>
                    <li><a href="my-posts.php" class="text-decoration-none" style="color: #CBD5E0;"><i class="bi bi-chevron-right me-1 small"></i> My Posts</a></li>
                    <li><a href="claims.php" class="text-decoration-none" style="color: #CBD5E0;"><i class="bi bi-chevron-right me-1 small"></i> Claims Center</a></li>
                    <li><a href="profile.php" class="text-decoration-none" style="color: #CBD5E0;"><i class="bi bi-chevron-right me-1 small"></i> User Profile</a></li>
                </ul>
            </div>

            <!-- Campus Resources / Key Offices -->
            <div class="col-lg-3 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">Campus Resources</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0" style="color: #CBD5E0;">
                    <li><i class="bi bi-building me-2 text-warning"></i>Student Affairs Office (SAO)</li>
                    <li><i class="bi bi-shield-check me-2 text-warning"></i>Campus Security Guardpost</li>
                    <li><i class="bi bi-book me-2 text-warning"></i>University Library Helpdesk</li>
                    <li><i class="bi bi-laptop me-2 text-warning"></i>IT Services Department</li>
                </ul>
            </div>

            <!-- Info / Support -->
            <div class="col-lg-3 col-md-12">
                <h6 class="text-white fw-bold mb-3">CTU Danao Lost & Found</h6>
                <p class="small" style="color: #A0AEC0;">
                    Report lost items immediately or turn in found valuables to campus security to help owners recover their belongings quickly.
                </p>
            </div>
        </div>

        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);" />

        <!-- Bottom Copyright Row -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
            <div>
                &copy; <?= date('Y') ?> Cebu Technological University — Danao Campus. All rights reserved.
            </div>
            <div class="text-muted">
                Lost &amp; Found System
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="<?= BASE_URL ?>assets/vendor/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
