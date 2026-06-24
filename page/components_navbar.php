<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fallback: If the main page didn't define $admin_display_name, pull it from the session
if (!isset($admin_display_name)) {
    $admin_display_name = $_SESSION['admin_name'] ?? 'Admin User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>AdminHub - Navbar</title>
</head>
<body>
<header class="main-navbar">
   <nav class="navbar navbar-dark fixed-top py-3 shadow-sm" style="background-color: #161e2e;">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="navbar-toggler me-3 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand fw-bold fs-3" href="index.php">AdminHub</a>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <input class="form-control rounded-pill" type="search" placeholder="Search anything..." style="width: 260px;">
                <div class="d-flex gap-3 text-white fs-5">
                    <i class="bi bi-bell"></i>
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="d-flex align-items-center gap-2 text-white">
                    <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Avatar">
                    <div class="d-none d-sm-block">
                        <small class="fw-bold"><?php echo htmlspecialchars($admin_display_name); ?></small><br>
                        <small class="text-success">● Online</small>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
</body>
</html>