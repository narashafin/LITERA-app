<?php

//$active_page 
if (!isset($active_page)) {
    $active_page = '';
}

// Helper: return class 'active' kalau $page sesuai $active_page
function nav_active(string $page): string {
    global $active_page;
    return $active_page === $page ? ' active' : '';
}

// Cek apakah user adalah admin (fungsi is_admin() dari auth_helper.php)
$_sidebar_is_admin = function_exists('is_admin') ? is_admin() : false;
?>


<!-- TOMBOL HAMBURGER (mobile ver) -->

<button class="hamburger-btn" id="hamburgerBtn" aria-label="Buka menu">
    <!-- Icon ☰ -->
    <svg id="iconOpen" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"
         stroke-linecap="round" viewBox="0 0 24 24">
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
    <!-- Icon × (tutup) — awalnya hidden -->
    <svg id="iconClose" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5"
         stroke-linecap="round" viewBox="0 0 24 24" style="display:none">
        <line x1="18" y1="6"  x2="6"  y2="18"/>
        <line x1="6"  y1="6"  x2="18" y2="18"/>
    </svg>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="mainSidebar">

    <div class="sidebar-logo">
        <img src="/LITERA-app/assets/LITERA.png" alt="LITERA Logo">
        <span class="logo-text">LITERA</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group-label">Main</div>
        <a href="/LITERA-app/dashboard.php" class="nav-item<?= nav_active('dashboard') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3"  y="3"  width="7" height="7" rx="1"/>
                <rect x="14" y="3"  width="7" height="7" rx="1"/>
                <rect x="3"  y="14" width="7" height="7" rx="1"/>
                <rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            Dashboard
        </a>

        <div class="nav-group-label">Koleksi</div>
        <a href="/LITERA-app/modules/books/index.php" class="nav-item<?= nav_active('books') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
            </svg>
            Buku
        </a>
        <a href="/LITERA-app/modules/categories-racks/category/index.php" class="nav-item<?= nav_active('categories') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            Kategori
        </a>
        <a href="/LITERA-app/modules/categories-racks/rack/index.php" class="nav-item<?= nav_active('racks') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="2" y="3"  width="20" height="5" rx="1"/>
                <rect x="2" y="10" width="20" height="5" rx="1"/>
                <rect x="2" y="17" width="20" height="5" rx="1"/>
            </svg>
            Rak Buku
        </a>

        <div class="nav-group-label">Transaksi</div>
        <a href="/LITERA-app/modules/borrowings/index.php" class="nav-item<?= nav_active('borrowings') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            Peminjaman
        </a>
        <a href="/LITERA-app/modules/returns-fines/returns/index.php" class="nav-item<?= nav_active('returns') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 .49-3.5"/>
            </svg>
            Pengembalian
        </a>
        <a href="/LITERA-app/modules/returns-fines/fines/index.php" class="nav-item<?= nav_active('fines') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8"  x2="12"   y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Denda
        </a>

        <?php if ($_sidebar_is_admin): ?>
        <div class="nav-group-label">Manajemen</div>
        <a href="/LITERA-app/pages/users.php" class="nav-item<?= nav_active('users') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Pengguna
        </a>
        <a href="/LITERA-app/pages/reports.php" class="nav-item<?= nav_active('reports') ?>">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/>
                <line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6"  y1="20" x2="6"  y2="14"/>
            </svg>
            Laporan
        </a>
        <?php endif; ?>

    </nav>
    <div class="sidebar-footer">
        <a href="/LITERA-app/modules/users-auth/logout.php" class="btn-logout">
            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</aside>

<style>
:root {
    --sidebar-bg : #C9D8E8;
    --sidebar-w  : 240px;
    --blue-dark  : #2563EB;
    --navy       : #1E3A5F;
    --white      : #ffffff;
    --bg         : #EDF2F7;
    --muted      : #64748B;
}

.sidebar {
    width         : var(--sidebar-w);
    height        : 100vh;
    background    : var(--sidebar-bg);
    border-radius : 0 24px 24px 0;
    display       : flex;
    flex-direction: column;
    padding-bottom: 24px;
    position      : fixed;
    top: 0; left: 0;
    z-index       : 998;
    box-shadow    : 2px 0 16px rgba(30,58,95,.08);
    overflow-y    : auto;
    transition    : transform .3s cubic-bezier(.4,0,.2,1);
}

