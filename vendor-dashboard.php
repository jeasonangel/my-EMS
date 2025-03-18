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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: #0b3d60;
            color: white;
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }

        header .logo {
            max-height: 50px;
        }

        header nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }

        header nav ul li {
            margin-left: 20px;
        }

        header nav ul li a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        header nav ul li a:hover {
            color: #ddd;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #0b3d60;
            margin-bottom: 20px;
            border-bottom: 2px solid #0b3d60;
            padding-bottom: 10px;
        }

        h3 {
            font-size: 24px;
            color: #0b3d60;
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
            background-color: #f8f8f8;
            font-weight: bold;
            color: #555;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .action-btn {
            display: inline-block;
            padding: 10px 15px;
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
            color: green;
            margin-top: 15px;
            display: block;
        }

        footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 40px;
        }

        .footer-content {
            display: flex;
            justify-content: space-around;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-content h4 {
            color: #eee;
            border-bottom: 1px solid #555;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .footer-content ul {
            list-style: none;
            padding: 0;
        }

        .footer-content ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
            display: block;
            margin-bottom: 8px;
        }

        .footer-content ul li a:hover {
            color: #eee;
        }

        .contact-form input[type="text"],
        .contact-form input[type="email"],
        .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #555;
            border-radius: 4px;
            box-sizing: border-box;
            background-color: #444;
            color: white;
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

        .company-info .logo {
            max-height: 40px;
            margin-bottom: 10px;
        }

        .company-info .address {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .company-info .icons {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            filter: invert(1);
        }

        .company-info .info {
            margin: 0;
        }

        .copyright {
            background-color: #222;
            color: #ccc;
            text-align: center;
            padding: 15px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" alt="Logo" style="max-height: 50px;">
        </div>
        <nav>
            <ul>
            <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="#footer">Contact Us</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Vendor Dashboard</h2>
        <?php
            // Display success message if it exists
            if (isset($_SESSION['success_message'])) {
                echo "<p class='success-message'>" . $_SESSION['success_message'] . "</p>";
                unset($_SESSION['success_message']); // Clear message
            }
        ?>
        <h3>My Profile</h3>
        <table>
            <thead>
                <tr>
                    <th>Company Name</th>
                    <th>Contact</th>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Experience</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($vendor_profile['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['contact']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['description']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['price']); ?></td>
                    <td><?php echo htmlspecialchars($vendor_profile['experience']); ?> years</td>
                    <td>
                        <a href="editvendorprofile.php" class="action-btn">Edit Profile</a>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        if (isset($_SESSION['success'])) {
            echo '<p class="success-message">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>
    </div>

    <div class="container">
        <h3>No product listed yet? <a href="add-product.php" style="color: #007bff; text-decoration: none;">Add product here</a></h3>
    </div>

    <footer>
        <div class="footer-content">
            <div class="contact-form">
                <h4>Contact Us</h4>
                <form action = "https://formsubmit.co/jeasonangel0@gmail.com" method = 'POST'>
                    <input type="text" placeholder="Your Name" id="name">
                    <input type="email" placeholder="Your Email" id="email">
                    <textarea placeholder="Your Message"></textarea>
                    <button type="submit" class="submit">Send</button>
                </form>
            </div>
            <div class="sitemap">
                <h4>Sitemap</h4>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Find Venues</a></li>
                    <li><a href="#login">Login</a></li>
                    <li><a href="#signup">Register</a></li>
                </ul>
            </div>
            <div class="company-info">
                <div class="logo">
                    <img src="aj - Copy-Photoroom.png" alt="Logo" style="max-height: 40px;">
                </div>
                <div class="address">
                    <img src="phone-solid.svg" alt="Phone Icon" class="icons">
                    <p class="info">672073759</p>
                </div>
                <div class="address">
                    <img src="envelope-solid.svg" alt="Email Icon" class="icons">
                    <p class="info">AJEvenemential@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>
    <div class="copyright">
        <p>&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
    </div>
</body>
</html>