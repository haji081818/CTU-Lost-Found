// ── Top Progress Bar Controller ──────────────────
const TopBar = {
    el: null,
    init() {
        if (!document.getElementById('topProgressBar')) {
            const bar = document.createElement('div');
            bar.id = 'topProgressBar';
            document.body.prepend(bar);
        }
        this.el = document.getElementById('topProgressBar');
    },
    start() {
        if (!this.el) this.init();
        this.el.classList.add('loading');
        this.el.style.width = '35%';
        setTimeout(() => {
            if (this.el && this.el.style.width === '35%') {
                this.el.style.width = '75%';
            }
        }, 200);
    },
    finish() {
        if (!this.el) return;
        this.el.style.width = '100%';
        setTimeout(() => {
            this.el.classList.remove('loading');
            this.el.style.width = '0%';
        }, 300);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    TopBar.init();
});

// ── Flash Alert Box ───────────────────────────────
const toast = document.getElementById('flashToast');
if (toast) {
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease-out';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);  
}

// ── Universal Button Loading States & Form Submission Lock ───────
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form || form.tagName !== 'FORM') return;

    // Check HTML5 validity
    if (form.checkValidity && !form.checkValidity()) {
        return; // Native browser validation handles invalid fields
    }

    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submitBtn) return;

    // Prevent duplicate submission clicks
    if (submitBtn.dataset.submitting === 'true') {
        e.preventDefault();
        return;
    }
    submitBtn.dataset.submitting = 'true';

    // Start top progress bar
    TopBar.start();

    // Determine custom or automatic loading text
    let loadingText = submitBtn.getAttribute('data-loading-text');
    const originalContent = submitBtn.innerHTML;
    const btnTextLower = submitBtn.textContent.trim().toLowerCase();

    if (!loadingText) {
        if (btnTextLower.includes('publish')) {
            loadingText = 'Publishing Notice...';
        } else if (btnTextLower.includes('claim')) {
            loadingText = 'Submitting Claim...';
        } else if (btnTextLower.includes('report') || btnTextLower.includes('send report')) {
            loadingText = 'Sending Report...';
        } else if (btnTextLower.includes('sign in') || btnTextLower.includes('login')) {
            loadingText = 'Signing In...';
        } else if (btnTextLower.includes('register') || btnTextLower.includes('create account')) {
            loadingText = 'Creating Account...';
        } else if (btnTextLower.includes('save') || btnTextLower.includes('profile')) {
            loadingText = 'Saving Changes...';
        } else if (btnTextLower.includes('approve')) {
            loadingText = 'Approving...';
        } else if (btnTextLower.includes('reject')) {
            loadingText = 'Rejecting...';
        } else if (btnTextLower.includes('returned')) {
            loadingText = 'Updating Status...';
        } else if (btnTextLower.includes('delete')) {
            loadingText = 'Deleting...';
        } else if (btnTextLower.includes('send') || submitBtn.querySelector('.bi-send')) {
            loadingText = 'Sending...';
        } else {
            loadingText = 'Processing...';
        }
    }

    // Set fixed width so button doesn't shrink or jump
    const currentWidth = submitBtn.offsetWidth;
    if (currentWidth > 0) {
        submitBtn.style.minWidth = `${currentWidth}px`;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>${loadingText}</span>`;

    // In case form submission is aborted or handled via ajax/back
    setTimeout(() => {
        if (submitBtn.dataset.submitting === 'true' && !document.hidden) {
            submitBtn.disabled = false;
            submitBtn.dataset.submitting = 'false';
            submitBtn.innerHTML = originalContent;
            TopBar.finish();
        }
    }, 15000);
});

// ── Image Preview Loader Component ────────────────
function formatBytes(bytes, decimals = 1) {
    if (!+bytes) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

function setupImagePreviewLoader(inputElement, wrapperElement) {
    if (!inputElement || !wrapperElement) return;

    inputElement.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) {
            wrapperElement.innerHTML = '';
            wrapperElement.style.display = 'none';
            return;
        }

        if (!file.type.startsWith('image/')) {
            wrapperElement.innerHTML = `<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>Please select a valid image file.</div>`;
            wrapperElement.style.display = 'block';
            return;
        }

        // Show inline loader while image file is decoded
        wrapperElement.style.display = 'block';
        wrapperElement.innerHTML = `
            <div class="image-loading-indicator">
                <span class="spinner-border spinner-border-sm text-primary"></span>
                <span>Loading image preview (${formatBytes(file.size)})...</span>
            </div>
        `;

        const reader = new FileReader();
        reader.onload = function (e) {
            wrapperElement.innerHTML = `
                <div class="image-preview-card">
                    <img src="${e.target.result}" alt="Preview" class="image-preview-thumb">
                    <div class="image-preview-meta">
                        <div class="image-preview-name">${file.name}</div>
                        <div class="image-preview-size"><i class="bi bi-file-earmark-image me-1"></i>${formatBytes(file.size)} • Ready to upload</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger p-1 px-2 rounded-2" title="Remove Photo" id="removeImgBtn-${inputElement.name || 'file'}">
                        <i class="bi bi-trash3-fill"></i>
                    </button>
                </div>
            `;

            const removeBtn = wrapperElement.querySelector('button');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    inputElement.value = '';
                    wrapperElement.innerHTML = '';
                    wrapperElement.style.display = 'none';
                });
            }
        };

        reader.onerror = function () {
            wrapperElement.innerHTML = `<div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i>Failed to read image file.</div>`;
        };

        reader.readAsDataURL(file);
    });
}

// Attach image loaders on page load
document.addEventListener('DOMContentLoaded', () => {
    // 1. Post modal
    const postImageInput = document.getElementById('imageInput');
    const postPreviewWrap = document.getElementById('imagePreviewWrap');
    if (postImageInput && postPreviewWrap) {
        setupImagePreviewLoader(postImageInput, postPreviewWrap);
    }

    // 2. Generic proof image inputs (Claims & Sightings)
    document.querySelectorAll('input[type="file"][name="proof_image"]').forEach(input => {
        let wrap = input.parentElement.querySelector('.proof-image-preview');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'proof-image-preview mt-2';
            wrap.style.display = 'none';
            input.parentElement.appendChild(wrap);
        }
        setupImagePreviewLoader(input, wrap);
    });
});

// ── Live Search with Spinner Feedback ───────────────
const searchInput = document.getElementById('liveSearch');
const searchSpinner = document.getElementById('searchSpinner');
const cardGrid    = document.getElementById('cardGrid');
const noResults   = document.getElementById('noResults');
const resultsCount = document.getElementById('resultsCount');

if (searchInput && cardGrid) {
    let debounceTimer;

    searchInput.addEventListener('input', function () {
        if (searchSpinner) searchSpinner.style.display = 'inline-block';
        cardGrid.style.opacity = '0.6';
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            filterCards();
            cardGrid.style.opacity = '1';
            if (searchSpinner) searchSpinner.style.display = 'none';
        }, 180);
    });

    function filterCards() {
        const q     = searchInput.value.trim().toLowerCase();
        const cards = cardGrid.querySelectorAll('.card-item');
        let   visible = 0;

        cards.forEach(card => {
            const title = (card.dataset.title || '').toLowerCase();
            const desc  = (card.dataset.desc || '').toLowerCase();
            const loc   = (card.dataset.loc || '').toLowerCase();
            const match = !q || title.includes(q) || desc.includes(q) || loc.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (resultsCount) {
            resultsCount.textContent = visible;
        }

        if (noResults) {
            noResults.style.display = visible === 0 ? 'flex' : 'none';
        }
    }
}

// ── Universal Feed Auto-Scroll ─────────────────
function scrollToFeed() {
    const feedElement = document.getElementById('feed');
    if (feedElement) {
        feedElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

const browseItemsBtn = document.getElementById('browseItemsBtn');
if (browseItemsBtn) {
    browseItemsBtn.addEventListener('click', function (e) {
        e.preventDefault();
        scrollToFeed();
    });
}

const feedControls = document.querySelectorAll(
    '.fpill, .fpill-lost, .fpill-found, .sb-cat-item, .dropdown-item[href*="type="], .dropdown-item[href*="cat="]'
);

feedControls.forEach(control => {
    control.addEventListener('click', function () {
        sessionStorage.setItem('scrollToFeed', 'true');
    });
});

window.addEventListener('load', function () {
    if (sessionStorage.getItem('scrollToFeed') === 'true') {
        setTimeout(scrollToFeed, 300);
        sessionStorage.removeItem('scrollToFeed');
    }
});

// ── Anti-Fraud & Custody Section Toggles in Post Modal ──
const typeLost = document.getElementById('typeLost');
const typeFound = document.getElementById('typeFound');
const verificationSection = document.getElementById('verificationSection');
const custodySection = document.getElementById('custodySection');

if (typeLost && typeFound && verificationSection && custodySection) {
    function toggleFoundItemSections() {
        if (typeFound.checked) {
            verificationSection.style.display = 'block';
            custodySection.style.display = 'block';
        } else {
            verificationSection.style.display = 'none';
            custodySection.style.display = 'none';
        }
    }
    
    typeLost.addEventListener('change', toggleFoundItemSections);
    typeFound.addEventListener('change', toggleFoundItemSections);
    
    toggleFoundItemSections();
}
