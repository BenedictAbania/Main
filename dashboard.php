<?php
include 'database.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/dashboard.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <div class="header-nav">
        <div class="navbar">
            <a href="login.php?logout=true">
                <span class="btn-icon">⬅️</span> Logout
            </a>
        </div>
    </div>
    <div class="container">
        <h1>Library Management System</h1>
        <p>Librarian Dashboard</p>
        <div class="button-group">
            <a href="createBook.php" class="btn">
                Add Books
            </a>
            <a href="viewCatalogue.php" class="btn">
                View Catalogues
            </a>
            <a href="borrowBook.php" class="btn">
                Borrow Book
            </a>
            <a href="returnBook.php" class="btn">
                Return Book
            </a>
            <a href="registerBorrower.php" class="btn">
                View Borrowers
            </a>
            <a href="borrowedRecords.php" class="btn">
                Borrowed Records
            </a>
        </div>
    </div>
</body>
</html>