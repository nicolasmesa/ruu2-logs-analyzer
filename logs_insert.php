<?php

date_default_timezone_set('America/New_York');

$handle = fopen("logs/port_logs_all.log", "r");

if ($handle) {
    $db = new PDO('mysql:host=127.0.0.1;dbname=ruu2;charset=utf8', 'ruu2', 'ruu2');

    while (($line = fgets($handle)) !== false) {
        $line = str_replace("\n", "", $line);
        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line);

        
        $datetime = $date . ' ' . $time;
        $timestamp = strtotime($datetime);
        
        $params = array(
            date('Y-m-d h:i:s', $timestamp),
            $timestamp,
            $ip_proto,
            $src_ip,
            $dst_ip,
            $src_port,
            $dst_port,
            $action,
            $flag
        );
        
        
        $sql = "INSERT INTO portmon_logs("
                        . "datetime,"
                        . "timestamp,"
                        . "ip_proto,"
                        . "src_ip,"
                        . "dst_ip,"
                        . "src_port,"
                        . "dst_port,"
                        . "action,"
                        . "flag"
                        . ") VALUES (?,?,?,?,?,?,?,?,?)";
        
        $stmt = $db->prepare($sql);
        
        if(!$stmt->execute($params)){
            var_dump($stmt->errorInfo());
            exit;
        }
        
    }

    fclose($handle);
}