<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background: linear-gradient(135deg, #2e7d32 0%, #27672aff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #2e7d32;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2e7d32 0%, #28682bff 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #2e7d32;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        require_once 'db_connect.php';

        $step = isset($_POST['step']) ? $_POST['step'] : 1;
        $message = "";
        $message_type = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if ($step == 1) {
                // Step 1: Verify username and email
                $username = $connect->real_escape_string(trim($_POST['username']));
                $email = $connect->real_escape_string(trim($_POST['email']));

                $sql = "SELECT userId FROM users WHERE username = ? AND email = ?";
                $stmt = $connect->prepare($sql);
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $step = 2;
                    $verified_userid = $row['userId'];
                    $verified_username = $username;
                } else {
                    $message = "Username and email do not match our records.";
                    $message_type = "error";
                }
                $stmt->close();
            } elseif ($step == 2) {
                // Step 2: Update password
                $userId = $connect->real_escape_string($_POST['userId']);
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if (strlen($new_password) < 6) {
                    $message = "Password must be at least 6 characters long.";
                    $message_type = "error";
                    $step = 2;
                    $verified_userid = $userId;
                    $verified_username = $connect->real_escape_string($_POST['username']);
                } elseif ($new_password !== $confirm_password) {
                    $message = "Passwords do not match.";
                    $message_type = "error";
                    $step = 2;
                    $verified_userid = $userId;
                    $verified_username = $connect->real_escape_string($_POST['username']);
                } else {
                    // Hash the password using bcrypt
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    $sql = "UPDATE users SET password = ? WHERE userId = ?";
                    $stmt = $connect->prepare($sql);
                    $stmt->bind_param("si", $hashed_password, $userId);

                    if ($stmt->execute()) {
                        $message = "Password reset successful! You can now login with your new password.";
                        $message_type = "success";
                        $step = 3;
                    } else {
                        $message = "Error updating password. Please try again.";
                        $message_type = "error";
                        $step = 2;
                        $verified_userid = $userId;
                        $verified_username = $connect->real_escape_string($_POST['username']);
                    }
                    $stmt->close();
                }
            }
        }

        $connect->close();
        ?>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your username and email to reset your password</p>
            <form method="POST" action="">
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit">Verify Account</button>
            </form>
        <?php elseif ($step == 2): ?>
            <h2>Set New Password</h2>
            <p class="subtitle">Enter your new password</p>
            <form method="POST" action="">
                <input type="hidden" name="step" value="2">
                <input type="hidden" name="userId" value="<?php echo $verified_userid; ?>">
                <input type="hidden" name="username" value="<?php echo $verified_username; ?>">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="password-requirements">Minimum 6 characters</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php elseif ($step == 3): ?>
            <h2>Success!</h2>
            <p class="subtitle">Your password has been reset successfully</p>
            <div class="back-link">
                <a href="login.php">Go to Login Page</a>
            </div>
        <?php endif; ?>

        <?php if ($step != 3): ?>
            <div class="back-link">
                <a href="login.php">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>