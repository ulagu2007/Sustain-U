<?php
$conn = new mysqli('localhost', 'root', '', 'sustain_u');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$result = $conn->query("DESCRIBE users");
while($row = $result->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " - Type: " . $row['Type'] . "\n";
}
$conn->close();
?>
