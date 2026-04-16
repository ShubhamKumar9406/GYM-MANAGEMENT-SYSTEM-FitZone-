<nav class="dashboard-nav">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <button onclick="toggleSidebar()" id="hamburgerBtn" class="hamburger-menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="dashboard.php" style="color: white; text-decoration: none; font-size: 1.5rem; font-weight: bold;">
            <span class="nav-full-text">💪 FitZone Admin</span>
            <span class="nav-short-text" style="display: none;">💪 Admin</span>
        </a>
    </div>
    <div class="nav-right-section">
        <span class="user-name-text" style="color: white;">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="../index.php" class="btn btn-sm btn-secondary nav-view-site">View Site</a>
        <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
</nav>