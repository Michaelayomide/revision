<?php
$siteName = app_setting('website_name', 'AdminHub');
$pageScripts = $pageScripts ?? '';
?>
<footer>
    <div class="container-fluid px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="small">
                <span class="footer-brand"><?php echo e($siteName); ?></span>
                <span class="text-muted"> · Operations dashboard for products, inventory, orders, and settings.</span>
            </div>
            <div class="small text-muted">&copy; <?php echo date('Y'); ?> <?php echo e($siteName); ?>. All rights reserved.</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php echo $pageScripts; ?>
</body>
</html>
