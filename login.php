<?php
session_start();

include 'db_connect.php'; // Ensure this file correctly establishes the $connect variable

$errorMessage = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Sanitize and trim user inputs
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $role = "user";

    // Basic validation
    if(empty($email) || empty($password)) {
        $errorMessage = 'Email and password are required!';
    }

    elseif(isset($_POST['btnSignUp'])) {
        // --- Sign Up Logic ---
        if(empty($name)) {
            $errorMessage = 'Name is required for sign up';
        }
        else{

            $checker = $connect->prepare("SELECT 1 FROM users WHERE email = ?");
            $checker->bind_param("s", $email);
            $checker->execute();
            $result = $checker->get_result();

            if($result->num_rows > 0) {
                $errorMessage = 'Email is already registered.';
            }
            $checker->close();

            if(!$errorMessage) {

                $checker = $connect->prepare("SELECT 1 FROM users WHERE username = ?");
                $checker->bind_param("s", $name);
                $checker->execute();
                $result = $checker->get_result();

                if($result->num_rows > 0) {
                $errorMessage = "Username '$name' is already taken. ";
                }
                $checker->close();
            }

            if(!$errorMessage) {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = $connect->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $query->bind_param("ssss", $name, $email, $hashed_password, $role);

                if($query->execute()) {

                // Store values in session directly
                $_SESSION['userId'] = $connect->insert_id;
                $_SESSION['username'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                                
                header("Location: /FYP/logbookfirstpage.php");
                exit;
                }
                else {
                    $errorMessage = "Failed to create account. Try again later";
                    error_log("MySQL Error: " . $query->error);
                }
                $query->close();
            }
        }

    }
    elseif(isset($_POST['signInBtn'])) {
        // --- Sign In Logic ---
        $checker = $connect->prepare("SELECT userId, username, email, password, role FROM users WHERE email = ?");
        $checker->bind_param("s", $email);
        $checker->execute();
        $result = $checker->get_result();

        if($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if(password_verify($password, $row['password'])) {
                // Successful Login
                $_SESSION['userId'] = $row['userId'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['role'] = $row['role'];
                if($_SESSION['role'] === 'user') {
                    header("Location: /FYP/logbookfirstpage.php");
                    exit();
                }
                else {
                    header("Location: /FYP/dashboard.php");
                    exit();
                }
            }
            else {
                // Wrong Password
                $errorMessage = "Incorrect password.";
            }

        } else {
            // User Not Found
            $errorMessage = "No account found with that email.";
        }
        $checker->close();
}
}
// Note: If no form was submitted, or an error occurred, the HTML below is rendered.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in and Sign up</title>
    <style>
        /* ... CSS from original code remains here ... */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            height: 100vh;
            background-image: linear-gradient(135deg, #2a612fff 0%, #2a612fff 100%);
            background-position: center;
            background-size: cover;
            position: relative;
        }

        .form-box {
            width: 90%;
            max-width: 450px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 50px 60px 70px;
            text-align: center;
        }

        .form-box h1 {
            font-size: 30px;
            margin-bottom: 60px;
            color: #2e7d32;
            position: relative;
        }

        .form-box h1::after {
            content: '';
            width: 30px;
            height: 4px;
            border-radius: 3px;
            background: #2e7d32;
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
        }

        .input-field {
            background: #eaeaea;
            margin: 15px 0;
            border-radius: 3px;
            display: flex;
            align-items: center;
            max-height: 65px;
            transition: max-height 0.5s;
            overflow: hidden;

        }
        input {
            width: 100%;
            background: transparent;
            border: 0;
            outline: 0;
            padding: 18px 15px;
        }

        .input-field i{
            margin-left: 15px;
            color: #999;
        }

        form p{
            text-align: left;
            font-size: 13px;
        }
        
        form p a{
            text-decoration: none;
            color: #2e7d32;
        }

        .btn-field {
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .btn-field button {
            flex-basis: 48%;
            background: #2e7d32;
            color: #fff;
            height: 40px;
            border-radius: 28px;
            border: 0;
            outline: 0;
            cursor: pointer;
            transition: background-color 1s;
        }
        .input-group {
            height: 280px;
        }

        .btn-field button.disable {
            background: #eaeaea;
            color: #555;
        }
        .toggle-field {
            margin-bottom: 20px;
        }

        .toggle-field button {
            gap: 10px;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
            background: #c8e6c9;
            color: #2e7d32;
        }
        .toggle-field button:hover {
            background: #2e7d32;
            color: #c8e6c9;
        }

        /** Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
            animation: slideUp 0.4s ease;
        }

        .modal-icon {
            font-size: 50px;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .modal-button {
            margin-top: 20px;
            padding: 10px 25px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;  
        }

        .modal-button:hover {
            background: #1b5e20;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1 id="title">Sign Up</h1>
            <form action="" method="POST">
                <div class="input-group">
                    <div class="input-field" id="nameField">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" placeholder="Name" name="name">
                    </div>
                    <div class="input-field">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" placeholder="Email" name="email">
                    </div>
                    <div class="input-field">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" placeholder="Password" name="password">
                    </div>
                    <p>Forgot your password? <a href="reset_password.php">Click here!</a></p>
                </div>
                <div class="toggle-field">
                    <button type="button" id="toggleSignUpBtn">Sign up</button>
                    <button type="button" id="toggleSignInBtn" class="disable">Sign in</button>
                </div>
                <div id="actionButtons" class="btn-field">
                    <button type="submit" name="btnSignUp" id="realSignUpBtn">Create Account</button>
                    <button type="submit" name="signInBtn" id="realSignInBtn" style="display:none;">Login</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ERROR MODAL-->
    <?php if ($errorMessage): ?>
    <div class="modal" id="errorModal" style="display: flex;">
    <div class="modal-content">
        <div class="modal-icon">Warning</div>
        <p><?= htmlspecialchars($errorMessage) ?></p>
        <button onclick="document.getElementById('errorModal').style.display='none'">OK</button>
    </div>
    </div>
<?php endif; ?>

    <script src="https://kit.fontawesome.com/67a65874b9.js" crossorigin="anonymous"></script>
<script>
    let signUpBtn = document.getElementById("toggleSignUpBtn");
    let signInBtn = document.getElementById("toggleSignInBtn");
    let nameField = document.getElementById("nameField");
    let title = document.getElementById("title");
    let realSignUpBtn = document.getElementById("realSignUpBtn");
    let realSignInBtn = document.getElementById("realSignInBtn");

    toggleSignInBtn.onclick = function() {
        nameField.style.maxHeight = "0";
        title.innerHTML = "Sign In";
        signUpBtn.classList.add("disable");
        signInBtn.classList.remove("disable");

        realSignUpBtn.style.display = "none";
        realSignInBtn.style.display = "inline-block";
    };
    toggleSignUpBtn.onclick = function() {
        nameField.style.maxHeight = "60px";
        title.innerHTML = "Sign Up";
        signUpBtn.classList.remove("disable");
        signInBtn.classList.add("disable");

        realSignUpBtn.style.display = "inline-block";
        realSignInBtn.style.display = "none";
    };

    // Optional: Close modal when clicking outside
    window.onclick = function(e) {
        const modal = document.getElementById('errorModal');
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    };
</script>
</body>
</html>