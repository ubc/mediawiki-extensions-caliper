<?php
namespace CaliperExtension\caliper;

use IMSGlobal\Caliper\Options;
use IMSGlobal\Caliper\Sensor;
use CaliperExtension\caliper\ResourceIRI;
use CaliperExtension\caliper\CaliperEvent;
use IMSGlobal\Caliper\events\Event;
use IMSGlobal\Caliper\Client;
use IMSGlobal\Caliper\util\TimestampUtil;

use IMSGlobal\Caliper\request\HttpRequestor;

class CaliperSensor {
    private static $options = null;
    private static $sensor = null;

    private static function getOptions() {
        global $wgCaliperHost;
        global $wgCaliperAPIKey;

		if ( self::$options == null ) {
            self::$options = (new Options())
                ->setApiKey("Bearer $wgCaliperAPIKey")
                ->setDebug(true)
                ->setHost($wgCaliperHost);
        }
        return self::$options;
    }

    private static function getSensor() {
		if ( self::$sensor == null ) {
            self::$sensor = new Sensor(ResourceIRI::getBaseUrl());
            self::$sensor->registerClient('default_client', new Client('remote_lrs', self::getOptions()));
        }
        return self::$sensor;
    }

    public static function caliperEnabled() {
        global $wgCaliperHost;
        global $wgCaliperAPIKey;

        return (is_string($wgCaliperHost) && is_string($wgCaliperAPIKey));
    }

    public static function mediawikiTimestampToDateTime($timestamp) {
        return \DateTime::createFromFormat("YmdHis", $timestamp);
    }

    public static function mediawikiTimestampToISO8601($timestamp) {
        return TimestampUtil::formatTimeISO8601MillisUTC(self::mediawikiTimestampToDateTime($timestamp));
    }

    public static function sendEvent(Event &$event) {
        global $wgCaliperUseJobQueue;

        if (!self::caliperEnabled()) {
            return;
        }
        CaliperEvent::addDefaults($event);

        if ($wgCaliperUseJobQueue) {
            $title = \Title::newMainPage(); //dummy title
            $params = array('event' => $event);
            $job = new \CaliperEmitEventJob($title, $params);
            \JobQueueGroup::singleton()->push($job);
        } else {
            self::_sendEvent($event);
        }
    }

    public static function _sendEvent(Event &$event) {
        if (!self::caliperEnabled()) {
            return;
        }

        $sensor = self::getSensor();
        try {
            $sensor->send($sensor, $event);
        } catch (\RuntimeException $sendException) {
            wfDebugLog("mediawiki-extension-caliper", 'Caliper Event Emit Error: '. $sendException->getMessage());
            self::storeFailedEvent($event, $sendException->getMessage());
        }
    }

    private static function storeFailedEvent(Event &$event, $errorString) {
        global $wgDBprefix;

        $requestor = new HttpRequestor(self::getOptions());
        $envelope = $requestor->createEnvelope(self::getSensor(), $event);
        $eventJson = @$requestor->serializeData($envelope);

        $dbw = wfGetDB(DB_MASTER);
        $res_ad = $dbw->insert($wgDBprefix."caliper_failed_events", array(
            'event_json' => $eventJson,
            'error' => $errorString
        ));
    }
}
