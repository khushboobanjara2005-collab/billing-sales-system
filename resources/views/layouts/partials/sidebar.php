<?php
use App\Core\View;

$currentRoute = (string) ($_GET['r'] ?? '');
$navigation = [
    'Dashboard' => 'dashboard',
    'Business profile' => 'businesses/edit',
    'Add business' => 'businesses/create',
    'Companies' => 'companies/index',
    'Invoices' => 'invoices/index',
    'Payments' => 'payments/index',
    'Reports' => 'reports',
];
?>
<nav class="sidebar" id="sidebar" aria-label="Main navigation">
    <p class="nav-caption">WORKSPACE</p>
    <?php foreach ($navigation as $label => $route): ?>
        <a class="nav-link <?= $currentRoute === $route ? 'active' : '' ?>"
           href="<?= View::escape($baseUrl) ?>/index.php?r=<?= View::escape($route) ?>"
           <?= $currentRoute === $route ? 'aria-current="page"' : '' ?>>
            <?= View::escape($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
