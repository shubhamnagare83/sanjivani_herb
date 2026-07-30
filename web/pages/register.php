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
  <title>Register | Sanjivani Herb Mapper</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">
  <div class="glass-card" style="width: 100%; max-width: 450px; padding: 2.5rem;">
    <div style="text-align: center; margin-bottom: 2rem;">
      <div style="font-size: 3rem; margin-bottom: 0.5rem;">🌱</div>
      <h2>Join Network</h2>
      <p style="color: var(--text-secondary); font-size: 0.9rem;">Register as a campus biodiversity contributor</p>
    </div>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.9rem;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form action="../api/auth/register.php" method="POST">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Shubham Nagare" required>
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="student@sanjivani.edu" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required minlength="6">
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"><i class="fa-solid fa-user-plus"></i> Create Account</button>
    </form>

    <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
      Already have an account? <a href="login.php">Sign in</a>
    </div>
  </div>
</body>
</html>
