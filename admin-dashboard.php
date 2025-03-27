<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for adding venue
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_venue'])) {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price'];

    // Image upload handling
    $target_dir = "uploads/venues/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $_SESSION['error'] = "File is not an image.";
        $uploadOk = 0;
    }
    if ($_FILES["image"]["size"] > 5000000) {
        $_SESSION['error'] = "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO venues (name, location, capacity, price, image) VALUES ('$name', '$location', '$capacity', '$price', '$target_file')";
            if ($conn->query($sql) === TRUE) {
                $_SESSION['success'] = "Venue added successfully.";
            } else {
                $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Sorry, there was an error uploading your file.";
        }
    }
    header("Location: admin-dashboard.php");
    exit();
}

// Fetch pending vendor registrations
$pending_vendors_sql = "SELECT * FROM vendors WHERE status = 'pending'";
$pending_vendors_result = $conn->query($pending_vendors_sql);

// Fetch all events
$events_sql = "SELECT events.*, clients.name AS client_name
                    FROM events
                    JOIN clients ON events.client_id = clients.client_id";
$events_result = $conn->query($events_sql);

// Fetch all expenses with event and client details
$expenses_sql = "SELECT expenses.category, expenses.budget_limit, events.event_name, clients.name AS client_name, clients.email AS client_email
                     FROM expenses
                     JOIN events ON expenses.event_id = events.ID
                     JOIN clients ON events.client_id = clients.client_id";
$expenses_result = $conn->query($expenses_sql);
if ($expenses_result === false) {
    echo "SQL Error: " . $conn->error;
    die();
}
$expenses = $expenses_result->fetch_all(MYSQLI_ASSOC);

// Fetch all expenses grouped by event to show a summary on the dashboard
$expenses_summary_sql = "SELECT events.ID AS event_id, events.event_name, clients.name AS client_name, COUNT(expenses.expense_id) AS total_expenses
                            FROM expenses
                            JOIN events ON expenses.event_id = events.ID
                            JOIN clients ON events.client_id = clients.client_id
                            GROUP BY events.ID
                            ORDER BY events.event_name";
$expenses_summary_result = $conn->query($expenses_summary_sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #495057;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        header {
            background-color: #007bff;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        header h2 {
            margin: 0;
            font-size: 2.5em;
        }

        .dashboard-options {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .dashboard-option {
            background-color: #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: background-color 0.3s ease;
            text-decoration: none;
            color: #495057;
        }

        .dashboard-option:hover {
            background-color: #dee2e6;
        }

        .dashboard-option i {
            font-size: 2em;
            margin-bottom: 10px;
            color: #007bff;
        }

        h3 {
            color: #28a745;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .action-btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            color: white;
            transition: background-color 0.3s ease;
            display: inline-block;
            text-align: center;
        }

        .approve-btn {
            background-color: #28a745; /* Green */
        }

        .approve-btn:hover {
            background-color: #1e7e34;
        }

        .reject-btn {
            background-color: #dc3545; /* Red */
        }

        .reject-btn:hover {
            background-color: #c82333;
        }

        .manage-btn {
            background-color: #ffc107; /* Yellow */
            color: #212529;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .manage-btn:hover {
            background-color: #e0a800;
        }

        form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border: 1px solid #ddd;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="number"],
        form input[type="file"] {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }

        form button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        form button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: #dc3545;
            margin-top: 10px;
        }

        .success-message {
            color: #28a745;
            margin-top: 10px;
        }

        .logout {
            background-color: #dc3545;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            float: right;
            margin-top: -20px;
            transition: background-color 0.3s ease;
        }

        .logout:hover {
            background-color: #c82333;
        }

        .report-link {
            display: inline-block;
            background-color: #17a2b8;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .report-link:hover {
            background-color: #138496;
        }
    </style>
</head>

<body>

<div class="container">
    <header>
        <h2>Admin Dashboard</h2>
    </header>

    <div class="dashboard-options">
        <a href="#pending-vendors" class="dashboard-option">
            <i class="fas fa-user-plus"></i>
            <span>Pending Vendors</span>
        </a>
        <a href="#all-events" class="dashboard-option">
            <i class="fas fa-calendar-alt"></i>
            <span>All Events</span>
        </a>
        <a href="#manage-expenses" class="dashboard-option">
            <i class="fas fa-money-bill-wave"></i>
            <span>Manage Expenses</span>
        </a>
        <a href="#all-expenses" class="dashboard-option">
            <i class="fas fa-list-alt"></i>
            <span>All Expenses List</span>
        </a>
        <a href="#add-venue" class="dashboard-option">
            <i class="fas fa-plus-circle"></i>
            <span>Add New Venue</span>
        </a>
    </div>

    <h3 id="pending-vendors">Pending Vendor Approvals</h3>
    <?php if ($pending_vendors_result->num_rows > 0) : ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Year of Experience</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $pending_vendors_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['price']); ?></td>
                        <td><?php echo htmlspecialchars($row['experience']); ?></td>
                        <td>
                            <div class="action-btn-container">
                                <a href="approve-vendor.php?id=<?php echo $row['ID']; ?>" class="action-btn approve-btn">Approve</a>
                                <a href="reject-vendor.php?id=<?php echo $row['ID']; ?>" class="action-btn reject-btn">Reject</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>No pending vendor approvals.</p>
    <?php endif; ?>

    <h3 id="all-events">All Events</h3>
    <?php if ($events_result->num_rows > 0) : ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Time</th>
                    <th>Budget</th>
                    <th>Client Name</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $events_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['time']); ?></td>
                        <td><?php echo htmlspecialchars($row['budget']); ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p>No events created yet.</p>
    <?php endif; ?>

    <h3 id="manage-expenses">Manage Event Expenses</h3>
    <?php if ($expenses_summary_result->num_rows > 0) : ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Client Name</th>
                    <th>Total Expense Categories</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $expenses_summary_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_expenses']); ?></td>
                        <td>
                            <a href="manage-expenses.php?event_id=<?php echo $row['event_id']; ?>" class="manage-btn">Manage</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else : ?>
            <p>No expenses recorded yet.</p>
        <?php endif; ?>

        <h3 id="all-expenses">All Expenses</h3>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Category</th>
                    <th>Budget Limit</th>
                    <th>Event Name</th>
                    <th>Client Name</th>
                    <th>Client Email</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $expense) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['category']); ?></td>
                        <td><?php echo htmlspecialchars($expense['budget_limit']); ?></td>
                        <td><?php echo htmlspecialchars($expense['event_name']); ?></td>
                        <td><?php echo htmlspecialchars($expense['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($expense['client_email']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 id="add-venue">Add New Venue</h3>
        <form action="admin-dashboard.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Venue Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Venue Name" required>
            </div>
            <div class="mb-3">
                <label for="location" class="form-label">Location</label>
                <input type="text" class="form-control" id="location" name="location" placeholder="Location" required>
            </div>
            <div class="mb-3">
                <label for="capacity" class="form-label">Capacity</label>
                <input type="number" class="form-control" id="capacity" name="capacity" placeholder="Capacity" required>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" placeholder="Price" required>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Venue Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
            </div>
            <button type="submit" name="add_venue" class="btn btn-primary">Add Venue</button>
        </form>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger mt-3">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success mt-3">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>

        <h3 id="reports">Reports</h3>
        <p><a href="generate-report.php" class="report-link"><i class="fas fa-file-pdf"></i> Generate Reports</a></p>

        <a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>