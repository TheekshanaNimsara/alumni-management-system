<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Directory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <span class="logo-badge">AN</span>
            <span style="font-weight: bold; font-size: 1.2rem;">Alumni Network</span>
        </div>
    </nav>

    <div class="container">
        <h1 style="color: var(--primary-color); font-size: 2.5rem;">Alumni Directory</h1>
        <p style="color: #666; margin-bottom: 1.5rem;">0 member(s) found</p>

        <div class="card" style="margin-bottom: 3rem;">
            <form action="directory.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, company...">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <select class="form-control" name="year">
                        <option value="">All Graduation Years</option>
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                    </select>
                </div>
                <div style="flex: 2; display: flex; gap: 1rem; min-width: 300px;">
                     <input type="text" class="form-control" name="degree" placeholder="Filter by degree programme">
                     <button type="submit" class="btn-primary" style="width: auto;">Filter</button>
                </div>
            </form>
        </div>

        <div class="empty-state" style="text-align: center; color: #666; padding: 3rem 0;">
            <h3>No alumni match your search yet. Try a different filter.</h3>
        </div>
    </div>
</body>
</html>