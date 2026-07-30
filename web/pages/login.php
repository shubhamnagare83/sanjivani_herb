<?php
require_once __DIR__ . '/../includes/auth.php';
$user = getCurrentUser();
if ($user) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Sanjivani Herb Mapper</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
  <div class="glass-card" style="width: 100%; max-width: 420px; padding: 2.5rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">🌿</div>
      <h2>Sanjivani Herb</h2>
      <p style="color: var(--text-secondary); font-size: 0.9rem;">Sign in to contribute campus observations</p>
    </div>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.9rem;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="../api/auth/login.php" method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="student@sanjivani.edu" required value="admin@sanjivani.edu">
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required value="password123">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fa-solid fa-right-to-bracket"></i> Sign In</button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-muted);">
      Demo Credentials:<br>
      <strong>Admin:</strong> admin@sanjivani.edu / password123<br>
      <strong>Student:</strong> student@sanjivani.edu / password123
    </div>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>
