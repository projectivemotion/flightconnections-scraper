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
use GuzzleHttp\Psr7\Request;
use projectivemotion\PhpScraperTools\CacheScraper;

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

//        $this->client->request('GET', '/ro113.json?v=826&f=no0&direction=from&exc=&ids=');
    }



    public function initClient(){
        $this->client = new Client(['base_uri' => 'https://www.flightconnections.com/']);
        // 113 = got
//        $this->client->request('GET', '/ro113.json?v=826&f=no0&direction=from&exc=&ids=');
    }

    public function findAirportById($id)
    {
        if(isset($this->airports[$id]))
            return $this->airports[$id];

        return new Airport(null, $id);
    }

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
        $zoom = 4;
        $promises = [];
        // european map ranges 6..11 on x and 2..7 on y on zoom=4

        for($x = 6; $x <= 11; $x++){
            for($y = 2; $y <= 7 ; $y++){
                $promises[] = $this->client->getAsync('/tiles/en/' . $zoom . '/' . $x. '-' . $y . '.json');
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

    public function getRoutes(FlightData $fd)
    {
        $url = "/ro{$fd->from->id}_{$fd->to->id}.json?v=&f=no0&direction=from&exc=&ids=";
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
    public function getRouteFlightInformation(FlightData $fd, $routeid)
    {
        $post = [
            'dep' => $fd->from->id,
            'des' => $fd->to->id,
            'id' => $routeid,
            'startDate' => date('Y'),
            'endDate' => date('Y')+1
        ];

        $response = $this->client->request('POST', '/validity.php', [
            'form_params' => $post
        ]);

        return \GuzzleHttp\json_decode($response->getBody(), false);
//
//        curl 'https://www.flightconnections.com/validity.php' -H 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:73.0) Gecko/20100101 Firefox/73.0' -H 'Accept: */*' -H 'Accept-Language: en-US,en;q=0.5'
//    --compressed -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'X-Requested-With: XMLHttpRequest' -H 'Origin: https://www.flightconnections.com'
//    -H 'Connection: keep-alive' -H 'Referer: https://www.flightconnections.com/flights-from-gothenburg-got'
//    -H 'Cookie: waldo-pbjs-pubCommonId=336109d1-39c0-4d0c-8e0f-f9e7ce7426bb; _ga=GA1.2.1235278324.1582671055; _gid=GA1.2.1584192879.1582671055; __gads=ID=5727f2c8f83b719c:T=1582671055:S=ALNI_MaoD17qKIvjkqi09pLrwXZSbpeVZg; waldo-pbjs-unifiedid=%7B%22TDID%22%3A%22e569e6d7-0f34-465a-b9ff-a7a59a7ced59%22%2C%22TDID_LOOKUP%22%3A%22FALSE%22%2C%22TDID_CREATED_AT%22%3A%222020-02-25T22%3A50%3A58%22%7D; intent_media_prefs=; im_puid=2f791d2d-2a0d-437e-ad08-6fe4d974fac8; _hjid=bb62a977-2892-4fea-940c-5b74610a626c; _hjIncludedInSample=1; _hjShownFeedbackMessage=true; waldo_country=MX; waldo_continent=NA; waldo_region=28; im_snid=59802a59-07e2-43e8-ab51-fcb2ca297f0b; _gat=1'
//    --data 'dep=113&des=45&id=39&startDate=2020&endDate=2021'
    }

}