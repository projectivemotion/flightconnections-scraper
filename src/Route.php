<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/26/20
 * Time: 12:40 AM
 */

namespace projectivemotion\flightconnections;


class Route
{
    /**
     * @var FlightData
     */
    public $fd;
    public $direction;
    public $rid;

    public function reverse()
    {
        $route = new Route();
        $route->fd = $this->fd->returnFD();
        $route->rid = $this->rid;
        $route->direction = $this->direction == 'to' ? 'from' : 'to';
        return $route;
    }
}