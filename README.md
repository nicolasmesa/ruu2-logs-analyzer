RUU2 Script Files
=====

Here are a bunch of scripts to fix corrupted RUU2 log files, convert those files into stuctured data, perform some analysis of portmon sensor logs and insert log files into the database

Scripts
======

- **split_merged_lines.php**: This script attempts to fix log entries that have been merged together, and will output a fixed log file with the same RUU2 format
- **logs_to_port.php**: This script takes as input the fixed log file from the previous script and will output a structured log file containing only portmon logs in a format that is easily parseable by the other scripts.
- **stat_analyzer.php**: Takes as input the structured log file from <code>logs_to_port.php</code> and produces some statistics from it. To change the analysis type, change the contents of the variable <code>$analysis</code> to the type of analysis that you want.
- **ruu2.sql**: Dump of MySQL database schema used to store log entries and connection information derived from the logs.
- **connections_insert**: Inserts log entries and keeps track of the time when connections are opened or closed. Then inserts each connection into the table, relating it to the open and close log entries.

