<?php
$siteName = app_setting('website_name', 'AdminHub');
$pageScripts = $pageScripts ?? '';
?>
<footer>
    <div class="container-fluid px-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="fw-bold text-white mb-1"><?php echo e($siteName); ?></h5>
                <p class="small mb-0 text-white-50">Operational dashboard for products, inventory, orders, reports, and settings.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <small class="text-white-50">&copy; <?php echo date('Y'); ?> <?php echo e($siteName); ?>. All rights reserved.</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php echo $pageScripts; ?>
</body>
</html>
