<?php
session_start();
require_once 'db_connect.php';

$userId = $_SESSION['userId'];

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($subject) || empty($message)) {
        $error_message = 'All fields are required.';
    } else {
        
        $stmt = $connect->prepare("INSERT INTO inquiries (subject, message, userId) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $subject, $message, $userId);
        
        if ($stmt->execute()) {
            $success_message = 'Your inquiry has been submitted successfully! We will get back to you soon.';
            // Clear form
            $subject = $message = '';
        } else {
            $error_message = 'An error occurred. Please try again later.';
        }
        
        $stmt->close();
        $connect->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center</title>
    <link href="sidebar.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: row;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            padding-bottom: 50px;
            min-height: 100vh;
            background-color: #E4E9E7;
            transition:  var(--tran-05);
            overflow-y: auto;
        }

        .sidebar.close ~ .main-content {
            margin-left: 88px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .container header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 20px 0;
            background-color: #2e7d32;
            border-radius: 1.5rem;
            font-weight: bold;
        }
        
        .container header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .container header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card h2 {
            color: #498b4c;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 3px solid #498b4c;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: #498b4c;
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        button {
            background: #498b4c;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(69, 150, 81, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .faq-item {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #498b4c;
        }
        
        .faq-item h3 {
            color: #498b4c;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .faq-item p {
            color: #555;
            line-height: 1.8;
        }
        
        .faq-section {
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 2em;
            }
            
            .faq-section {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <section class="main-content">

        <div class="container">
        <header>
            <h1>Help Center</h1>
            <p>We're here to help! Browse our FAQ or submit an inquiry</p>
        </header>
        
        <div class="content-wrapper">
            <div class="card">
                <h2>Submit an Inquiry</h2>
                
                <?php if ($success_message): ?>
                    <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit">Submit Inquiry</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Frequently Asked Questions</h2>
                
                <div class="faq-item">
                    <h3>How long does it take to get a response?</h3>
                    <p>We typically respond to inquiries within 24-48 hours during business days. For urgent matters, please include "URGENT" in your subject line.</p>
                </div>
                
                <div class="faq-item">
                    <h3>What information should I include in my inquiry?</h3>
                    <p>Please provide as much detail as possible about your issue or question. Include relevant account information, order numbers, or screenshots if applicable.</p>
                </div>
                
                <div class="faq-item">
                    <h3>Can I track the status of my inquiry?</h3>
                    <p>Currently, you will receive email updates when your inquiry status changes. We're working on a self-service portal for tracking inquiries.</p>
                </div>
                
                <div class="faq-item">
                    <h3>What are your support hours?</h3>
                    <p>Our support team is available Monday through Friday, 9:00 AM to 6:00 PM. Inquiries submitted outside these hours will be addressed on the next business day.</p>
                </div>
                
                <div class="faq-item">
                    <h3>How can I update my submitted inquiry?</h3>
                    <p>To add information to an existing inquiry, simply submit a new inquiry referencing your original subject line and inquiry number if you have it.</p>
                </div>
            </div>
        </div>
    </div>

    </section>
</body>
</html>