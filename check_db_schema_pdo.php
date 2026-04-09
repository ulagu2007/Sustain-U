<?php
try {
    $dsn = "mysql:host=localhost;dbname=sustain_u;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Field: " . $row['Field'] . " - Type: " . $row['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
