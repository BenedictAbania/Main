<?php
include "database.php"; 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_id']) && isset($_POST['borrower_id'])) {
    $book_id = $conn->real_escape_string($_POST['book_id']);
    $borrower_id = $conn->real_escape_string($_POST['borrower_id']);

    if (empty($borrower_id)) {
        $message = "<p style='color:red;'>Error: Borrower ID cannot be empty.</p>";
    } else {
        $borrow_date = date("Y-m-d");
        $check_query = "SELECT status, title FROM books WHERE id = '$book_id' LIMIT 1";
        $check_result = $conn->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $book_data = $check_result->fetch_assoc();
            
            if ($book_data['status'] == 'Available' || empty($book_data['status'])) {
                
                $conn->begin_transaction();
                $success = true;

                $update_book_query = "UPDATE books SET status = 'Borrowed' WHERE id = '$book_id'";
                if (!$conn->query($update_book_query)) {
                    $success = false;
                }
                $insert_record_query = "INSERT INTO borrowing_records (book_id, user_id, borrow_date) 
                                         VALUES ('$book_id', '$borrower_id', '$borrow_date')";
                
                if (!$conn->query($insert_record_query)) {
                    $success = false;
                }
                if ($success) {
                    $conn->commit();
                    $message = "<p style='color:green;'> Book '<b>" . htmlspecialchars($book_data['title']) . "</b>' checked out successfully to Borrower ID: <b>$borrower_id</b>.</p>";
                } else {
                    $conn->rollback();
                    $message = "<p style='color:red;'> Error recording borrow. Database failed to update. Please try again.</p>";
                }
            } else {
                   $message = "<p style='color:red;'> Error: Book '<b>" . htmlspecialchars($book_data['title']) . "</b>' is already marked as '{$book_data['status']}'.</p>";
            }

        } else {
            $message = "<p style='color:red;'> Error: Book ID not found in the catalog.</p>";
        }
    }
}

$books = $conn->query("SELECT * FROM books WHERE status = 'Available' OR status IS NULL ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Book</title>
    <link rel="stylesheet" href="functions.css">
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
             <a href="borrowBook.php" class="active">
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
            <?php if (!empty($message)): ?>
                <div class="message-container">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h1>Available Books for Checkout</h1>
                <p style="color: #666; font-style: italic; margin-bottom: 25px;">Enter the borrower's ID/Credential and click 'Checkout' to process the loan.</p>
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by title, author, or ISBN...">

                <table id="bookTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>ISBN</th>
                            <th style="width: 250px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books->num_rows > 0) {
                            while ($row = $books->fetch_assoc()) { ?> 
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['author']) ?></td>
                                    <td><?= htmlspecialchars($row['publication_year']) ?></td>
                                    <td><?= htmlspecialchars($row['isbn']) ?></td>
                                    <td>
                                        <form method="post" class="action-container" onsubmit="return validateForm(this)">
                                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                            <input type="text" 
                                                            name="borrower_id" 
                                                            class="borrower-input" 
                                                            placeholder="Borrower ID" 
                                                            required>
                                            <button type="submit" class="action-btn">
                                                Checkout
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No books are currently available to borrow.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function validateForm(form) {
            const borrowerInput = form.querySelector('[name="borrower_id"]');
            if (borrowerInput.value.trim() === "") {
                borrowerInput.style.borderColor = 'red';
                borrowerInput.placeholder = 'ID REQUIRED';
                return false;
            }
            return true;
        }
        function filterTable() {
            let input, filter, table, tr, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("bookTable");
            tr = table.getElementsByTagName("tr");
            for (i = 1; i < tr.length; i++) {
                let rowMatch = false;
                for(let j = 0; j < 4; j++) {
                    txtValue = tr[i].cells[j].textContent || tr[i].cells[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        rowMatch = true;
                        break;
                    }
                }
                if (rowMatch) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
