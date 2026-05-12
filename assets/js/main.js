
// ── Flash Alert Box ───────────────────────────────
const toast = document.getElementById('flashToast');
if (toast) {
    // Auto-dismiss toast after 4 seconds instead of blocking with alert()
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease-out';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);-
    }, 4000);  
}

// ── Live Search ───────────────────────────────
const searchInput = document.getElementById('liveSearch');
const cardGrid    = document.getElementById('cardGrid');
const noResults   = document.getElementById('noResults');

if (searchInput && cardGrid) {
    let debounceTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filterCards, 220);
    });

    function filterCards() {
        const q     = searchInput.value.trim().toLowerCase();
        const cards = cardGrid.querySelectorAll('.card-item');
        let   visible = 0;

        cards.forEach(card => {
            const title = card.dataset.title  || '';
            const desc  = card.dataset.desc   || '';
            const loc   = card.dataset.loc    || '';
            const match = !q || title.includes(q) || desc.includes(q) || loc.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        if (noResults) {
            noResults.style.display = visible === 0 ? 'flex' : 'none';
        }
    }
}

// ── Image Preview in Post Modal ───────────────
const imageInput       = document.getElementById('imageInput');
const imagePreview     = document.getElementById('imagePreview');
const imagePreviewWrap = document.getElementById('imagePreviewWrap');

if (imageInput) {
    imageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => {
                imagePreview.src = e.target.result;
                imagePreviewWrap.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreviewWrap.style.display = 'none';
        }
    });
}

// ── Smooth scroll for hero CTA ────────────────
document.querySelectorAll('a[href="#feed"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.getElementById('feed');
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});