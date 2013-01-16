<?php

// How many queries will run for each benchmark.
$queriesToRun = 100;

/**
 * Make a random point based on limits defined insede the function.
 *
 * @return array [latitude, longitude]
 */
function makeRandomPoint()
{
    $minLatitude = -4677000;
    $maxLatitude = -4643800;
    $minLongitude = -2470650;
    $maxLongitude = -2349470;
    $latitude = mt_rand($minLatitude, $maxLatitude) / 100000;
    $longitude = mt_rand($minLongitude, $maxLongitude) / 100000;

    return array($latitude, $longitude);
}

function calculateAverageTime($times) {
    $sum = 0;

    foreach ($times as $time) {
        $sum += $time;
    }

    return $sum / count($times);
}

$benchmarks = array();
$points = array();

for ($i = 0; $i < $queriesToRun; $i++) {
    $points[] = makeRandomPoint();
}

/**
 * Solr
 */
$benchmarks[] = function () use ($points) {
    echo "Starting Solr benchmark...\n";

    $found = 0;
    $notFound = 0;
    $times = array();

    $solr =  new SolrClient(array('hostname' => 'localhost', 'port' => 8983, 'path' => 'solr/collection1'));

    foreach ($points as $point) {
        $point = "{$point[0]} {$point[1]}";
        $startTime = microtime(true);
        $query = new SolrQuery("bounds:\"Intersects({$point})\"");
        $response = $solr->query($query);
        $result = $response->getResponse();
        $times[] = microtime(true) - $startTime;
        ($result->response->numFound > 0) ? $found++ : $notFound++;
    }

    $averageTime = calculateAverageTime($times);

    return array($found, $notFound, $averageTime);
};

/**
 * Postgres
 */
$benchmarks[] = function () use ($points) {
    echo "Starting Postgres benchmark...\n";

    $found = 0;
    $notFound = 0;
    $times = array();

    $pdo = new PDO('pgsql:host=localhost;dbname=geospatial;user=fernando');

    foreach ($points as $point) {
        $point = "{$point[0]} {$point[1]}";
        $startTime = microtime(true);
        $query = $pdo->prepare("SELECT * FROM polygons WHERE ST_Intersects(ST_GeomFromText('POINT({$point})'), bounds)");
        $query->execute();
        $times[] = microtime(true) - $startTime;
        ($query->rowCount() > 0) ? $found++ : $notFound++;
    }

    $averageTime = calculateAverageTime($times);

    return array($found, $notFound, $averageTime);
};

/**
 * MySQL
 */
$benchmarks[] = function () use ($points) {
    echo "Starting MySQL benchmark...\n";

    $found = 0;
    $notFound = 0;
    $times = array();

    $pdo = new PDO('mysql:host=localhost;dbname=geospatial', 'root', '1234');

    foreach ($points as $point) {
        $point = "{$point[0]} {$point[1]}";
        $startTime = microtime(true);
        $query = $pdo->prepare("SELECT * FROM polygons WHERE GISWithin(GeomFromText('POINT({$point})'), bounds)");
        $query->execute();
        $times[] = microtime(true) - $startTime;
        ($query->rowCount() > 0) ? $found++ : $notFound++;
    }

    $averageTime = calculateAverageTime($times);

    return array($found, $notFound, $averageTime);
};

foreach ($benchmarks as $benchmark) {
    $startTime = microtime(true);
    $result = $benchmark();
    $totalTime = microtime(true) - $startTime;
    echo "Queries: {$queriesToRun}, Found: {$result[0]}, Not Found: {$result[1]}, Time taken: {$totalTime} seconds, Average time per query: {$result[2]} seconds\n\n";
}