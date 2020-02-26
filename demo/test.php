<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    $t = new \projectivemotion\flightconnections\Scraper();
    $c = new \projectivemotion\flightconnections\Collector();
    $t->fetchEuropeAirports();

    $depart = 'SKG';

    if(isset($_POST['from']))
        $depart = strtoupper($_POST['from']);

    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport($depart));

    $c->getFlightNumberData($t, $airport);
