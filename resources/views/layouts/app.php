<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($title ?? 'Billing Sales System') ?> | Billing Sales</title>
    <link rel="stylesheet" href="<?= View::escape($baseUrl) ?>/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>
<div class="shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main id="main-content" class="main-content">
        <?php if (!empty($message)): ?>
            <div class="flash <?= View::escape($messageType ?? 'success') ?>" role="status"><?= View::escape($message) ?></div>
        <?php endif; ?>
        <?= $content ?>
    </main>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
<script src="<?= View::escape($baseUrl) ?>/assets/js/app.js" defer></script>
</body>
</html>
