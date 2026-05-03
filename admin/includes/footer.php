    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alert.alert-success,.alert.alert-info').forEach(el => {
        el.style.transition = 'opacity .4s,max-height .4s,margin .4s,padding .4s';
        el.style.overflow = 'hidden';
        el.style.maxHeight = el.offsetHeight + 'px';
        requestAnimationFrame(() => {
            el.style.opacity = '0'; el.style.maxHeight = '0';
            el.style.marginBottom = '0'; el.style.paddingTop = '0'; el.style.paddingBottom = '0';
        });
        setTimeout(() => el.remove(), 450);
    });
}, 4000);
</script>
</body>
</html>
