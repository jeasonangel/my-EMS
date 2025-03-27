<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch vendor's profile
$vendor_id = $_SESSION['user_id'];
$vendor_profile_sql = "SELECT * FROM vendors WHERE ID = '$vendor_id'";
$vendor_profile_result = $conn->query($vendor_profile_sql);
$vendor_profile = $vendor_profile_result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #495057;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #343a40;
            color: white;
            padding: 1rem 0;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .navbar-nav {
            list-style: none;
            display: flex;
            margin-left: auto;
        }

        .nav-item {
            margin-left: 20px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #f8f9fa;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #007bff;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        h3 {
            color: #28a745;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
            background-color: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .action-btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            color: white;
            background-color: #007bff;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        .success-message {
            color: #28a745;
            margin-top: 15px;
            display: block;
        }

        .logout-btn {
            background-color: #dc3545;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        .add-product-link {
            color: #007bff;
            text-decoration: none;
        }

        .add-product-link:hover {
            text-decoration: underline;
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 40px 0;
            margin-top: 40px;
        }

        .footer-content {
            display: flex;
            justify-content: space-around;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-section {
            flex: 1;
            margin-right: 20px;
        }

        .footer-section:last-child {
            margin-right: 0;
        }

        .footer-section h4 {
            color: #f8f9fa;
            border-bottom: 1px solid #6c757d;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s ease;
            display: block;
            margin-bottom: 8px;
        }

        .footer-section ul li a:hover {
            color: #fff;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #6c757d;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: #495057;
            color: #f8f9fa;
        }

        .contact-form button.submit {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .contact-form button.submit:hover {
            background-color: #0056b3;
        }

        .company-info .logo-footer {
            max-height: 40px;
            margin-bottom: 10px;
        }

        .company-info .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #adb5bd;
        }

        .company-info .info-item i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .copyright {
            background-color: #212529;
            color: #adb5bd;
            text-align: center;
            padding: 15px 0;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2><i class="fas fa-tachometer-alt"></i> Vendor Dashboard</h2>
        <?php
            // Display success message if it exists
            if (isset($_SESSION['success_message'])) {
                echo "<p class='success-message'><i class='fas fa-check-circle'></i> " . $_SESSION['success_message'] . "</p>";
                unset($_SESSION['success_message']); // Clear message
            }
        ?>
        <h3><i class="fas fa-user"></i> My Profile</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Experience</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($vendor_profile['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['price']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['experience']); ?> years</td>
                    <td>
                        <a href="editvendorprofile.php" class="action-btn"><i class="fas fa-edit"></i> Edit Profile</a>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        if (isset($_SESSION['success'])) {
            echo '<p class="success-message"><i class="fas fa-check-circle"></i> ' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>
    </div>

    <div class="container">
        <h3><i class="fas fa-box-open"></i> Products</h3>
        <p>No product listed yet? <a href="add-product.php" class="add-product-link"><i class="fas fa-plus-circle"></i> Add product here</a></p>
     </div>
     <li class="nav-item"><a class="nav-link btn btn-danger btn-sm logout-btn" href="logout.php" onclick="return confirm('Are you sure you want to logout?');"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
   
    <footer>
        <div class="footer-content">
            <div class="footer-section contact-form">
                <h4><i class="fas fa-envelope"></i> Contact Us</h4>
                <form action="https://formsubmit.co/jeasonangel0@gmail.com" method="POST">
                    <input type="text" placeholder="Your Name" id="name" class="form-control">
                    <input type="email" placeholder="Your Email" id="email" class="form-control">
                    <textarea placeholder="Your Message" class="form-control"></textarea>
                    <button type="submit" class="submit btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                </form>
            </div>
            <div class="footer-section sitemap">
                <h4><i class="fas fa-sitemap"></i> Sitemap</h4>
                <ul>
                    <li><a href="event.html"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="feature.html"><i class="fas fa-star"></i> Features</a></li>
                    <li><a href="#footer"><i class="fas fa-envelope"></i> Contact Us</a></li>
                    <li><a href="find_venue.php"><i class="fas fa-map-marker-alt"></i> Find Venues</a></li>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                </ul>
            </div>
            <div class="footer-section company-info">
                <h4><i class="fas fa-info-circle"></i> Company Info</h4>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <p class="info">672073759</p>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <p class="info">AJEvenemential@gmail.com</p>
                </div>
                <div class="logo-footer">
                    <img src="aj - Copy-Photoroom.png" alt="Logo" style="max-height: 40px;">
                </div>
            </div>
        </div>
    </footer>
    <div class="copyright">
        <p>&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>