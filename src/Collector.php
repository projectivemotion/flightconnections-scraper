<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 9:12 PM
 */

namespace projectivemotion\flightconnections;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Collector
{
    protected $routes;
    protected $rroutes;

    public function __construct()
    {
        $this->routes = [];
        $this->rroutes = [];
    }

    public function getFlightNumberData(Scraper $t, Airport $airport)
    {
        $destinations = $t->fetchDestinations($airport);

        $c = $this;

        foreach($destinations as $fd){
            $promise = $t->getRoutes($fd);
            $promise->then(
                function (\Psr\Http\Message\ResponseInterface $res) use ($c, $fd) {
                    //        echo $res->getStatusCode() . "\n";
                    //        echo $res->getBody();
                    $c->collectRoutes($fd, $res->getBody());
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
        foreach($this->genRouteQueries() as $fd_route){

            // query return flights..
            if($fd_route->direction != 'from')    continue;

            $availflights = $t->getRouteFlightInformation($fd_route);
            $availreturnflights = $t->getRouteFlightInformation($fd_route->reverse());

//            $Airline = $availflights->airline;
            $dest_id =  $fd_route->fd->to->id;

            $collect_estfn[$dest_id] = [$fd_route, self::FlightNumbersOnly($availflights), self::FlightNumbersOnly($availreturnflights)];

//    var_export($response);
        }

        return $collect_estfn;
    }

    public static function asTSV($airport, $collect_estfn, Scraper $t)
    {
        header("Content-Type: text/tsv", true);
        header("Content-Disposition: attachment; filename=\"$airport->code.tsv\"", true);

        printf(join("\t", [
                'Airline',
                'Departure',
                'Destination',
                'Code',
                'Code',
                'Outbound',
                'Inbound'
            ]) . "\n");
        $afname = preg_replace('#\(.+\)$#', '', $airport->name);

        foreach($collect_estfn as $did => $flights){
            $dairport = $t->findAirportById($did, true);

            $airline = $flights[1]->airline;
            $departures = $flights[1]->flights;
            $returns = $flights[2]->flights;

            $adname = preg_replace('#\(.+\)$#', '', $dairport->name);

            printf("%s\t%s\t%s\t%s\t%s\t%s\t%s\n", $airline, $afname, $adname, $airport->code, $dairport->code,
                implode(',', $departures),  implode(',', $returns));
        }
    }

    public function asXLS(Airport $airport, Scraper $t)
//    public static function asXLS($airport, $collect_estfn, $t)
    {
        $collect_estfn = $this->getFlightNumberData($t, $airport);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$airport->code.xls\"", true);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $h = [
            'Airline',
            'Departure',
            'Destination',
            'Code',
            'Code',
            'Outbound',
            'Inbound'
        ];
        $printrow = function ($h, $row, $sheete){
            for($i = 0; $i < count($h); $i++)
                $sheete->setCellValueByColumnAndRow($i+1, $row, $h[$i]);
        };

        $printrow($h, 1, $sheet);

        $afname = preg_replace('#\(.+\)$#', '', $airport->name);

        $rn = 2;
        foreach($collect_estfn as $did => $flights){
            $dairport = $t->findAirportById($did, false);

            $airline = $flights[1]->airline;
            $departures = $flights[1]->flights;
            $returns = $flights[2]->flights;

            $adname = preg_replace('#\(.+\)$#', '', $dairport->name);

            $rdata = [$airline, $afname, $adname, $airport->code, $dairport->code,
                implode(',', $departures),  implode(',', $returns)];
            $printrow($rdata, $rn++, $sheet);
        }

//        $sheet->setCellValueByColumnAndRow(1,1, 'Hello World !');
        $writer = new Xls($spreadsheet);
        $writer->save('php://output');
        return $writer;
//
//
//        printf(join("\t", [
//                'Airline',
//                'Departure',
//                'Destination',
//                'Code',
//                'Code',
//                'Outbound',
//                'Inbound'
//            ]) . "\n");
//        $afname = preg_replace('#\(.+\)$#', '', $airport->name);
//
//        foreach($collect_estfn as $did => $flights){
//            $dairport = $t->findAirportById($did, true);
//
//            $airline = $flights[1]->airline;
//            $departures = $flights[1]->flights;
//            $returns = $flights[2]->flights;
//
//            $adname = preg_replace('#\(.+\)$#', '', $dairport->name);
//
//            printf("%s\t%s\t%s\t%s\t%s\t%s\t%s\n", $airline, $afname, $adname, $airport->code, $dairport->code,
//                implode(',', $departures),  implode(',', $returns));
//        }
    }

    public static function FlightNumbersOnly($routeflights)
    {
        $routeflights->flights = array_unique(array_map(function ($e){
            return \projectivemotion\flightconnections\Collector::CleanFN($e->flightnumber);
        }, $routeflights->flights));
        return $routeflights;
    }

    public function collectRoutes(FlightData $fd, $respstr, $direction = 'from')
    {
        $routedata = \GuzzleHttp\json_decode($respstr, false)->data;
        $this->routes[$fd->getHash()] = [$fd, $routedata, $direction];
    }

    /**
     * @return \Generator|Route[]
     */
    public function genRouteQueries()
    {
        foreach($this->routes as $multiroute){
            foreach($multiroute[1] as $vroute){
                $route = new Route();
                $route->direction = $multiroute[2];
                $route->fd = $multiroute[0];
                $route->rid = $vroute->route[0];
                yield $route;
//                yield [$multiroute[0], $route];
            }
        }
    }

    public static function FormatFlight($airlineName, $iflight)
    {
        $weekday = implode(',', array_filter([
            $iflight->su == 1 ? 'Su' : null,
            $iflight->mo == 1 ? 'Mo' : null,
            $iflight->tu == 1 ? 'Tu' : null,
            $iflight->we == 1 ? 'We' : null,
            $iflight->th == 1 ? 'Th' : null,
            $iflight->fr == 1 ? 'Fr' : null,
            $iflight->sa == 1 ? 'Sa' : null
        ]));
        return sprintf("%s\t%s\t%s\t%s\t%s\t%s\n", $airlineName,
                preg_replace('#\s{2,}#', '', $iflight->flightnumber),
            $iflight->deptime,
            $iflight->destime,
            $iflight->datefrom . '-' . $iflight->dateto,
            $weekday);
    }

    public static function CleanFN($fn)
    {
        return preg_replace('#\s{2,}#', '', $fn);
    }
}