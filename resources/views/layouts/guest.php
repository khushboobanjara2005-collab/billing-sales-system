<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::escape($title ?? 'Account') ?> | BillingDesk</title>
    <link rel="stylesheet" href="<?= View::escape($baseUrl) ?>/assets/css/style.css">
</head>
<body class="guest-body">
<header class="guest-brand"><a class="brand" href="<?= View::escape($baseUrl) ?>/index.php?r=auth/login">Billing<span>Desk</span></a></header>
<main class="guest-main">
    <?php if (!empty($message)): ?>
        <div class="flash <?= View::escape($messageType ?? 'success') ?>" role="alert"><?= View::escape($message) ?></div>
    <?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
