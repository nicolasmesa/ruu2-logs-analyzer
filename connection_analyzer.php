<?php

date_default_timezone_set('America/New_York');

$handle = fopen("port_logs_all.log", "r");

if ($handle) {
    $connection_analysis = array();
    $newest = array();
    
    $open_connections = array();
    $closed_connections = array();
    $close_unopen_conn_count = 0;
    
    
    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n", "", $line);
        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line);

        $key = "$src_ip,$dst_ip,$src_port,$dst_port";
        if ($action == 'new') {       
            if ($flag == "ESTABLISHED" || $flag == 'SYN_SENT') {

                if (!isset($open_connections[$key])) {
                    $open_connections[$key] = array();
                }
                
                $new_conn = array(
                    'date_start' => $date,
                    'date_end' => null,
                    'time_start' =>  $time,
                    'time_end' => null
                );
                
                array_push($open_connections[$key], $new_conn);                
            }
        } elseif ($action == 'close') {
            if(isset($open_connections[$key]) && count($open_connections[$key])){
                $closed_conn = array_pop($open_connections[$key]);
                $closed_conn['date_end'] = $date;
                $closed_conn['time_end'] = $time;
                
                if(!count($open_connections[$key])){
                    unset($open_connections[$key]);
                }
                
                if(!isset($closed_connections[$key])){
                    $closed_connections[$key] = array(
                        'num_connections' => 0,
                        'total_time' => 0
                    );
                }
                
                $closed_connections[$key]['num_connections']++;
                
                $date_s = $closed_conn['date_start'] . ' ' . $closed_conn['time_start'];                
                $date_start = strtotime($date_s);                
                $date_end = strtotime($date . ' ' . $time);
                                
                
                $closed_connections[$key]['total_time'] += $date_end - $date_start;                  
                
                unset($closed_conn);
            }else{
                $close_unopen_conn_count++;
            }
        }
    }

    //asort($dst_port_analysis);
    
    $dst_port_analysis = array();
    $dst_ip_analysis = array();
    $dst_host_analysis = array();

    foreach($closed_connections as $key => $value){
        list($src_ip,$dst_ip,$src_port,$dst_port) = explode(',', $key);
        
        if(!isset($dst_port_analysis[$dst_port])){
            $dst_port_analysis[$dst_port] = array(
                'num_connections' => 0,
                'total_time' => 0
            );
        }
        
        $dst_port_analysis[$dst_port]['num_connections'] += $value['num_connections'];
        $dst_port_analysis[$dst_port]['total_time'] += $value['total_time']; 
        
        
        if(!isset($dst_ip_analysis[$dst_ip])){
            $dst_ip_analysis[$dst_ip] = array(
                'num_connections' => 0,
                'total_time' => 0
            );
        }
        
        $dst_ip_analysis[$dst_ip]['num_connections'] += $value['num_connections'];
        $dst_ip_analysis[$dst_ip]['total_time'] += $value['total_time']; 
    }
    
    $num_analyzed = 0;
    foreach($dst_port_analysis as $key => $value){
        $num_analyzed += $value['num_connections'];
        //echo $key . "\t" . number_format($value['total_time'] / $value['num_connections'], 2) . "\t" . $value['num_connections'] . "\n";
    }
    
    foreach($dst_ip_analysis as $key => $value){
        $host = 'unknown host';
        
        try{
            $host = gethostbyaddr($key);
        } catch (Exception $ex) {
            $host = 'unknown host';
        }
        
        if($host != $key){
            $parts = explode('.', $host);
            
            if(count($parts) > 1){
                $host2 = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            }
            
            if(!isset($dst_host_analysis[$host2])){
                $dst_host_analysis[$host2] = 0;
            }
            $dst_host_analysis[$host2] += $value['num_connections'];
        }
        
        //echo $key . "\t" . $host . "\t" . number_format($value['total_time'] / $value['num_connections'], 2) . "\t" . $value['num_connections'] . "\n";
    }
    
    foreach($dst_host_analysis as $key => $value){
        echo "$key\t$value\n";
    }
    
    echo "\n\nConnections analyzed: $num_analyzed\n";
    echo "Number of ports: " . count($dst_port_analysis) . "\n";
    echo "Number of IPs: " . count($dst_ip_analysis) . "\n";
    echo "Number of hosts: " . count($dst_host_analysis) . "\n";
    echo "Unclosed connections: " . count($open_connections) . "\n";    
    echo "Closed not opened connections: $close_unopen_conn_count \n";

    fclose($handle);
}