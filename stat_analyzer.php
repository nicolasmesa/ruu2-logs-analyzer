<?php
/**
 * This script counting analysis on the log files including:
 *  - src_port,dst_port pairing count ($port_analysis)
 *  - dst_port count ($dst_port_analysis)
 *  - src_port count ($src_port_analysis)
 *  - src_ip,dst_ip pairing count ($ip_analysis)
 *  - src_ip count ($src_ip_analysis)
 *  - dst_ip count ($dst_ip_analysis)
 *  - ports per ip ($ip_ports_analysis)
 *  - count number of ports per ip ($src_ip_diff_analysis)
 */

$handle = fopen("logs/port_logs.txt", "r");

if ($handle) {
    $port_analysis = array();
    $dst_port_analysis = array();
    $src_port_analysis = array();
    $ip_analysis = array();
    $src_ip_analysis = array();
    $dst_ip_analysis = array();
    $ip_proto_analysis = array();
    $ip_ports_analysis = array();
    $src_ip_diff_ports_analysis = array();


    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n", "", $line);
        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line);

       

        $port_key = "$src_port,$dst_port";
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

    /*
     * Change this variable for the analysis that you want to output
     * e.g. $analysis = $ip_analysis
     */
    $analysis = $dst_ip_analysis;

    arsort($analysis);

    $max_output = 25;
    $index = 0;

    foreach ($analysis as $key => $value) {
        echo "$key\t$value\n";
        $index++;

        if ($index >= $max_output) {
            break;
        }
    }

    //echo "count: " . count($analysis);

    fclose($handle);
}