<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\ResourceIRI;
use CaliperExtension\caliper\CaliperActor;
use CaliperExtension\caliper\CaliperEntity;
use IMSGlobal\Caliper\events\Event;

class CaliperEvent {
    public static function addDefaults(Event &$event) {
        global $wgUser;
        global $wgRequest;

        $headers = $wgRequest->getAllHeaders();

        $event->setActor(CaliperActor::generateActor($wgUser));
        $event->setSession(CaliperEntity::session($wgUser));
        $event->setEdApp(CaliperEntity::media_wiki());


        if (!$event->getEventTime()) {
            $event->setEventTime(new \DateTime('@'. time()));
        }

        $extensions = $event->getExtensions() ?: [];
        if (array_key_exists('USER-AGENT', $headers)) {
            $extensions['browser-info']['userAgent'] = $headers['USER-AGENT'];
        }
        if (array_key_exists('REFERER', $headers)) {
            $extensions['browser-info']['referer'] = $headers['REFERER'];
        }
        if ($wgRequest->getIP()) {
            $extensions['browser-info']['ipAddress'] = $wgRequest->getIP();
        }

        if ([] !== $extensions) {
            $event->setExtensions($extensions);
        }
    }
}