<?php
include "database.php"; 

$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'add_borrower') {
    
    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $age = $conn->real_escape_string($_POST['age'] ?? '');

    if (empty($name) || empty($email) || empty($age)) {
        $message = "<p style='color:red;'> Error: All fields are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color:red;'> Error: Invalid email format.</p>";
    } elseif (!is_numeric($age) || $age <= 0) {
        $message = "<p style='color:red;'> Error: Age must be a positive number.</p>";
    } else {
        $sql = "INSERT INTO borrower (name, email, age) VALUES ('$name', '$email', '$age')";

        if ($conn->query($sql) === TRUE) {
            $new_borrower_id = $conn->insert_id;
            $message = "<p style='color:green;'> New borrower **" . htmlspecialchars($name) . "** successfully registered! Borrower ID: **{$new_borrower_id}**</p>";
        } else {
            $message = "<p style='color:red;'> Error adding borrower: " . $conn->error . "</p>";
        }
    }
}

$borrowers_query = "
    SELECT
        b.borrower_id,
        b.name,
        b.email,
        b.age,
        COUNT(br.book_id) AS borrowed_count
    FROM
        borrower b -- Corrected table name to 'borrower'
    LEFT JOIN
        borrowing_records br ON b.borrower_id = br.user_id AND br.return_date IS NULL
    GROUP BY
        b.borrower_id, b.name, b.email, b.age
    ORDER BY
        b.borrower_id ASC
";

$borrowers_result = $conn->query($borrowers_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Borrower</title>
    <link rel="stylesheet" href="assets/functions.css">
</head>

<body>
    <div class="header-nav">
        <div class="navbar">
            <a href="dashboard.php">
                <span class="navbar-icon"></span> Dashboard
            </a>
            <a href="createBook.php">
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
            <a href="registerBorrower.php" class="active">
                <span class="navbar-icon"></span> Register Borrower
            </a>
            <a href="borrowedRecords.php">
                <span class="navbar-icon"></span> Borrowed Records
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="form-container container">
            <?php if (!empty($message)): ?>
                <div class="message-container">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h1>Register New Borrower</h1>
                
                <form action="registerBorrower.php" method="post">
                    <input type="hidden" name="form_type" value="add_borrower">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required placeholder="e.g., Jane Doe">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="e.g., jane.doe@example.com">
                    </div>

                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" required min="10" max="120" placeholder="e.g., 35">
                    </div>
                    
                    <button type="submit" class="btn">
                        Register Borrower
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card container">
            <h1>Registered Borrowers</h1>
            
            <table id="borrowerTable">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 30%;">Name</th>
                        <th style="width: 30%;">Email</th>
                        <th style="width: 10%;">Age</th>
                        <th style="width: 20%;">Books Borrowed (Active)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($borrowers_result && $borrowers_result->num_rows > 0) {
                        while ($row = $borrowers_result->fetch_assoc()) { ?> 
                            <tr>
                                <td><?= htmlspecialchars($row['borrower_id']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['age']) ?></td>
                                <td>
                                    <span style="font-weight: bold; color: <?= ($row['borrowed_count'] > 0) ? '#ff6347' : '#10b981'; ?>;">
                                        <?= $row['borrowed_count'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No borrowers have been registered yet.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>