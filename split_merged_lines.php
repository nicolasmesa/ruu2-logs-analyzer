<?php
/**
 * Some lines in the log files are merged together (maybe some bug). This process
 * will get the raw log and split the marged lines to try and fix the log file
 */
$handle = fopen("logs/merged_logs.txt", "r");

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