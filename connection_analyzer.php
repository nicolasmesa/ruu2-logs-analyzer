<?php

$filename = "logs/port_logs_all.log";
date_default_timezone_set('America/New_York');

function parse_log_file($filename, &$closed_connections, $std_dev = false, $index = false, &$analysis = false) {
    $handle = fopen($filename, "r");

    if (!$handle) {
        return false;
    }

    $close_unopen_conn_count = 0;
    $open_connections = array();

    while (($line = fgets($handle)) !== false) {
        $line2 = str_replace("\n", "", $line);

        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line2);

        /*
         * The host system is always the host IP. This is why this key works fine
         */
        $key = "$src_ip,$dst_ip,$src_port,$dst_port";

        if ($action == 'new') {
            if ($flag == "ESTABLISHED" || $flag == 'SYN_SENT') {

                if (!isset($open_connections[$key])) {
                    $open_connections[$key] = array();
                }

                $new_conn = array(
                    'date_start' => $date,
                    'date_end' => null,
                    'time_start' => $time,
                    'time_end' => null
                );

                /*
                 * Push new connection into the Open connections. When a close
                 * of this connection is seen, the connection will be popped
                 */
                array_push($open_connections[$key], $new_conn);
            }
        } elseif ($action == 'close') {
            if (isset($open_connections[$key]) && count($open_connections[$key])) {
                /*
                 * Pop the previously open connection and record the time between
                 * the open and close
                 */
                $closed_conn = array_pop($open_connections[$key]);
                $closed_conn['date_end'] = $date;
                $closed_conn['time_end'] = $time;

                /*
                 * Saving memory
                 */
                if (!count($open_connections[$key])) {
                    unset($open_connections[$key]);
                }

                /*
                 * Calculate time difference between start and end
                 */
                $date_s = $closed_conn['date_start'] . ' ' . $closed_conn['time_start'];
                $date_start = strtotime($date_s);
                $date_end = strtotime($date . ' ' . $time);

                if ($index && $analysis) {         
                    /*
                     * Too much noise generated by these ports
                     */
                    if (isset($$index) && $dst_port != 443 && $dst_port != 5222) {
                        $key = $$index;
                        $time_diff =  $date_end - $date_start;
                        if (abs($time_diff - $analysis[$key]['mean']) > 2*$analysis[$key]['std_dev']) {
                            echo "$src_ip:$src_port -> $dst_ip:$dst_port\t$time_diff\t" . number_format($analysis[$key]['mean'], 2) . "\t" . number_format($analysis[$key]['std_dev'], 2) . "\n";
                        }else{
                            //echo "relax\n";
                        }
                    }
                } else if ($std_dev) {
                    if (!isset($closed_connections[$key]['std_dev_sum'])) {
                        $closed_connections[$key]['std_dev_sum'] = 0;
                    }

                    if (!isset($closed_connections[$key]['mean'])) {
                        $closed_connections[$key]['mean'] = $closed_connections[$key]['total_time'] / $closed_connections[$key]['num_connections'];
                    }

                    $closed_connections[$key]['std_dev_sum'] += pow(($closed_connections[$key]['mean'] - ($date_end - $date_start)), 2);
                } else {
                    if (!isset($closed_connections[$key])) {
                        $closed_connections[$key] = array(
                            'num_connections' => 0,
                            'total_time' => 0
                        );
                    }

                    $closed_connections[$key]['num_connections'] ++;

                    $closed_connections[$key]['total_time'] += $date_end - $date_start;
                }

                unset($closed_conn);
            } else {
                $close_unopen_conn_count++;
            }
        }
    }

    fclose($handle);
}

$closed_conns = array();

/*
 * Parse once to get information about all connections
 */
parse_log_file($filename, $closed_conns);

/*
 * Parse a second time to get standard deviation and mean
 */
parse_log_file($filename, $closed_conns, true);


/*
 * Merge data for destination port and destination IP
 */
$dst_port_analysis = array();
$src_port_analysis = array();
$dst_ip_analysis = array();
$dst_host_analysis = array();

