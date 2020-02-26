<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 8:07 PM
 */

namespace projectivemotion\flightconnections;


class FlightData
{
    /**
     * @var Airport
     */
    public $from;
    /**
     * @var Airport
     */
    public $to;


    public function getHash(){
        return $this->from->id . '_' . $this->to->id;
    }

    public function returnFD(){
        $fd = new FlightData();
        $fd->from = $this->to;
        $fd->to = $this->from;
        return $fd;
    }
//https://www.flightconnections.com/ro113_326.json?v=826&f=no0&direction=from&exc=&ids=
}