<?php
error_reporting(E_ALL ^ E_NOTICE); 

include "database.php"; 
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    $delete_id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);

    if ($delete_id) {
        $conn->begin_transaction();
        $success = false;
        
        try {
            $stmt_delete_records = $conn->prepare("DELETE FROM borrowing_records WHERE book_id = ?");
            $stmt_delete_records->bind_param("i", $delete_id);
            $stmt_delete_records->execute();
            $stmt_delete_records->close();

            $stmt_delete_book = $conn->prepare("DELETE FROM books WHERE id = ?");
            $stmt_delete_book->bind_param("i", $delete_id);
            $stmt_delete_book->execute();
            
            if ($stmt_delete_book->affected_rows > 0) {
                 $message = "<p class='success-msg'>✅ Book ID {$delete_id} and its borrowing records deleted successfully!</p>";
                 $success = true;
            } else {
                 $message = "<p class='error-msg'>❌ Book ID {$delete_id} was not found, but related records were cleaned up.</p>";
                 $success = true; 
            }
            $stmt_delete_book->close();

            if ($success) {
                $conn->commit();
            } else {
                $conn->rollback();
            }
            
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $message = "<p class='error-msg'>❌ Fatal Error deleting book: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

    } else {
        $message = "<p class='error-msg'>❌ Invalid book ID for deletion.</p>";
    }
}

$books = $conn->query("SELECT id, title, author, publication_year, isbn, status FROM books ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Catalog & Management</title>
    <link rel="stylesheet" href="functions.css">
    <style>
        .main-content { padding: 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 1200px; }
        .card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        h1 { color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; }
        #searchInput { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        
        #bookTable { width: 100%; border-collapse: collapse; margin-top: 20px; }
        #bookTable th, #bookTable td { padding: 12px; border: 1px solid #eee; text-align: left; vertical-align: middle; }
        #bookTable th { background-color: #f8f8f8; font-weight: bold; }

        .status-available { color: green; font-weight: 600; }
        .status-borrowed { color: #e74c3c; font-weight: 600; }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
            margin: 2px;
            display: inline-block;
            font-weight: 500;
        }
        .edit-btn { background-color: #3498db; color: white; }
        .edit-btn:hover { background-color: #2980b9; }
        .delete-btn { background-color: #e74c3c; color: white; }
        .delete-btn:hover { background-color: #c0392b; }

        .error-msg, .success-msg { 
            padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600;
        }
        .error-msg { color: #c0392b; background-color: #fcecec; border: 1px solid #e74c3c; }
        .success-msg { color: #27ae60; background-color: #e9f7ef; border: 1px solid #2ecc71; }
    </style>
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
            <a href="viewCatalogue.php" class="active">
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
            <div class="card">
                <h1>Book Catalog & Management</h1>
                
                <?php if (!empty($message)) echo $message; ?>
                
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by title, author, or ISBN...">

                <table id="bookTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>ISBN</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($books->num_rows > 0) {
                            while ($row = $books->fetch_assoc()) { 
                                $status_class = $row['status'] == 'Available' ? 'status-available' : 'status-borrowed';
                            ?> 
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['author']) ?></td>
                                    <td><?= htmlspecialchars($row['publication_year']) ?></td>
                                    <td><?= htmlspecialchars($row['isbn']) ?></td>
                                    <td><span class="<?= $status_class ?>"><?= htmlspecialchars($row['status'] ?? 'Available') ?></span></td>
                                    <td>
                                        <a href="edit_book.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">✏️ Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete \'<?= addslashes($row['title']) ?>\' and ALL its borrowing history?')">
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="action-btn delete-btn">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No books found in the catalog.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function filterTable() {
            let input, filter, table, tr, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            
            table = document.getElementById("bookTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                txtValue = tr[i].textContent || tr[i].innerText;
                
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
