<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <title>Document</title>
</head>
<body>
    <header class="main-navbar">
    <div class="nav-toggle">
        <i class="bi bi-list" id="menu-toggle"></i>
    </div>
    <div class="user-wrapper">
        <i class="bi bi-bell me-3 text-muted" style="cursor: pointer;"></i>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin User'); ?></span>
            <small class="user-role d-block text-muted">Administrator</small>
        </div>
    </div>
</header>
</body>
</html>