<?php
// process_planner_survey.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $experience = $_POST['experience'];
    $event_types = isset($_POST['event_types']) ? implode(", ", $_POST['event_types']) : "";
    $challenges = isset($_POST['challenges']) ? implode(", ", $_POST['challenges']) : "";
    $feature1 = $_POST['feature1'];
    $feature2 = $_POST['feature2'];
    $feature3 = $_POST['feature3'];
    $additional_features = $_POST['additional_features'];


    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ems";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $query = "insert into planner_surveys (name, email, experience, event_types, challenges, feature1, feature2, feature3) values ('$name', '$email' , '$experience' , '$event_types' , '$challenges' , '$feature1' , '$feature2' , '$feature3')";
         $sql = mysqli_query($conn, $query);
         if($sql==false){
            die ("incorrect request");
      }

    $conn->close();
} else {
    echo "Invalid request.";
}
?>