.sidebar-logo {
    display        : flex;
    flex-direction : column;
    align-items    : center;
    padding        : 28px 16px 20px;
    border-bottom  : 1px solid rgba(30,58,95,.12);
    flex-shrink    : 0;
}
.sidebar-logo img { width:90px; height:90px; object-fit:contain; }
.sidebar-logo .logo-text {
    font-size   : 1.25rem;
    font-weight : 800;
    letter-spacing: 4px;
    background  : linear-gradient(90deg,#4ecdc4,#45b7d1,#96c93d,#f7971e,#f9d62e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-top  : 4px;
}

.sidebar-nav { flex:1; padding:16px 0; overflow-y:auto; }

.nav-group-label {
    font-size      : .68rem;
    font-weight    : 800;
    color          : var(--navy);
    letter-spacing : 1.4px;
    text-transform : uppercase;
    padding        : 14px 24px 6px;
}

.nav-item {
    display        : flex;
    align-items    : center;
    gap            : 10px;
    padding        : 9px 24px 9px 32px;
    color          : #374151;
    text-decoration: none;
    font-size      : .875rem;
    font-weight    : 500;
    border-radius  : 0 20px 20px 0;
    margin-right   : 16px;
    transition     : all .2s;
    position       : relative;
}
.nav-item:hover  { background:rgba(37,99,235,.1); color:var(--blue-dark); font-weight:600; }
.nav-item.active { background:#fff; color:var(--blue-dark); font-weight:700; box-shadow:0 2px 8px rgba(37,99,235,.12); }
.nav-item.active::before {
    content       : '';
    position      : absolute;
    left:0; top:6px; bottom:6px;
    width         : 3px;
    background    : var(--blue-dark);
    border-radius : 0 3px 3px 0;
}

.sidebar-footer { padding:0 16px; margin-top:8px; flex-shrink:0; }
.btn-logout {
    display        : flex;
    align-items    : center;
    gap            : 8px;
    width          : 100%;
    padding        : 10px 16px;
    background     : rgba(239,68,68,.12);
    color          : #DC2626;
    border         : none;
    border-radius  : 12px;
    font-family    : 'Nunito', sans-serif;
    font-size      : .85rem;
    font-weight    : 700;
    cursor         : pointer;
    text-decoration: none;
    transition     : background .2s;
}
.btn-logout:hover { background:rgba(239,68,68,.22); }

.hamburger-btn {
    display        : none; /* hidden di desktop */
    position       : fixed;
    top: 12px; left: 12px;
    z-index        : 1000;
    background     : var(--blue-dark);
    color          : #fff;
    border         : none;
    padding        : 10px;
    border-radius  : 10px;
    cursor         : pointer;
    align-items    : center;
    justify-content: center;
    box-shadow     : 0 4px 12px rgba(37,99,235,.3);
    transition     : background .2s, transform .2s;
}
.hamburger-btn:hover { background:#1d4ed8; transform:scale(1.05); }

.sidebar-overlay {
    display       : none; /* hidden di desktop */
    position      : fixed;
    inset         : 0;
    background    : rgba(15,23,42,.5);
    backdrop-filter: blur(2px);
    z-index       : 997;
    opacity       : 0;
    transition    : opacity .3s ease;
    pointer-events: none;
}
.sidebar-overlay.active { opacity:1; pointer-events:auto; }

.main {
    margin-left   : var(--sidebar-w);
    flex          : 1;
    min-height    : 100vh;
    display       : flex;
    flex-direction: column;
}


@media (max-width: 1023px) {
    .hamburger-btn { display:flex; }

    .sidebar-overlay { display:block; }

    .sidebar {
        transform     : translateX(-100%);
        border-radius : 0 20px 20px 0;
        box-shadow    : 4px 0 24px rgba(0,0,0,.15);
    }

    .sidebar.sidebar-open { transform:translateX(0); }

    .main {
        margin-left : 0;
        padding-top : 56px; /* ruang untuk tombol hamburger */
    }

    /* Page header menyesuaikan */
    .page-header { padding:16px 20px 14px; }
    .content     { padding:20px; }
}
@media (max-width: 480px) {
    .content { padding:16px; }
}
</style>


<script>
(function () {
    var btn       = document.getElementById('hamburgerBtn');
    var sidebar   = document.getElementById('mainSidebar');
    var overlay   = document.getElementById('sidebarOverlay');
    var iconOpen  = document.getElementById('iconOpen');
    var iconClose = document.getElementById('iconClose');

    function openSidebar() {
        // Panggil 'sidebar-open' untuk sidebar, 'active' untuk overlay
        sidebar.classList.add('sidebar-open'); 
        overlay.classList.add('active');
        
        iconOpen.style.display  = 'none';
        iconClose.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        // Hapus 'sidebar-open' dari sidebar, 'active' dari overlay
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('active');
        
        iconOpen.style.display  = 'block';
        iconClose.style.display = 'none';
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', function () {
        // Cek apakah sidebar sedang 'sidebar-open'
        sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    var mq = window.matchMedia('(min-width: 1024px)');
    mq.addEventListener('change', function (e) {
        if (e.matches) closeSidebar();
    });
})();
</script>