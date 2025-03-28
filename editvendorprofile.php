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

$vendor_id = $_SESSION['user_id'];

// Fetch vendor's profile
$vendor_profile_sql = "SELECT * FROM vendors WHERE ID = '$vendor_id'";
$vendor_profile_result = $conn->query($vendor_profile_sql);
$vendor_profile = $vendor_profile_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_vendorexpense'])) {
        $company_name = $_POST['company_name'];
        $contact = $_POST['contact'];
        $product_name = $_POST['product_name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $experience = $_POST['experience'];

            // Sanitize inputs
    $company_name = $conn->real_escape_string($company_name);
    $contact = $conn->real_escape_string($contact);
    $product_name = $conn->real_escape_string($product_name);
    $description = $conn->real_escape_string($description);
    $price = $conn->real_escape_string($price);
    $experience = $conn->real_escape_string($experience);

        $sql = "UPDATE vendors SET company_name = '$company_name', contact = '$contact', product_name = '$product_name', description = '$description', price = '$price', experience = '$experience' WHERE ID = '$vendor_id'";

        $conn->query($sql);
    

    if ($conn->query($sql) === TRUE) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header('Location: vendor-dashboard.php');
        exit;
    } else {
        $error_message = "Error updating profile: " . $conn->error;
    }
}
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Vendor Profile</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>

        input[type="text"],
        textarea,
        input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        button {
            background-color: rgb(54, 112, 247);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
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
                <li><a href="getStarted.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h2>Edit Profile</h2>

        <?php if (isset($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>

        <form method="post" action="editvendorprofile.php">
            <label for="company_name">Company Name:</label>
            <input type="text" name="company_name" value="<?php echo $vendor_profile['company_name']; ?>" required>

            <label for="contact">Contact:</label>
            <input type="text" name="contact" value="<?php echo $vendor_profile['contact']; ?>" required>

            <label for="product_name">Product Name:</label>
            <input type="text" name="product_name" value="<?php echo $vendor_profile['product_name']; ?>" required>

            <label for="description">Description:</label>
            <textarea name="description" required><?php echo $vendor_profile['description']; ?></textarea>

            <label for="price">Price:</label>
            <input type="number" name="price" value="<?php echo $vendor_profile['price']; ?>" required>

            <label for="experience">Experience (Years):</label>
            <input type="number" name="experience" value="<?php echo $vendor_profile['experience']; ?>" required>

            <button type="submit" name = "edit_vendorexpense">Update Profile</button>
        </form>
    </div>

    <footer>
        <div class="footer-content">
            </div>
    </footer>
    <div class="copyright">
        <p>&copy; 2025 Event Management System. All rights reserved. AJ EVENEMENTIAL</p>
    </div>
</body>
</html>