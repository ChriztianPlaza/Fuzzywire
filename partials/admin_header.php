<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crafty Fuzzy Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <a href="?page=admin" class="admin-brand">Crafty Fuzzy <span style="font-style:italic;color:var(--terracotta-soft);font-size:0.9rem">studio</span></a>
    <nav class="admin-nav">
      <a href="?page=admin&section=dashboard" class="<?= $section==='dashboard'?'active':'' ?>">Dashboard</a>
      <a href="?page=admin&section=inventory" class="<?= $section==='inventory'?'active':'' ?>">Flower Inventory</a>
      <a href="?page=admin&section=customization" class="<?= $section==='customization'?'active':'' ?>">Builder Controls</a>
      <a href="?page=admin&section=wrappers" class="<?= $section==='wrappers'?'active':'' ?>">Wrappers</a>
      <a href="?page=admin&section=ribbons" class="<?= $section==='ribbons'?'active':'' ?>">Ribbons</a>
      <a href="?page=admin&section=presets" class="<?= $section==='presets'?'active':'' ?>">Bouquet Presets</a>
      <a href="?page=admin&section=orders" class="<?= $section==='orders'?'active':'' ?>">Orders</a>
      <a href="?page=admin&section=sales" class="<?= $section==='sales'?'active':'' ?>">Sales Report</a>
    </nav>
    <button type="button" class="admin-back admin-sidebar-btn" onclick="openAdminPasswordModal()">Change password</button>
    <button type="button" class="admin-back admin-sidebar-btn" onclick="adminLogout()">Log out</button>
    <a href="?page=home" class="admin-back">Back to shop</a>
  </aside>
  <main class="admin-main">

