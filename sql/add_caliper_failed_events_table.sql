CREATE TABLE IF NOT EXISTS `caliper_failed_events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_json` longtext NOT NULL,
  `error` text,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);