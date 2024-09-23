<?php
session_start();
include 'db.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
   header("Location: login.php");
   exit;
}
$json_output=null;
// Function to call Python script dynamically and get matching resumes
function getMatchingResumes($jobDescription, $matchPercentage) {
    // Encode the text in base64
    $encodedText = base64_encode($jobDescription);
    $escapedJobDescription = escapeshellarg($encodedText);
    $escapedMatchPercentage = escapeshellarg($matchPercentage);
    $command = "python temp.py $escapedJobDescription $escapedMatchPercentage";
    $output = shell_exec($command);

    // Print the output
    // echo $output;
    
    // if ($output === null) {
    //     die('Error executing Python script.');
    // }
    $json_output = json_decode($output, true);
    return $json_output;
}

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    if (isset($_POST['job_description']) && isset($_POST['match_percentage'])) {
        // Retrieve form data and sanitize
        $jobDescription = $_POST['job_description'];
        $matchPercentage = $_POST['match_percentage'];

        // Print the data to the browser's console using JavaScript
        //echo $jobDescription;
        //echo $matchPercentage;

        $json_output = getMatchingResumes($jobDescription, $matchPercentage);
    }
    else{
        echo "Data is not sent via POST";
        }
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Matcher</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: auto;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            max-width: 200px;
            margin-bottom: 10px;
        }

        h1 {
            font-size: 2.5em;
            color: #333;
        }

        form {
            margin: 20px 0;
        }

        form div {
            margin-bottom: 15px;
        }

        label {
            font-size: 1.2em;
            color: #333;
        }

        input[type="file"], select, input[type="submit"] {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 10px auto;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            display: block;
            width: 100%;
            max-width: 600px;
            margin: 12px auto;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: 100px;
            overflow-x: auto;
            max-height: 300px;
            }

        input[type="number"] {
            display: block;
            width: 50px;
            max-width: 400px;
            margin: 10px auto;
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="radio"] {
            margin-right: 10px;
        }

        input[type="submit"] {
            background-color: #f79007;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #ffb700;
        }

        #results {
            margin-top: 20px;
        }

        h2 {
            font-size: 2em;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
            color: #333;
            font-size: 1.1em;
        }

        .pagination {
            margin-top: 20px;
        }

        .pagination button {
            padding: 10px 20px;
            font-size: 1em;
            border: 1px solid #ccc;
            background-color: #f2f2f2;
            cursor: pointer;
        }

        .pagination button:hover {
            background-color: #ddd;
        }

        footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: #333;
            color: white;
            text-align: center;
            font-size: 0.9em;
        }

        .logout-btn {
            background-color: #ff4c4c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #e43b3b;
        }

    </style>
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="Company Logo" class="logo">
        <h1>Resume Matcher</h1>

        <form id="resumeMatchForm" action="temp.php" method="POST">
            <div>
                <input type="radio" id="enter_jd" name="job_description_option" value="enter" value="<?php echo $_POST['job_description']; ?>" checked>
                <label for="enter_jd">Enter Job Description</label>
                <!-- <input type="radio" id="upload_jd" name="job_description_option" value="upload">
                <label for="upload_jd">Upload Document</label>
                <input type="radio" id="select_jd" name="job_description_option" value="select">
                <label for="select_jd">Select Recent Job Description</label> -->
            </div>

            <div id="enter_jd_section">
                <textarea id="job_description" name="job_description" rows="4" cols="50"></textarea>
            </div>

            <div style="display: flex; align-items: center; padding-left: 400px;">
                <label for="match_percentage">Match Percentage:</label>
                <input type="number" id="match_percentage" name="match_percentage" value="0" value="<?php echo $_POST['match_percentage']; ?>" min="0" max="100" required style="margin-left: 10px;">
            </div>

            <input type="submit" value="Match Resumes">
            <?php
            // Create a table to display the output
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Name</th><th>Match</th></tr>";

            // Loop through the resumes and display each one in a table row
            if ($json_output !== null){
                foreach ($json_output['resumes'] as $resume) {
                    echo "<tr>";
                    echo "<td>" . $resume['id'] . "</td>";
                    echo "<td>" . $resume['name'] . "</td>";
                    echo "<td>" . $resume['match'] . "</td>";
                    echo "</tr>";
                }
            } else{
                echo "<tr><td colspan='3'>No resumes found.</td></tr>";
            }

            echo "</table>";
            ?>
        </form>
            
        <form action="logout.php" method="post">
            <input type="submit" value="Logout">
        </form>
    <footer>
        &copy; 2024 KenexOft Technologies. All rights reserved.
    </footer>
</body>
</html>

