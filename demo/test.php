<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    $t = new \projectivemotion\flightconnections\Scraper();
    $c = new \projectivemotion\flightconnections\Collector();
    $t->fetchEuropeAirports();

    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport('SKG'));
//    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport('NNM'));

    $flights = $t->fetchDestinations($airport);

    foreach($flights as $fd){
        $promise = $t->getRoutes($fd);
        $promise->then(
            function (\Psr\Http\Message\ResponseInterface $res) use ($c, $fd) {
        //        echo $res->getStatusCode() . "\n";
                $c->collectRoutes($fd, $res->getBody());
        //        echo $res->getBody();
            },
            function (\GuzzleHttp\Exception\RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );
        $promise->wait();
//        break;
    }

$collect_estfn = [];
foreach($c->genRouteQueries() as $fd_route){
    $availflights = $t->getRouteFlightInformation($fd_route[0], $fd_route[1]->route[0]);

    $Airline = $availflights->airline;
    $dest_id =  $fd_route[0]->to->id;

    if(!isset($collect_estfn[$dest_id]))
        $collect_estfn[$dest_id] = [];

    foreach($availflights->flights as $flight){
        $fn = \projectivemotion\flightconnections\Collector::CleanFN($flight->flightnumber);

        if(!isset($collect_estfn[$dest_id][$fn]))
            $collect_estfn[$dest_id][$fn] = [$Airline, $flight];

//        echo sprintf("%s\t%s\t%s\t%s\t", $airport->name, $airport->code, $fd_route[0]->to->name, $fd_route[0]->to->code);
//        echo \projectivemotion\flightconnections\Collector::FormatFlight($Airline, $flight);
    }
//    var_export($response);
}

//var_export($collect_estfn);

foreach($collect_estfn as $aid => $flights){
    $iairport = $t->findAirportById($aid);

    $keys = array_keys($flights);
    $airline = $flights[$keys[0]][0];

    $fns = implode(',', array_map(function ($k) use($flights){
        return \projectivemotion\flightconnections\Collector::CleanFN($flights[$k][1]->flightnumber);
    }, $keys));

    printf("%s\t%s\t%s\t%s\t%s\t%s\n", $airport->name, $airport->code, $airline, $iairport->name, $iairport->code, $fns);
}
