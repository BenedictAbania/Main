<?php

include 'database.php';

$message = ""; 
$id = $_GET['id'] ?? null;
$book = null; 

if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
    header("Location: viewCatalogue.php?status=invalid_id");
    exit;
} else {
    $stmt_select = $conn->prepare("SELECT id, title, author, publication_year, isbn, status FROM books WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $book = $result->fetch_assoc();
    $stmt_select->close();
    if (!$book) {
        header("Location: viewCatalogue.php?status=not_found");
        exit;
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST" && $book){
    $action = $_POST['action'] ?? ''; 
    
    if ($action === 'update') {
        $title = trim($_POST['title']);
        $author = trim($_POST['author']);
        $year = (int)($_POST['year'] ?? 0);
        $isbn = trim($_POST['isbn']);
        $status = $_POST['status'] === 'Borrowed' ? 'Borrowed' : 'Available'; 

        $min_year = 1450;
        $max_year = date("Y") + 5; 

        if ($year < $min_year || $year > $max_year) {
            $message = "<p style='color:red;'>❌ Error: Invalid Publication Year. Must be between {$min_year} and {$max_year}.</p>";
        } elseif (empty($title) || empty($author) || empty($isbn)) {
             $message = "<p style='color:red;'>❌ Error: All text fields are required.</p>";
        } else {
            $stmt_update = $conn->prepare("UPDATE books SET title=?, author=?, publication_year=?, isbn=?, status=? WHERE id=?");
            $stmt_update->bind_param("ssissi", $title, $author, $year, $isbn, $status, $id);
            
            if ($stmt_update->execute()) {
                $message = "<p style='color:green;'>✅ Book updated successfully!</p>";
                $book['title'] = $title; $book['author'] = $author; $book['publication_year'] = $year;
                $book['isbn'] = $isbn; $book['status'] = $status;
            } else {
                $message = "<p style='color:red;'>❌ Error updating book: " . htmlspecialchars($conn->error) . "</p>";
            }
            $stmt_update->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book: <?= htmlspecialchars($book['title'] ?? 'N/A') ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #eef2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background-color: #ffffff; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); width: 100%; max-width: 450px; }
        h2 { color: #2c3e50; margin-bottom: 25px; text-align: center; border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; }
        label { display: block; margin-top: 15px; margin-bottom: 7px; font-weight: 600; color: #34495e; }
        input[type="text"], input[type="number"], select { 
            width: 100%; padding: 12px; margin-bottom: 15px; 
            border: 1px solid #bdc3c7; border-radius: 8px; 
            box-sizing: border-box; font-size: 16px; 
        }
        .btn { padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px; transition: background-color 0.3s, transform 0.1s; font-weight: bold; }
        .btn-save { background-color: #2ecc71; color: white; }
        .btn-save:hover { background-color: #27ae60; }
        .message-container { margin-bottom: 20px; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600; }
        p[style*='color:red'] { color: #c0392b; background-color: #fcecec; border: 1px solid #e74c3c; padding: 10px; border-radius: 5px; }
        p[style*='color:green'] { color: #27ae60; background-color: #e9f7ef; border: 1px solid #2ecc71; padding: 10px; border-radius: 5px; }
        .back-link { display: block; text-align: center; margin-top: 25px; color: #3498db; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit Book Details (ID: <?= htmlspecialchars($book['id'] ?? 'N/A') ?>)</h2>

    <div class="message-container">
        <?php if (!empty($message)) echo $message; ?>
    </div>

    <form method="post">
        <input type="hidden" name="action" value="update">
        
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>

        <label for="author">Author:</label>
        <input type="text" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>

        <label for="year">Publication Year:</label>
        <input type="number" id="year" name="year" value="<?= htmlspecialchars($book['publication_year']) ?>" required min="1450" max="<?= date("Y") + 5 ?>">

        <label for="isbn">ISBN:</label>
        <input type="text" id="isbn" name="isbn" value="<?= htmlspecialchars($book['isbn']) ?>" required>

        <label for="status">Status:</label>
        <select id="status" name="status">
           <option value="Available" <?= $book['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
           <option value="Borrowed" <?= $book['status'] == 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
        </select>

        <button type="submit" class="btn btn-save">💾 Save Changes</button>
    </form>
    
    <a href="viewCatalogue.php" class="back-link">← Back to Book Catalog</a>
</div>

</body>
</html>
