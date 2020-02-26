<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 7:51 PM
 */

namespace projectivemotion\flightconnections;


class DestinationCollection
{
    /**
     * @var Airport[]
     */
    public $tiledata;
    public $airports;

    public function __construct($airports)
    {
        $this->tiledata = $airports;
    }

    public static function buildFlights($tiledata)
    {
        $points = $airport->getPoints();
//        var_dump($points);
//        var_dump($airport->id);
//        var_dump($this->tiledata);
        $available_destinations = array_filter($tiledata, function (Airport $e) use ($points) {
            return in_array($e->id, $points, true);
        });

        $flights = [];
        foreach($tiledata as $dest){
            $fd = new FlightData();
            $fd->from = $airport;
            $fd->to = $dest;

            $flights[] = $fd;
        }

        return $flights;
    }

}