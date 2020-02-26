<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 9:12 PM
 */

namespace projectivemotion\flightconnections;


class Collector
{
    protected $routes;

    public function __construct()
    {
        $this->routes = [];
    }

    public function collectRoutes(FlightData $fd, $respstr)
    {
        $routedata = \GuzzleHttp\json_decode($respstr, false)->data;
        $this->routes[$fd->getHash()] = [$fd, $routedata];
        //route id: routedata[0]->route[0]
        //route id: routedata[0]->route[0]
    }

    public function genRouteQueries()
    {
        foreach($this->routes as $multiroute){
            foreach($multiroute[1] as $route){
                yield [$multiroute[0], $route];
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
}