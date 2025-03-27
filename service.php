<?php
session_start();
// db connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Fetch vendor services from the database
$sql = "SELECT image, product_name, price FROM vendors where status = 'approved'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services</title>
    <link rel="stylesheet" href="features.css"> 
    <style>
    .container {
        padding: 0;
        width: 100%; /* Ensure container takes full width */
        text-align: center;
    }

    .service-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        padding: 20px;
        
    }

    .service-item {
        width: 300px;
        margin: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        text-align: center; border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        background-color: #f9f9f9;
        box-sizing: border-box;
    }

    .service-item img {
        max-width: 100%;
        height: auto;
        margin-bottom: 10px;
    }

    .service-item .price {
        font-weight: bold;
        color: #007bff; 
    }
    .price{
        font-size: 30px;
    }
    h4{
        font-size: 20px;
    }
</style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="aj - Copy-Photoroom.png" class="logo">
        </div>
        <nav>
            <ul>
            <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Services</h2>
        <div class="service-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='service-item'>";
                    echo "<img src='" . $row['image'] . "' alt='" . $row['product_name'] . "'>";
                    echo "<h4>" . $row['product_name'] . "</h4>";
                    echo "<p class='price'>Price:" . $row['price'] . " fcfa </p>";
                    echo "</div>";
                }
            } else {
                echo "<p>No vendor services found.</p>";
            }
            ?>
        </div>
    </div>
    <a href="event.html">Back</a><br><br>
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
                <li><a href="event.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="company-info">
                <div class="logo">
                    <img src="aj - Copy-Photoroom.png" class="logo">
                </div>
                <div class="address">
                    <img src="phone-solid.svg" class="icons">
                    <p class="info">672073759</p>
                </div>
                <div class="address">
                    <img src="envelope-solid.svg" class="icons">
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

<?php
$conn->close();
?>