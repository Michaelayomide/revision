<?php
$pageTitle = $pageTitle ?? app_setting('website_name', 'AdminHub');
$bodyClass = trim(($bodyClass ?? '') . ' ' . (app_setting('site_theme') === 'dark' ? 'dark-theme' : ''));
$themeAttribute = app_setting('site_theme') === 'dark' ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e(app_setting('website_name', 'AdminHub')); ?> — enterprise operations dashboard.">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0f172a">
    <title><?php echo e($pageTitle . ' - ' . app_setting('website_name', 'AdminHub')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?php echo e($bodyClass); ?>" data-theme="<?php echo e($themeAttribute); ?>">
