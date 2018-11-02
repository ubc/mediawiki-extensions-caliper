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

        $requestor = new HttpRequestor(self::getOptions());
        $envelope = $requestor->createEnvelope(self::getSensor(), $event);
        $eventJson = $requestor->serializeData($envelope);

        if ($wgCaliperUseJobQueue) {
            $title = \Title::newMainPage(); //dummy title
            $params = array('eventJson' => $eventJson);
            $job = new \CaliperEmitEventJob($title, $params);
            \JobQueueGroup::singleton()->push($job);
        } else {
            self::_sendEvent($eventJson, false);
        }
    }

    public static function _sendEvent($eventJson, $throwErrors) {
        if (!is_string($eventJson)) {
            throw new \InvalidArgumentException(__METHOD__ . ': string expected');
        }
        if (!self::caliperEnabled()) {
            return;
        }

        // Requires curl extension
        // based off of https://github.com/IMSGlobal/caliper-php/blob/master/src/request/HttpRequestor.php#L75
        $client = curl_init(self::getOptions()->getHost());
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' .self::getOptions()->getApiKey()
        ];
        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT_MS => self::getOptions()->getConnectionTimeout(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'Caliper (PHP curl extension)',
            CURLOPT_HEADER => true, // CURLOPT_HEADER required to return response text
            CURLOPT_RETURNTRANSFER => true, // CURLOPT_RETURNTRANSFER required to return response text
            CURLOPT_POSTFIELDS => $eventJson,
        ]);

        $responseText = curl_exec($client);
        $responseInfo = curl_getinfo($client);
        curl_close($client);

        $responseCode = $responseText ? $responseInfo['http_code'] : null;
        if ($responseCode != 200) {
            wfDebugLog("mediawiki-extension-caliper", 'Caliper Event Emit Error: '. $responseCode);
            if ($throwErrors) {
                throw new \RuntimeException('Failure: HTTP error: ' . $responseCode);
            } else {
                wfDebugLog("mediawiki-extension-caliper", 'Caliper Event JSON: '. $eventJson);
            }
        }
    }
}
