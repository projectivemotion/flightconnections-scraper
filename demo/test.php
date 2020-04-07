<?php
    require_once __DIR__ . '/../vendor/autoload.php';

//    $depart = 'GOT';
    $depart = '';

    function checkresults($from){
        $worker = new \GearmanWorker();
        $worker->addServer('gearman');
        $worker->setTimeout(2000);

        $result = false;
        $worker->addFunction('report-data-' . $from, function ($job) use ($from, &$result) {
            $data = unserialize($job->workload());

            ecHO "Got Data $from data: ";
            var_export($data);
            $result = true;
        });

        $worker->work();
        return $result;
    }

    if(isset($_GET['from']))
    {
        $depart = $_GET['from'];
        checkresults($_GET['from']);
    }

    // load results
    if(isset($_POST['from'])) {
        $depart = strtoupper($_POST['from']);
        if(!checkresults($depart)){
            $server = new \GearmanClient();
            $server->addServer('gearman');

            $server->doBackground('exec', $depart);
            echo 'Processing..';
        }
    }

    if(!$depart){
        $redis = new \Redis();
        $redis->connect('redis');

        $list = $redis->lrange('ready-jobs', 0, -1);
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
