<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Crafty Fuzzy Studio</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500;9..144,600&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= filemtime(__DIR__ . '/../../assets/style.css') ?>">
</head>
<body class="admin-login-page">
  <main class="admin-login-shell">
    <section class="admin-login-panel">
      <h1>Admin login</h1>
      <form id="adminLoginForm" onsubmit="adminLogin(event)" autocomplete="off">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" autocomplete="username" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Sign in</button>
      </form>
    </section>
  </main>
  <script src="assets/script.js?v=<?= filemtime(__DIR__ . '/../../assets/script.js') ?>"></script>
</body>
</html>
