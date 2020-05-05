<?php
require_once __DIR__ . '/../vendor/autoload.php';

//    $depart = 'GOT';
$depart = '';

$redis = new \Redis();
$redis->connect('redis');
$list = $redis->lrange('ready-jobs', 0, -1);


function checkresults($from, $redis)
{
    $worker = new \GearmanWorker();
    $worker->addServer('gearman');
    $worker->setTimeout(2000);

    $result = false;
    $worker->addFunction('report-data-' . $from, function ($job) use ($from, &$result, $redis) {
        $data = unserialize($job->workload());

//            ecHO "Got Data $from data: ";
//            var_export($data);
        $t = new \projectivemotion\flightconnections\Scraper();
        $depart = $from;
        $str = $redis->get("AIRPORTS");
        if ($str) {
            $t->setAirports(unserialize($str));
        } else {
            $airports = $t->fetchEuropeAirports();
            $redis->set("AIRPORTS", serialize($airports), ['EX' => 3600]);   // 5 min
        }

        $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport($depart));
        $c = new \projectivemotion\flightconnections\Collector();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$from.xls\"", true);
        $c->asXLS($airport, $t)->save('php://output');
//            ecHO "Got Data $from data: ";
        //           var_export($data);

        $result = true;
    });

    $worker->work();
    return $result;
}

if (isset($_GET['from'])) {
    $depart = strtoupper($_GET['from']);

    // if($nohasresults){
    if (isset($_GET['start'])) {
        $server = new \GearmanClient();
        $server->addServer('gearman');

        $server->doBackground('exec', $depart);
        echo $depart . ' Processing..';
    } else
        checkresults($depart, $redis);
}

if (!$depart) {
    $redis->del('ready-jobs');

//        var_export($list);

    header("Content-type:", "text/json");
    echo \json_encode([
        'list' => $list
    ]);
}


//    $airport = $t->fetchAirport(new \projectivemotion\flightconnections\Airport($depart));
//
//    // print xls
//    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//    header("Content-Disposition: attachment; filename=\"$airport->code.xls\"", true);
//    $c->asXLS($airport, $t)->save('php://output');
