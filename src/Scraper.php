<?php
/**
 * Created by PhpStorm.
 * User: eye
 * Date: 2/25/20
 * Time: 4:41 PM
 */

namespace projectivemotion\flightconnections;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class Scraper
{
    protected $client;
    protected $airports;

    public function __construct()
    {
        $this->initClient();
    }

    public function fetchAirport(Airport $airport)
    {
        if(!$airport->code)
            throw new Exception('Must supply airport code.');

        $response = $this->client->request('GET', '/autocompl_airport.php?lang=en&term=' . $airport->code);

        $arr = \GuzzleHttp\json_decode($response->getBody(), false);

        if(count($arr) !== 1)
            throw new Exception('Expected 1 result got: ' . count($arr));

        $airport->name = $arr[0]->airport;
        $airport->id = $arr[0]->id;

        return $airport;
    }

    public function initClient(){
        $this->client = new Client(['base_uri' => 'https://www.flightconnections.com/']);
        // 113 = got
//        $this->client->request('GET', '/ro113.json?v=826&f=no0&direction=from&exc=&ids=');
    }

    public function findAirportById($id, $fail = false)
    {
        if(isset($this->airports[$id]))
            return $this->airports[$id];

        if($fail)
            throw new Exception('Unknown ID: ' . $id);

        return new Airport(null, $id);
    }

    /**
     * @param Airport $airport
     * @return FlightData[]
     */
    public function fetchDestinations(Airport $airport)
    {
        $response = $this->client->request('GET', '/ro' . $airport->id . '.json?v=&f=no0&direction=from&exc=&ids=');
        $pointdata = \GuzzleHttp\json_decode($response->getBody(), false);
        $airport->setPointdata($pointdata);

        $points = $airport->getPoints();
        $f = [];
        foreach ($points as $aid) {
            if ($aid == $airport->id) continue;  // skip
            $fd = new FlightData();
            $fd->from = $airport;
            $fd->to = $this->findAirportById($aid);

            $f[] = $fd;
        }
        return $f;
    }

    public function fetchEuropeAirports(){
//        $zoom = [4, 6,11,2,7];  // zoom 4 , 6-11 x 2-7 y
        $zoom = [3, 1,6,1,4];  // zoom 3, 1-6 x, 1-4 y
        $promises = [];
        // european map ranges 6..11 on x and 2..7 on y on zoom=4

        for($x = $zoom[1]; $x <= $zoom[2]; $x++){
            for($y = $zoom[3]; $y <= $zoom[4] ; $y++){
                $promises[] = $this->client->getAsync('/tiles/en/' . $zoom[0] . '/' . $x. '-' . $y . '.json');
            }
        }

        $responses = Promise\settle($promises)->wait();

        $airports = [];
        foreach($responses as $r){
            // r has state and value (response)

            if($r['state'] != 'fulfilled') continue;    // something happened
//            var_export($r);
            $str = (string) $r['value']->getBody();
            if($str == '0'){        // sometimes this happens idk
                continue;
            }
            $geom = \GuzzleHttp\json_decode($str, false)->geom;

            foreach($geom as $airport){
                $airports[$airport->c] = new Airport($airport->a, $airport->c, $airport->a, $airport->s);
            }
        }

        $this->airports = $airports;
        return $airports;
    }

    /**
     * @param FlightData $fd
     * @param string $direction from (outbound) or to (return)
     * @return mixed
     */
    public function getRoutes(FlightData $fd, $direction = 'from')
    {
        $url = "/ro{$fd->from->id}_{$fd->to->id}.json?v=&f=no0&direction={$direction}&exc=&ids=";
        $promise = $this->client->getAsync($url);

        return $promise;
    }

    /**
     * Returns a stdclass with airline (str) and flights (arr)
     * The flights value contains full flight information includign:
     * flight code, weekdays, time of departure and time of arrival.
     *
     * @param FlightData $fd
     * @param $routeid
     * @return mixed
     */
    public function getRouteFlightInformation(Route $route)
    {
        $fd = $route->fd;
        $post = [
            'dep' => $fd->from->id,
            'des' => $fd->to->id,
            'id' => $route->rid,
            'startDate' => date('Y'),
            'endDate' => date('Y')+1
        ];

        $response = $this->client->request('POST', '/validity.php', [
            'form_params' => $post
        ]);

        return \GuzzleHttp\json_decode($response->getBody(), false);
    }

    public function getReturnRouteFlightInformation(FlightData $fd)
    {

    }
}