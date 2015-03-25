<?php

$handle = fopen("split_all_log.log", "r");
//$handle = fopen("mac64d7d7e9b42cbdeaf38433dde82ad18d_1426179194.txt", "r");

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $src_port = null;
        $dst_port = null;
        $src_ip = null;
        $dst_ip = null;
        $proto = 'TCP';
        $ipProto = null;
        $action = null;
        $flag = 'N/A';
        
        $matches2 = array();
        $matches = array();
        
        /*
         * Skip anything that is not portmon new
         */
        if(!preg_match('/portmon/', $line)){            
            continue;
        }
        
        preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2})\s([0-9]{2}:[0-9]{2}:[0-9]{2})/', $line, $matches);
        
        if(!isset($matches[1]) || !isset($matches[2])){
            continue;
        }
        
        $date = $matches[1];
        $time = $matches[2];
        
        if(preg_match('/closed/', $line)){              
            $action = 'close';            
        }elseif(preg_match('/new/', $line)){
            $action = 'new';
            
            if(preg_match('/\(([A-Z0-9_]+)\)/', $line, $matches)){
                $flag = $matches[1];
            }
        }  
       
        $matches = array();
        
        if (preg_match_all('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}):([0-9]+)/', $line, $matches)) {
            $ipProto = 'IPv4';
            
        } elseif (preg_match_all('/\[([0-9a-f:]+)\]:([0-9]+)/', $line, $matches)) {
            $ipProto = 'IPv6';            
        }

        if (isset($matches[1]) && isset($matches[2]) && is_array($matches[1]) && is_array($matches[2]) &&
                count($matches[1]) == 2 && count($matches[2]) == 2) {
            
            list($src_ip, $dst_ip) = $matches[1];
            list($src_port, $dst_port) = $matches[2];
                        
            echo "$date\t$time\t$ipProto\t$src_ip\t$dst_ip\t$src_port\t$dst_port\t$action\t$flag\n";
        }else{
            // ignore invalid lines
        }
    }
    
    fclose($handle);
} else {
    echo "Error";
}