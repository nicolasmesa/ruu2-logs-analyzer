<?php
/**
 * This file handles the insertion of connections and log file entries to the
 * database. It keeps track of TCP connections and the time they last. Every time
 * a TCP connection is closed, it is matched to the open connection and the 
 * time between open and close is calculated.
 * 
 * This script inserts into two different tables:
 *  - portmon_logs: Has all log entries separated by each field for easy lookup
 *  - connections_log: Only saves conenctions that we were able to track (see an
 *      open and close statement) and it references with a foreign key the 
 *      open log entry (in portmon_logs) and the close log entry (in portmon_logs)
 * 
 */

$filename = "logs/port_logs.txt";
date_default_timezone_set('America/New_York');

$db = null;

function insert_log_file($filename){
    $handle = fopen($filename, "r");

    if (!$handle) {
        return false;
    }

    $close_unopen_conn_count = 0;
    $open_connections = array();

    while (($line = fgets($handle)) !== false) {
        $line2 = str_replace("\n", "", $line);

        list($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) = explode("\t", $line2);

        $id = insert_to_portlogs($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag);

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
                    'id' => $id,
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

                /*
                 * Saving memory
                 */
                if (!count($open_connections[$key])) {
                    unset($open_connections[$key]);
                }

                $connId = insert_to_connectionslog(
                        $closed_conn['id'], $id, $src_ip, $dst_ip, $src_port, 
                        $dst_port, $closed_conn['date_start'], $date, 
                        $closed_conn['time_start'], $time);

                unset($closed_conn);
            } else {
                $close_unopen_conn_count++;
            }
        }
    }

    fclose($handle);
}

function insert_to_portlogs($date, $time, $ip_proto, $src_ip, $dst_ip, $src_port, $dst_port, $action, $flag) {
    $db = get_db();

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

    if (!$stmt->execute($params)) {
        var_dump($stmt->errorInfo());
        exit;
    }

    return $db->lastInsertId();
}

function insert_to_connectionslog($idConnStart, $idConnEnd, $src_ip, $dst_ip, $src_port, $dst_port, $date_start, $date_end, $time_start, $time_end) {
    $db = get_db();


    $datetime_start = $date_start . ' ' . $time_start;
    $datetime_end = $date_end . ' ' . $time_end;
    
    $time = strtotime($datetime_end) - strtotime($datetime_start);

    $params = array(
        $idConnStart,
        $idConnEnd,
        $src_ip,
        $dst_ip,
        $src_port,
        $dst_port,
        $date_start . ' ' . $time_start,
        $date_end . ' ' . $time_end,
        $time
    );


    $sql = "INSERT INTO connections_log("
            . "id_conn_start,"
            . "id_conn_end,"
            . "src_ip,"
            . "dst_ip,"
            . "src_port,"
            . "dst_port,"
            . "datetime_start,"
            . "datetime_end,"
            . "time"
            . ") VALUES (?,?,?,?,?,?,?,?,?)";

    $stmt = $db->prepare($sql);

    if (!$stmt->execute($params)) {
        var_dump($params);
        echo $sql;
        var_dump($stmt->errorInfo());
        exit;
    }

    return $db->lastInsertId();
}

/**
 * 
 * @return PDO
 */
function get_db() {
    global $db;
    
    if(!$db){
        $db = new PDO('mysql:host=127.0.0.1;dbname=ruu2;charset=utf8', 'ruu2', 'ruu2');
    }
    
    return $db;
}

insert_log_file($filename);

echo "Finished successfully\n";