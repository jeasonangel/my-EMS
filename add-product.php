<?php
session_start();

// Check if the vendor is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id = $_SESSION['user_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = floatval($_POST['price']);
     // Image upload handling
     $target_dir = "uploads/vendor_products/"; // Creating this directory if it doesn't exist
     if (!file_exists($target_dir)) {
         mkdir($target_dir, 0777, true);
     }
     $target_file = $target_dir . basename($_FILES["image"]["name"]);
     $uploadOk = 1;
     $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
 
     // Check if image file is a actual image or fake image
     $check = getimagesize($_FILES["image"]["tmp_name"]);
     if ($check === false) {
         $_SESSION['error'] = "File is not an image.";
         $uploadOk = 0;
     }
 
     // Check file size
     if ($_FILES["image"]["size"] > 5000000) { // Limit to 5MB
         $_SESSION['error'] = "Sorry, your file is too large.";
         $uploadOk = 0;
     }
 
     // Allow certain file formats
     if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
         $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
         $uploadOk = 0;
     }
 
     if ($uploadOk == 1) {
         if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
             // Insert products data into the database
             $sql = "INSERT INTO vendor_products (vendor_id, name, description, price, image) VALUES ('$vendor_id', '$name', '$description', '$price', '$target_file')";
 
             if ($conn->query($sql) === TRUE) {
                 $_SESSION['success'] = "Product added successfully.";
             } else {
                 $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
             }
         } else {
             $_SESSION['error'] = "Sorry, there was an error uploading your file.";
         }
     }

     // Display error and success messages
if (isset($_SESSION['error'])) {
    echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
    unset($_SESSION['success']);
}

$conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Add New Product</h2>
        <form action="add-product.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01">
            </div>
            <div class="form-group">
                <label for="image">Product Image:</label>
                <input type="file" name="image" accept="image/*" required>
            </div>
            <button type="submit">Add Product</button>
        </form>
        <p><a href="vendor-dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>