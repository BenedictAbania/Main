<?php
include "database.php";
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $year = $_POST['publication_year'] ?? null; 
    $isbn = $conn->real_escape_string($_POST['isbn']);

    $check_query = "SELECT id FROM books WHERE isbn = '$isbn'";
    $check_result = $conn->query($check_query);
    if ($check_result && $check_result->num_rows > 0) {
        $message = "<p style='color:red;'> Error: ISBN already exists!</p>";
    } else {
        $current_year = date("Y");
        $min_year = 1450; 
        $max_year = $current_year + 5; 
        if (!filter_var($year, FILTER_VALIDATE_INT) || $year < $min_year || $year > $max_year) {
            $message = "<p style='color:red;'> Error: Invalid Publication Year. Please enter a valid four-digit year between {$min_year} and {$max_year}.</p>";
        } else {
            $insert_query = "INSERT INTO books (title, author, publication_year, isbn) 
                              VALUES ('$title', '$author', '$year', '$isbn')";

            if ($conn->query($insert_query) === TRUE) {
                $message = "<p style='color:green;'> Book added successfully!</p>";
            } else {
                $message = "<p style='color:red;'> Error adding book: " . $conn->error . "</p>";
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
    <title>Add New Book</title>
    <link rel="stylesheet" href="assets/functions.css">
</head>

<body>
    <div class="header-nav">
        <div class="navbar">
            <a href="dashboard.php">
                <span class="navbar-icon"></span> Dashboard
            </a>
            <a href="createBook.php" class="active">
                <span class="navbar-icon"></span> Add Book
            </a>
            <a href="viewCatalogue.php">
                <span class="navbar-icon"></span> View Catalogues
            </a>
             <a href="borrowBook.php">
                <span class="navbar-icon"></span> Borrow Book
            </a>
            <a href="returnBook.php">
                <span class="navbar-icon"></span> Return Book
            </a>
            <a href="registerBorrower.php">
                <span class="navbar-icon"></span> Register Borrower
            </a>
            <a href="borrowedRecords.php">
                <span class="navbar-icon"></span> Borrowed Records
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="message-container">
                <?php if (!empty($message)) echo $message; ?>
            </div>

            <div class="card">
                <h3>Add New Book to Library</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" required>
                    </div>
                    <div class="form-group">
                        <label>Publication Year</label>
                        <input type="number" name="publication_year" required>
                    </div>
                    <div class="form-group">
                        <label>ISBN (Unique Identifier)</label>
                        <input type="text" name="isbn" required>
                    </div>
                    <button type="submit" class="btn">Add Book</button>
                </form>
            </div>
            
        </div>
    </div>
</body>
</html>
