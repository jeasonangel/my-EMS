<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Search logic
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM venues";

if (!empty($search_term)) {
    $search_term = $conn->real_escape_string($search_term); // Sanitize input
    $sql .= " WHERE name LIKE '%" . $search_term . "%' OR location LIKE '%" . $search_term . "%' OR capacity LIKE '%" . $search_term . "%'";
}

$result = $conn->query($sql);
$venues = ($result && $result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Venues</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #495057;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 25px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            text-align: center;
        }

        .search-container {
            display: flex;
            margin-bottom: 30px;
            gap: 15px;
            align-items: center;
        }

        .search-container form {
            display: flex;
            flex-grow: 1;
            gap: 10px;
        }

        .search-container input[type="text"] {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .venue-item {
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            transition: transform 0.2s ease-in-out;
        }

        .venue-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .venue-item h3 {
            color: #343a40;
            margin-bottom: 15px;
            font-size: 1.75rem;
        }

        .venue-item p {
            color: #6c757d;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .venue-item strong {
            font-weight: bold;
            color: #495057;
        }

        .venue-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            margin-top: 15px;
            display: block;
        }

        .no-venues {
            color: #6c757d;
            font-style: italic;
            margin-top: 20px;
            text-align: center;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-map-marked-alt"></i> Find Your Perfect Venue</h2>

        <div class="search-container">
            <form method="GET" action="find_venue.php">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search venues by name, location, or capacity">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if (!empty($venues)): ?>
            <div class="venues-grid">
                <?php foreach ($venues as $venue): ?>
                    <div class="venue-item">
                        <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                        <?php if (!empty($venue['image'])): ?>
                            <img src="<?php echo htmlspecialchars($venue['image']); ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>" class="venue-image">
                        <?php endif; ?>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Location:</strong> <?php echo htmlspecialchars($venue['location']); ?></p>
                        <p><strong><i class="fas fa-users"></i> Capacity:</strong> <?php echo htmlspecialchars($venue['capacity']); ?></p>
                        <p><strong><i class="fas fa-dollar-sign"></i> Price:</strong> <?php echo htmlspecialchars($venue['price']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-venues"><i class="fas fa-exclamation-triangle"></i> No venues found matching your criteria.</p>
        <?php endif; ?>

        <p class="mt-3"><a href="client-dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>