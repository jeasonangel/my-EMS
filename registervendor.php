<?php
session_start();
//if (isset($_SESSION['success'])) {
  //echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
  //unset($_SESSION['success']);
//}
      // 3. Database connection 
      $conn = new mysqli('localhost', 'root', '', 'eventmgtsyst');
      if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
      }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Retrieve form data 
  $company_name = $_POST['company_name'];
  $email = $_POST['email'];
  $contact = $_POST['contact'];
  $product_name = $_POST['product_name'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $experience = $_POST['experience'];

   // Image upload handling
   $target_dir = "uploads/vendors/"; // Creating this directory if it doesn't exist
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
  if ($_FILES["image"]["size"] > 500000) {
    $_SESSION['error'] = "Sorry, your file is too large.";
    $uploadOk = 0;
  }

  // Check if $uploadOk is set to 0 by an error
  if ($uploadOk == 0) {
    header('Location: registervendor.php');
    exit;
  // if everything is ok, try to upload file
  } else {
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {


      //  Insert vendor data into database
      $sql = "INSERT INTO vendors (company_name, email, contact, product_name, description, price, image, experience, status) 
              VALUES ('$company_name', '$email', '$contact', '$product_name', '$description', '$price', '$target_file', '$experience', 'pending')";

if ($conn->query($sql) === TRUE) {
  // Vendor registration successful
  $_SESSION['success'] = "Registration successful. Please wait for admin approval.";
  header('Location: getStarted.php');
  exit;
} else {
  $_SESSION['error'] = "Error: " . $sql . "<br>" . $conn->error;
  header('Location: registervendor.php');
  exit;
}
} else {
$_SESSION['error'] = "Sorry, there was an error uploading your file.";
header('Location: registervendor.php');
exit;
}
}
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Vendor Registration</title>
  <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

  <div class="container">
    <h2>Vendor Registration</h2>
    <form action="registervendor.php" method="POST" enctype="multipart/form-data">
      <input type="text" name="company_name" placeholder="Company Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="contact" placeholder="Contact" required>
      <input type="text" name="product_name" placeholder="Product Name" required>
      <textarea name="description" placeholder="Description"></textarea>
      <input type="number" name="price" placeholder="Price" required>
      <input type="file" name="image" accept="image/*" required>
      <input type="number" name="experience" placeholder="Years of Experience" required>
      <button type="submit" name="submit">Add</button>
    </form>
    <div class="login-link">
                <p>Already a member? <a href="login.php">Login</a></p>
            </div>
    <?php
    if (isset($_SESSION['error'])) {
      echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
      unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
      echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
      unset($_SESSION['success']);
    }
    ?>
  </div>
</body>
</html>