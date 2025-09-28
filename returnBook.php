<?php
include "database.php"; 
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'return_book') {
    
    $id = $conn->real_escape_string($_POST['record_id'] ?? ''); 
    $return_date = $conn->real_escape_string($_POST['return_date'] ?? '');
    $book_id = $conn->real_escape_string($_POST['book_id'] ?? '');

    if (empty($id) || empty($return_date) || empty($book_id)) {
        $message = "<p style='color:red;'> Error: Missing record ID, book ID, or return date.</p>";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $return_date)) {
        $message = "<p style='color:red;'> Error: Invalid date format. Please use YYYY-MM-DD.</p>";
    } else {
        $conn->begin_transaction(); 
        try {
            $update_borrow_sql = "UPDATE borrowing_records SET return_date = '{$return_date}' WHERE id = '{$id}' AND return_date IS NULL";
            
            if ($conn->query($update_borrow_sql) === FALSE) {
                throw new Exception("Error updating borrowing record: " . $conn->error);
            }
            $update_book_sql = "UPDATE books SET status = 'Available' WHERE id = '{$book_id}'";
            if ($conn->query($update_book_sql) === FALSE) {
                throw new Exception("Error updating book status: " . $conn->error);
            }
            
            $conn->commit();
            $message = "<p style='color:green;'> Book return successfully recorded on **{$return_date}**!</p>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<p style='color:red;'> Transaction Failed: " . $e->getMessage() . "</p>";
        }
    }
}

$active_records_query = "
    SELECT
        br.id AS record_id, 
        br.book_id,
        b.title AS book_title,
        br.user_id,
        r.name AS borrower_name,
        br.borrow_date
    FROM
        borrowing_records br
    JOIN
        books b ON br.book_id = b.id 
    JOIN
        borrower r ON br.user_id = r.borrower_id
    WHERE
        br.return_date IS NULL
    ORDER BY
        br.borrow_date ASC
";

$active_records_result = $conn->query($active_records_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book</title>
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
             <a href="borrowBook.php">
                <span class="navbar-icon"></span> Borrow Book
            </a>
            <a href="returnBook.php" class="active">
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
                <h1>Active Borrowing Records</h1>
                <p style="text-align:center; color:#555; margin-bottom: 25px;"><i>Select a record below and enter the return date to complete the transaction.</i></p>
                
                <table id="activeRecordsTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Record ID</th>
                            <th style="width: 10%;">Book ID</th>
                            <th style="width: 30%;">Book Title</th>
                            <th style="width: 25%;">Borrower Name</th>
                            <th style="width: 15%;">Borrowed Date</th>
                            <th style="width: 10%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_records_result && $active_records_result->num_rows > 0) {
                            while ($row = $active_records_result->fetch_assoc()) { ?> 
                                <tr>
                                    <td><?= htmlspecialchars($row['record_id']) ?></td>
                                    <td><?= htmlspecialchars($row['book_id']) ?></td>
                                    <td><?= htmlspecialchars($row['book_title']) ?></td>
                                    <td><?= htmlspecialchars($row['borrower_name']) ?> (ID: <?= htmlspecialchars($row['user_id']) ?>)</td>
                                    <td><?= htmlspecialchars($row['borrow_date']) ?></td>
                                    <td>
                                        <button 
                                            class="return-btn" 
                                            data-record-id="<?= htmlspecialchars($row['record_id']) ?>"
                                            data-book-id="<?= htmlspecialchars($row['book_id']) ?>"
                                            onclick="openReturnPrompt(this)">
                                            Return
                                        </button>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #555;">No books are currently checked out.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="datePrompt" class="date-prompt">
        <div class="prompt-content">
            <h3>Enter Return Date</h3>
            <p>What date did the user return this book?</p>
            <form id="returnForm" method="post" action="returnBook.php">
                <input type="hidden" name="form_type" value="return_book">
                <input type="hidden" name="record_id" id="promptRecordId">
                <input type="hidden" name="book_id" id="promptBookId">
                
                <label for="returnDateInput">Return Date:</label>
                <input type="date" id="returnDateInput" name="return_date" value="<?= date('Y-m-d'); ?>" required> 
                
                <div class="prompt-actions">
                    <button type="submit" id="confirmReturn">Confirm Return</button>
                    <button type="button" id="cancelReturn" onclick="closeReturnPrompt()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReturnPrompt(button) {
            const recordId = button.getAttribute('data-record-id');
            const bookId = button.getAttribute('data-book-id');

            document.getElementById('promptRecordId').value = recordId;
            document.getElementById('promptBookId').value = bookId;

            document.getElementById('datePrompt').style.display = 'flex';
        }

        function closeReturnPrompt() {
            document.getElementById('datePrompt').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('returnDateInput').setAttribute('max', today);
        });
    </script>
</body>
</html>
