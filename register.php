<?php
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $password_hash, $first_name, $last_name);
            
            if ($stmt->execute()) {
                $success = "Registration successful. You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0px 10px rgba(0, 0, 0);
            width: 300px;
            position: relative;

        .heading {
            font-size: 2em;
            color: #2e2e2e;
            font-weight: 700;
            margin: 5px 0 10px 0;
            z-index: 2;
        }
        .inputContainer {
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }
        .inputField {
            width: 100%;
            height: 20px;
            background-color: transparent;
            border: none;
            border-bottom: 2px solid rgb(173, 173, 173);
            margin: 10px 0;
            color: black;
            font-size: .8em;
            font-weight: 500;
            box-sizing: border-box;
            padding-left: 30px;
        }

        .inputField:focus {
            outline: none;
            border-bottom: 2px solid rgb(255, 154, 21);
        }

        .inputField::placeholder {
            color: rgb(80, 80, 80);
            font-size: 1em;
            font-weight: 500;
        }
        
        #button {
            z-index: 2;
            position: relative;
            width: 100%;
            border: none;
            background-color: rgb(252, 166, 54);
            height: 30px;
            color: white;
            font-size: .8em;
            font-weight: 500;
            letter-spacing: 1px;
            margin: 10px;
            cursor: pointer;
        }

        #button:hover {
            background-color: rgb(228, 102, 0);
        }
        .heading {
            font-size: 2em;
            color: #2e2e2e;
            font-weight: 700;
            margin: 5px 0 10px 0;
            z-index: 3;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        
    </style>
</head>
<body>
    
    <?php
    if (!empty($error)) {
        echo "<p class='error'>$error</p>";
    }
    if (!empty($success)) {
        echo "<p class='success'>$success</p>";
    }
    ?>
    <div class="container">
    <p class="heading">Register</p>
        <div class="inputcontainer">
        <form method="POST" class="form_main" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="inputcontainer">
                <input type="text" class="inputField" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="inputcontainer">
                <input type="email" class="inputField" id="email"name="email" placeholder="Email" required>
            </div>
            <div class="inputcontainer">
                <input type="password" class="inputField" id="password"name="password" placeholder="Password" required>
            </div>
            <div class="inputcontainer">
                <input type="password" class="inputField" id="confirm_password"name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <div class="inputcontainer">
                <input type="text" class="inputField" id="first_name"name="first_name" placeholder="First Name" required>
            </div>
            <div class="inputcontainer">
                <input type="text" class="inputField" id="last_name" name="last_name" placeholder="Last Name" required>
            </div>
            <div class="inputcontainer">
                <input type="submit" id="button" value="Register">
            </div>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    
</body>
</html>