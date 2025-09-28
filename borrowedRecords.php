<?php
include "database.php";
$filter_user_id = isset($_GET['user_id']) ? $conn->real_escape_string($_GET['user_id']) : '';
$query = "
    SELECT 
        br.id as record_id,
        b.title, 
        b.isbn,
        u.name as user_name,
        u.borrower_id as user_id, /* Use borrower_id for consistency */
        br.borrow_date,
        br.return_date /* Now fetching all records and return date */
    FROM 
        borrowing_records br
    JOIN 
        books b ON br.book_id = b.id
    LEFT JOIN
        borrower u ON br.user_id = u.borrower_id /* Use the 'borrower' table */
    WHERE 
        1=1 /* Start of dynamic WHERE clause, no initial restriction */
";
if (!empty($filter_user_id)) {
    $query .= " AND br.user_id = '$filter_user_id'";
}
$query .= " ORDER BY br.borrow_date ASC";
$borrowed_records = $conn->query($query);
$users_result = $conn->query("SELECT borrower_id, name FROM borrower ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Book History & Tracking</title>
    <link rel="stylesheet" href="assets/functions.css"> 
    <style>
        .badge-returned {
            background-color: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            text-transform: uppercase;
        }
        .badge-borrowed {
            background-color: #f97316;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            text-transform: uppercase;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
            background: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .filter-form label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="main-content">
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
                <a href="registerBorrower.php">
                    <span class="navbar-icon"></span> Register Borrower
                </a>
                <a href="borrowedRecords.php" class="active">
                    <span class="navbar-icon"></span> Borrowed Records
                </a>
            </div>
        </div>
        
        <div class="container">
            <div class="card">
                <h1>Borrowing History</h1>
                <form method="get" class="filter-form">
                    <div style="flex-grow: 1;">
                        <label for="user_id">Filter by Borrower:</label>
                        <select name="user_id" id="user_id">
                            <option value="">-- All Borrowers --</option>
                            <?php 
                            if ($users_result && $users_result->num_rows > 0) {
                                $users_result->data_seek(0);
                                while ($user = $users_result->fetch_assoc()) {
                                    $selected = ($user['borrower_id'] == $filter_user_id) ? 'selected' : '';
                                    echo "<option value=\"{$user['borrower_id']}\" {$selected}>" . htmlspecialchars($user['name']) . " (ID: {$user['borrower_id']})" . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit">Apply Filter</button>
                    <a href="borrowedRecords.php" class="btn">Clear Filter</a>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Title (ISBN)</th>
                            <th>Borrowed By</th>
                            <th>User ID</th>
                            <th>Date Borrowed</th>
                            <th>Date Returned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    if ($borrowed_records && $borrowed_records->num_rows > 0) {
                        while ($row = $borrowed_records->fetch_assoc()) { 
                            
                            $raw_return_date = trim($row['return_date'] ?? '');

                            $is_outstanding = empty($raw_return_date) || ($raw_return_date === '2999-12-31');

                            if ($is_outstanding) {
                                $return_display = '<span style="color:#888;">—</span>';
                                $status_badge = '<span class="badge badge-borrowed">BORROWED</span>';
                            } else {
                                $return_display = htmlspecialchars($raw_return_date);
                                $status_badge = '<span class="badge badge-returned">RETURNED</span>';
                            }
                    ?>
                        <tr> 
                            <td>
                                <strong><?= htmlspecialchars((string)($row['title'] ?? 'N/A')) ?></strong> 
                                <br><small style="color: #888;">ISBN: <?= htmlspecialchars((string)($row['isbn'] ?? 'N/A')) ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars((string)($row['user_name'] ?? 'Unknown User')) ?>
                            </td>
                            <td><?= htmlspecialchars((string)($row['user_id'] ?? 'N/A')) ?></td>
                            <td><?= htmlspecialchars((string)($row['borrow_date'] ?? 'N/A')) ?></td>
                            <td><?= $return_display ?></td> 
                            <td><?= $status_badge ?></td> 
                        </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="6" style="text-align: center; color: #777; padding: 30px;">';
                        if (!empty($filter_user_id)) {
                            echo "No borrowing history found for that Borrower ID.";
                        } else {
                            echo 'No borrowing records exist yet.';
                        }
                        echo '</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
