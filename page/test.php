<div class="row g-4 mb-5">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
            <div class="card-body">
                <i class="bi bi-shield-lock-fill fs-1 mb-3"></i>
                <h5>Total Admins</h5>
                <h2 class="fw-bold"><?php echo $total_admins; ?></h2>
                <small class="opacity-90">Active administrative records</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="card-body">
                <i class="bi bi-people-fill fs-1 mb-3"></i>
                <h5>Registered Users</h5>
                <h2 class="fw-bold"><?php echo $total_users; ?></h2>
                <small class="opacity-90">Total active app members</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="card-body">
                <i class="bi bi-box-seam-fill fs-1 mb-3"></i>
                <h5>Total Products</h5>
                <h2 class="fw-bold"><?php echo $total_products; ?></h2>
                <small class="opacity-90">Unique items listed in catalog</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
            <div class="card-body">
                <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                <h5>Pending Orders</h5>
                <h2 class="fw-bold"><?php echo $pending_orders; ?></h2>
                <small class="opacity-90">Requires immediate fulfillment</small>
            </div>
        </div>
    </div>
</div>