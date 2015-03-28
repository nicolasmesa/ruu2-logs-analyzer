
SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `connections_log`
-- ----------------------------
DROP TABLE IF EXISTS `connections_log`;
CREATE TABLE `connections_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_conn_start` int(11) NOT NULL,
  `id_conn_end` int(11) NOT NULL,
  `src_ip` varchar(200) NOT NULL,
  `dst_ip` varchar(200) NOT NULL,
  `src_port` int(11) NOT NULL,
  `dst_port` int(11) NOT NULL,
  `datetime_start` datetime NOT NULL,
  `datetime_end` datetime NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_conn_start` (`id_conn_start`),
  KEY `id_conn_end` (`id_conn_end`),
  CONSTRAINT `fk_id_conn_end1` FOREIGN KEY (`id_conn_end`) REFERENCES `portmon_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_id_conn_start1` FOREIGN KEY (`id_conn_start`) REFERENCES `portmon_logs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=580561 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Table structure for `portmon_logs`
-- ----------------------------
DROP TABLE IF EXISTS `portmon_logs`;
CREATE TABLE `portmon_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `timestamp` int(11) NOT NULL,
  `ip_proto` varchar(5) NOT NULL,
  `src_ip` varchar(200) NOT NULL,
  `dst_ip` varchar(200) NOT NULL,
  `src_port` int(11) NOT NULL,
  `dst_port` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `flag` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1889524 DEFAULT CHARSET=latin1;

SET FOREIGN_KEY_CHECKS = 1;
