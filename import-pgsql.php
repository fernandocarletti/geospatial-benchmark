<?php
$pdo = new PDO('pgsql:host=localhost;dbname=geospatial;user=fernando');
$pdo->query('DELETE FROM polygons');

$lines = file('polygons.txt');
$i = 1;

foreach ($lines as $polygon) {
    $query = $pdo->prepare("INSERT INTO polygons (bounds) VALUES (ST_GeomFromText(:polygon))");
    $query->bindValue('polygon', trim($polygon));
    $query->execute();

    if ($i % 1000 == 0) {
        echo '.';
    }

    $i++;
}

echo "\n";