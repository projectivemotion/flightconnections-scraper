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
//https://www.flightconnections.com/ro113_326.json?v=826&f=no0&direction=from&exc=&ids=
}