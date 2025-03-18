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
    $sql .= " WHERE name LIKE '%" . $search_term . "%' OR location LIKE '%" . $search_term . "%'";
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
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
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

        .search-container input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .search-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        .venues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .venue-item {
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
            transition: transform 0.2s ease-in-out;
        }

        .venue-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .venue-item h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.6em;
        }

        .venue-item p {
            color: #555;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .venue-item strong {
            font-weight: 600;
            color: #444;
        }

        .venue-image {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 15px;
            display: block;
        }

        .no-venues {
            color: #777;
            font-style: italic;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Find Your Perfect Venue</h2>

        <div class="search-container">
            <form method="GET" action="find_venue.php" style="display: flex; flex-grow: 1; gap: 10px;">
                <input type="text" name="search" placeholder="Search venues by name or location">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!empty($venues)): ?>
            <div class="venues-grid">
                <?php foreach ($venues as $venue): ?>
                    <div class="venue-item">
                        <h3><?php echo htmlspecialchars($venue['name']); ?></h3>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($venue['location']); ?></p>
                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars($venue['capacity']); ?></p>
                        <p><strong>Price:</strong> <?php echo htmlspecialchars($venue['price']); ?></p>
                        <?php if (!empty($venue['image'])): ?>
                            <img src="<?php echo htmlspecialchars($venue['image']); ?>" alt="<?php echo htmlspecialchars($venue['name']); ?>" class="venue-image">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-venues">No venues found matching your criteria.</p>
        <?php endif; ?>
    </div>
</body>
</html>    