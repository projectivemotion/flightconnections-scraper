<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 6:23 PM
 */

namespace projectivemotion\flightconnections;


class Airport
{
    public $code;
    public $id;
    public $name;
    public $state;

    protected $pointdata;

    public function __construct($code, $id = null, $name = null, $state = 0)
    {
        $this->id = $id;
        $this->state= $state;
        $this->name = $name;


        $this->code = $code;

        if($code && preg_match('#\((...)\)$#', $code, $m))
            $this->code = $m[1];
    }

    public function setPointdata($pointdata)
    {
        $this->pointdata = $pointdata;
    }

    public function getPoints()
    {
        return $this->pointdata->pts; //ids of airports
    }
}