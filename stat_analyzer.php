<?php

$handle = fopen("port_logs_all.log", "r");
//$handle = fopen("mac64d7d7e9b42cbdeaf38433dde82ad18d_1426179194.txt", "r");

if ($handle) {
    $port_analysis = array();
    $dst_port_analysis = array();
    $src_port_analysis = array();
    $ip_analysis = array();
    $src_ip_analysis = array();
    $dst_ip_analysis = array();
    $ip_proto_analysis = array();
    $connection_analysis = array();
    $ip_ports_analysis = array();
    $src_ip_diff_ports_analysis = array();


    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n", "", $line);
        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line);


        $port_arr = array($src_port, $dst_port);
        asort($port_arr);

        $port_key = implode(',', $port_arr);
        if (!isset($port_analysis[$port_key])) {
            $port_analysis[$port_key] = 0;
        }
        $port_analysis[$port_key] ++;


        if (!isset($dst_port_analysis[$dst_port])) {
            $dst_port_analysis[$dst_port] = 0;
        }
        $dst_port_analysis[$dst_port] ++;


        if (!isset($src_port_analysis[$src_port])) {
            $src_port_analysis[$src_port] = 0;
        }
        $src_port_analysis[$src_port] ++;


        $ip_key = "$src_ip,$dst_ip";
        if (!isset($ip_analysis[$ip_key])) {
            $ip_analysis[$ip_key] = 0;
        }
        $ip_analysis[$ip_key] ++;


        if (!isset($src_ip_analysis[$src_ip])) {
            $src_ip_analysis[$src_ip] = 0;
        }
        $src_ip_analysis[$src_ip] ++;


        if (!isset($dst_ip_analysis[$dst_ip])) {
            $dst_ip_analysis[$dst_ip] = 0;
        }
        $dst_ip_analysis[$dst_ip] ++;


        if (!isset($ip_proto_analysis[$ip_proto])) {
            $ip_proto_analysis[$ip_proto] = 0;
        }
        $ip_proto_analysis[$ip_proto] ++;


        /*
         * 
         */
        if (!isset($ip_ports_analysis[$dst_ip])) {
            $ip_ports_analysis[$dst_ip] = array();
        }
        
        if(!isset($src_ip_diff_ports_analysis[$dst_ip])){
            $src_ip_diff_ports_analysis[$dst_ip] = 0;
        }

        if (!isset($ip_ports_analysis[$dst_ip][$src_port])) {
            $ip_ports_analysis[$dst_ip][$src_port] = 0;
            $src_ip_diff_ports_analysis[$dst_ip]++;
        }
        $ip_ports_analysis[$dst_ip][$src_port] ++;
    }


    $analysis = $src_ip_diff_ports_analysis;

    arsort($analysis);

    $max_output = 1000000;
    $index = 0;

    foreach ($analysis as $key => $value) {
        echo "$key\t$value\n";
        $index++;

        if ($index >= $max_output) {
            break;
        }
    }

    echo "count: " . count($analysis);

    fclose($handle);
}