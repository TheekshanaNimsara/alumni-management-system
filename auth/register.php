<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Alumni Network</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <span class="logo-badge">AN</span>
            <span style="font-weight: bold; font-size: 1.2rem;">Alumni Network</span>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h1 style="text-align: center; color: var(--primary-color);">Create Your Alumni Account</h1>
            <p style="text-align: center; color: #666; margin-bottom: 2rem;">Join fellow graduates — reconnect, discover opportunities, and give back.</p>
            
            <form action="register_action.php" method="POST">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" class="form-control" name="firstname" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" class="form-control" name="lastname" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label>Graduation Year</label>
                    <input type="number" class="form-control" placeholder="e.g., 2021" name="grad_year">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" placeholder="Minimum 8 characters." name="password" required>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>
            <p style="text-align: center; margin-top: 1rem;">Already have an account? <a href="login.php" style="color: var(--primary-color); font-weight: bold;">Log in</a></p>
        </div>
    </div>
</body>
</html>