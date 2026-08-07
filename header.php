<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isUserLoggedIn = !empty($_SESSION['user_id']);
$isAdminLoggedIn = !empty($_SESSION['is_admin']);
?>
<!-- Ícone de engrenagem na aba do navegador para todas as páginas -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 512 512%22><path fill=%22%232b82b3%22 d=%22M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.3-10.3 16.1-16.8 23.2-5.6 6.1-12.3 11.2-19.7 15.2l-56.9-19c-16.6 13.1-35.6 23.6-56.3 31.1l-11.8 59.2c-2.3 11.5-10.9 20.6-22.3 23-13.6 2.8-27.5 4.3-41.6 4.3s-28-1.5-41.6-4.3c-11.4-2.4-20-11.5-22.3-23l-11.8-59.2c-20.7-7.5-39.7-18.1-56.3-31.1l-56.9 19c-7.4-4-14.1-9.1-19.7-15.2-6.5-7.1-12.1-14.9-16.8-23.2-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.3 10.3-16.1 16.8-23.2 5.6-6.1 12.3-11.2 19.7-15.2l56.9 19c16.6-13.1 35.6-23.6 56.3-31.1l11.8-59.2c2.3-11.5 10.9-20.6 22.3-23C228.4 1.5 242.3 0 256 0s28 1.5 41.6 4.3c11.4 2.4 20 11.5 22.3 23l11.8 59.2c20.7 7.5 39.7 18.1 56.3 31.1l56.9-19c7.4 4 14.1 9.1 19.7 15.2 6.5 7.1 12.1 14.9 16.8 23.2 6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z%22/></svg>">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="inicio.php">Help Desk <span></span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="inicio.php"><i class="fas fa-home"></i> Início</a>
                </li>
                <?php if ($isAdminLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?tab=overview"><i class="fas fa-chart-pie"></i> Visão Geral</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?tab=tickets"><i class="fas fa-tasks"></i> Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php?tab=users"><i class="fas fa-users"></i> Usuários</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair Admin</a>
                    </li>
                <?php else: ?>
                    <?php if (empty($hideAdminInHeader)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php"><i class="fas fa-tachometer-alt"></i> Admin</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($isUserLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="fas fa-plus-circle"></i> Abrir/Meus Tickets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login_fun.php"><i class="fas fa-sign-in-alt"></i> Área do Cliente</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
