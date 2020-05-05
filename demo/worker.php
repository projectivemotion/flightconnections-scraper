<?php

require_once __DIR__ . '/../vendor/autoload.php';

use \projectivemotion\flightconnections;


$client = new \GearmanClient();
$client->addServer('gearman');

$worker = new \GearmanWorker();
$worker->addServer('gearman');

$redis = new \Redis();
$redis->connect('redis');

// initialize
$t = new \projectivemotion\flightconnections\Scraper();

$str = $redis->get("AIRPORTS");
if($str){
    echo "Loaded Airports from Cache..\n";
    $t->setAirports(unserialize($str));
}else {
    echo "Loading Airports..\n";
    $airports = $t->fetchEuropeAirports();
    $redis->set("AIRPORTS", serialize($airports), ['EX' => 3600]);   // 5 min
}

$worker->addFunction("exec",
    function ($job) use ($client, $t, $redis) {

    $airportstr = $job->workload();
    echo "Airport: $airportstr\n";

    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport($airportstr));
       $c  = new \projectivemotion\flightconnections\Collector();
    $collect_estfn = $c->getFlightNumberData($t, $airport);

    //    $collect_estfn = '99';

    $collect_estfn = '99';

    $client->doBackground('report-data-' . $airportstr, serialize($collect_estfn));

    $redis->lPush('ready-jobs', $airportstr);
//    $client->addTask('ready-jobs', $airportstr);
    return '';
});

echo "Waiting for work..";
while ($worker->work());

echo "Bye..";
