<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Map page filenames to display names
$page_titles = [
    'dashboard.php' => 'Dashboard',
    'orders.php' => 'Orders',
    'invoice.php' => 'Invoice',
    'customers.php' => 'Customers',
    'products.php' => 'Products',
    'inventory.php' => 'Inventory',
    'trucks.php' => 'Trucks',
    'agents.php' => 'Agents',
    'stores.php' => 'Stores',
    'users.php' => 'Users',
    'sales.php' => 'Sales' 
];

// Default title if page is not found
$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Page';
?>

<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
    <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="/thenuka-stores/pages/sales.php">Home</a></li>
                <li class="breadcrumb-item text-sm text-dark active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
            </ol>
            <h6 class="font-weight-bolder mb-0"><?php echo htmlspecialchars($page_title); ?></h6>
        </nav>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
            <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                <div class="input-group">
                    <span class="input-group-text text-body"><i class="fas fa-search" aria-hidden="true"></i></span>
                    <input type="text" class="form-control" placeholder="Search...">
                </div>
            </div>
            <ul class="navbar-nav justify-content-end">
                <li class="nav-item d-flex align-items-center">
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                        <a href="/thenuka-stores/pages/logout.php" class="nav-link text-body font-weight-bold px-0">
                            <i class="fa fa-sign-out-alt me-sm-1"></i>
                            <span class="d-sm-inline d-none">Logout (<?php echo htmlspecialchars($_SESSION['user']['email']); ?>)</span>
                        </a>
                    <?php else: ?>
                        <a href="/thenuka-stores/pages/login.php" class="nav-link text-body font-weight-bold px-0">
                            <i class="fa fa-user me-sm-1"></i>
                            <span class="d-sm-inline d-none">Sign In</span>
                        </a>
                    <?php endif; ?>
                </li>
                <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </a>
                </li>
                <li class="nav-item px-3 d-flex align-items-center">
                    <a href="/thenuka-stores/pages/settings.php" class="nav-link text-body p-0">
                        <i class="fa fa-cog fixed-plugin-button-nav cursor-pointer"></i>
                    </a>
                </li>
                <li class="nav-item dropdown pe-2 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-bell cursor-pointer"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                        <li class="mb-2">
                            <a class="dropdown-item border-radius-md" href="javascript:;">
                                <div class="d-flex py-1">
                                    <div class="my-auto">
                                        <img src="/thenuka-stores/assets/img/team-2.jpg" class="avatar avatar-sm me-3">
                                    </div>
                                    <div class="d-flex flex-column justify-content-center">
                                        <h6 class="text-sm font-weight-normal mb-1">
                                            <span class="font-weight-bold">New sale</span> recorded
                                        </h6>
                                        <p class="text-xs text-secondary mb-0">
                                            <i class="fa fa-clock me-1"></i>
                                            10 minutes ago
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->