<?php
// includes/navbar.php
$user = current_user();
$cur  = basename($_SERVER['PHP_SELF']);
?>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<nav class="navbar">
  <div class="nav-brand">
    <span class="nav-logo">📚</span>
    <span class="nav-title">Litera</span>
  </div>
  <div class="nav-links">
    <a href="../pages/dashboard.php" class="<?= $cur==='dashboard.php'?'active':'' ?>">🏠 Dashboard</a>
    <?php if (is_admin()): ?>
    <a href="../pages/users.php"     class="<?= $cur==='users.php'    ?'active':'' ?>">👥 Kelola User</a>
    <?php endif; ?>
  </div>
  <div class="nav-user">
    <div class="nav-info">
      <span class="nav-name"><?= htmlspecialchars($user['nama']) ?></span>
      <span class="nav-badge <?= $user['role_id']==1?'admin':'member' ?>">
        <?= htmlspecialchars($user['nama_role']) ?>
      </span>
    </div>
    <a href="../auth/logout.php" class="btn-logout">Logout</a>
  </div>
</nav>
<style>
.navbar{display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(90deg,#0B2D6E,#1A4DB5);
  padding:0 28px;height:62px;position:sticky;top:0;z-index:100;
  box-shadow:0 2px 20px rgba(11,45,110,.3)}
.nav-brand{display:flex;align-items:center;gap:10px}
.nav-logo{font-size:22px}
.nav-title{font-family:'Playfair Display',serif;color:#fff;font-size:1.3rem}
.nav-links{display:flex;gap:4px}
.nav-links a{color:rgba(255,255,255,.72);text-decoration:none;padding:8px 16px;
  border-radius:8px;font-size:.86rem;font-weight:500;transition:all .2s}
.nav-links a:hover,.nav-links a.active{background:rgba(255,255,255,.16);color:#fff}
.nav-user{display:flex;align-items:center;gap:12px}
.nav-info{display:flex;flex-direction:column;align-items:flex-end;gap:2px}
.nav-name{color:#fff;font-size:.85rem;font-weight:500}
.nav-badge{padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.nav-badge.admin{background:#FCD34D;color:#78350F}
.nav-badge.member{background:rgba(255,255,255,.2);color:#fff}
.btn-logout{background:rgba(255,255,255,.14);color:#fff;text-decoration:none;
  padding:7px 16px;border-radius:8px;font-size:.8rem;font-weight:600;
  border:1px solid rgba(255,255,255,.28);transition:all .2s}
.btn-logout:hover{background:rgba(255,255,255,.26)}
</style>