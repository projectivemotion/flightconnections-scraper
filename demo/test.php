<?php
    require_once __DIR__ . '/../vendor/autoload.php';

    $t = new \projectivemotion\flightconnections\Scraper();
    $c = new \projectivemotion\flightconnections\Collector();
    $t->fetchEuropeAirports();

    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport('SKG'));
//    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport('NNM'));

    $flights = $t->fetchDepartures($airport);

    foreach($flights as $fd){
        $promise = $t->getFlights($fd);
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
        break;
    }

foreach($c->genRouteQueries() as $fd_route){
    $availflights = $t->validify($fd_route[0], $fd_route[1]->route[0]);

    $Airline = $availflights->airline;
    foreach($availflights->flights as $flight){
        echo sprintf("%s\t%s\t%s\t%s\t", $airport->name, $airport->code, $fd_route[0]->to->name, $fd_route[0]->to->code);
        echo \projectivemotion\flightconnections\Collector::FormatFlight($Airline, $flight);
    }
//    var_export($response);
}
