<?php
if (isset($_SESSION['success'])) {
    echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
    unset($_SESSION['success']);
  }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Choose Your Role</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>


        .role-container {
            text-align: center;
        }

        .role-button {
            display: inline-block;
            margin: 50px;
            cursor: pointer;
        }

        .role-button img {
            width: 150px;
            height: 150px;
            border-radius: 50%;

            border: 2px solid #ddd;
        }

        .role-button p {
            margin-top: 10px;
            font-weight: bold;
        }
        .image{
            width: 30px;
            height: 30px;
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
                <li><a href="index.html">Home</a></li>
                <li><a href="feature.html">Features</a></li>
                <li><a href="service.php">Services</li>
                <li><a href="find_venue.php">Find Venues</a></li>
                <li><a href="contactUs.php">Contact Us</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="getStarted.php">Register</a></li>
            </ul>
        </nav>
    </header>
    <div class="role-container">
        <h2>Choose Your Role</h2>

        <div class="role-button">
            <a href="registerClient.php"><img src="user-client.svg" alt="Client" class = "image"></a>
            <p>Client</p>
        </div>

        <div class="role-button">
            <a href="registerAdmin.php"><img src="circle-user-admin.svg" alt="Admin" class = "image"></a>
            <p>Admin</p>
        </div>

        <div class="role-button">
            <a href="registervendor.php"><img src="user-group-vendor.svg" alt="Vendor" class = "image"></a>
            <p>Vendor</p>
        </div>
    </div>
    <footer>
        <div class="footer-content">
            <div class="contact-form">
                <h4>Contact Us</h4>
                <form action = "https://formsubmit.co/jeasonangel0@gmail.com" method = 'POST'>
                    <input type="text" placeholder="Your Name">
                    <input type="email" placeholder="Your Email">
                    <textarea placeholder="Your Message"></textarea>
                    <button type="submit">Send</button>
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
    <script>
        // Optional: Add hover effect for better user experience
        const roleButtons = document.querySelectorAll('.role-button');
        roleButtons.forEach(button => {
            button.addEventListener('mouseover', () => {
                button.querySelector('img').style.border = '2px solid #aaa';
            });
            button.addEventListener('mouseout', () => {
                button.querySelector('img').style.border = '2px solid #ddd';
            });
        });
    </script>
</body>
</html>