foreach ($closed_conns as $key => $value) {
    list($src_ip, $dst_ip, $src_port, $dst_port) = explode(',', $key);

    /*
     * Destination port analysis
     */
    if (!isset($dst_port_analysis[$dst_port])) {
        $dst_port_analysis[$dst_port] = array(
            'num_connections' => 0,
            'total_time' => 0,
            'std_dev_sum' => 0
        );
    }

    $dst_port_analysis[$dst_port]['num_connections'] += $value['num_connections'];
    $dst_port_analysis[$dst_port]['total_time'] += $value['total_time'];
    $dst_port_analysis[$dst_port]['std_dev_sum'] += $value['std_dev_sum'];


    /*
     * Source port analysis
     */
    if (!isset($src_port_analysis[$src_port])) {
        $src_port_analysis[$src_port] = array(
            'num_connections' => 0,
            'total_time' => 0,
            'std_dev_sum' => 0
        );
    }

    $src_port_analysis[$src_port]['num_connections'] += $value['num_connections'];
    $src_port_analysis[$src_port]['total_time'] += $value['total_time'];
    $src_port_analysis[$src_port]['std_dev_sum'] += $value['std_dev_sum'];


    /*
     * Destination IP analysis
     */
    if (!isset($dst_ip_analysis[$dst_ip])) {
        $dst_ip_analysis[$dst_ip] = array(
            'num_connections' => 0,
            'total_time' => 0,
            'std_dev_sum' => 0
        );
    }

    $dst_ip_analysis[$dst_ip]['num_connections'] += $value['num_connections'];
    $dst_ip_analysis[$dst_ip]['total_time'] += $value['total_time'];
    $dst_ip_analysis[$dst_ip]['std_dev_sum'] += $value['std_dev_sum'];
}

unset($closed_conns);


/*
 * Calculate new mean and standard deviation for each analysis
 */
foreach ($dst_port_analysis as $key => $value) {
    $dst_port_analysis[$key]['std_dev'] = sqrt($dst_port_analysis[$key]['std_dev_sum']) / $dst_port_analysis[$key]['num_connections'];
    $dst_port_analysis[$key]['mean'] = $dst_port_analysis[$key]['total_time'] / $dst_port_analysis[$key]['num_connections'];

    unset($dst_port_analysis[$key]['std_dev_sum']);
}

foreach ($src_port_analysis as $key => $value) {
    $src_port_analysis[$key]['std_dev'] = sqrt($src_port_analysis[$key]['std_dev_sum']) / $src_port_analysis[$key]['num_connections'];
    $src_port_analysis[$key]['mean'] = $src_port_analysis[$key]['total_time'] / $src_port_analysis[$key]['num_connections'];

    unset($src_port_analysis[$key]['std_dev_sum']);
}

foreach ($dst_ip_analysis as $key => $value) {
    $dst_ip_analysis[$key]['std_dev'] = sqrt($dst_ip_analysis[$key]['std_dev_sum']) / $dst_ip_analysis[$key]['num_connections'];
    $dst_ip_analysis[$key]['mean'] = $dst_ip_analysis[$key]['total_time'] / $dst_ip_analysis[$key]['num_connections'];

    unset($dst_ip_analysis[$key]['std_dev_sum']);
}


$closed_conns = array();

parse_log_file($filename, $closed_conns, false, 'src_port', $src_port_analysis);


//var_dump($dst_port_analysis);

$num_analyzed = 0;
foreach ($dst_port_analysis as $key => $value) {
    $num_analyzed += $value['num_connections'];
    //echo $key . "\t" . number_format($value['total_time'] / $value['num_connections'], 2) . "\t" . $value['num_connections'] . "\n";
}

foreach ($dst_ip_analysis as $key => $value) {
    $host = $key;

    try {
        //$host = gethostbyaddr($key);
    } catch (Exception $ex) {
        $host = $key;
    }

    if ($host != $key) {
        $parts = explode('.', $host);

        if (count($parts) > 1) {
            $host2 = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
        }

        if (!isset($dst_host_analysis[$host2])) {
            $dst_host_analysis[$host2] = 0;
        }
        $dst_host_analysis[$host2] += $value['num_connections'];
    }

    //echo $key . "\t" . $host . "\t" . number_format($value['total_time'] / $value['num_connections'], 2) . "\t" . $value['num_connections'] . "\n";
}

foreach ($dst_host_analysis as $key => $value) {
    echo "$key\t$value\n";
}

echo "\n\nConnections analyzed: $num_analyzed\n";
echo "Number of ports: " . count($dst_port_analysis) . "\n";
echo "Number of IPs: " . count($dst_ip_analysis) . "\n";
echo "Number of hosts: " . count($dst_host_analysis) . "\n";
echo "Unclosed connections: " . count($open_connections) . "\n";
echo "Closed not opened connections: $close_unopen_conn_count \n";
