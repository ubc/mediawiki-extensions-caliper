<?php

// Used to store failed events
class CaliperMigrations {
    public static function onLoadExtensionSchemaUpdates(\DatabaseUpdater $updater)
    {
        $updater->addExtensionTable('caliper_events', __DIR__ . '/sql/add_caliper_failed_events_table.sql');
	    return true;
    }
}