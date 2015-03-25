<?php
$handle = fopen("all_logs.log", "r");

if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $log_entries = explode("ver1.2", $line);

        unset($log_entries[count($log_entries) - 1]);

        foreach ($log_entries as $entry) {
            echo $entry . "ver1.2\n";
        }
    }
    
    fclose($handle);
}