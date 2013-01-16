<?php
$pdo = new PDO('mysql:host=localhost;dbname=geospatial', 'root', '1234');
$pdo->query('DELETE FROM polygons');

$lines = file('polygons.txt');
$i = 1;

foreach ($lines as $polygon) {
    $query = $pdo->prepare("INSERT INTO polygons (bounds) VALUES (GeomFromText(:polygon))");
    $query->bindValue('polygon', trim($polygon));
    $query->execute();

    if ($i % 1000 == 0) {
        echo '.';
    }

    $i++;
}

echo "\n";
