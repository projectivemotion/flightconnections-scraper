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

//    public static function CollectFlightNumbers(Scraper)
//    {
//
//    }

}