<?php require_once __DIR__ . '/../../KT/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact Us | Global Tech</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            text-align: center;
            padding: 50px;
        }

        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: inline-block;
            max-width: 500px;
        }

        h1 {
            color: #333;
        }

        nav a {
            margin: 0 10px;
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>Get in Touch 📩</h1>
        <p>Reach out to the team behind the most powerful PHP translator.</p>
        <nav>
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
        </nav>
        <hr>
        <?php include __DIR__ . '/../../KT/widget.php'; ?>
    </div>
</body>

</html>