<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\CaliperActor;
use CaliperExtension\caliper\CaliperEntity;
use IMSGlobal\Caliper\events\Event;

class CaliperEvent {
    public static function addDefaults(Event &$event) {
        global $wgUser;

        $event->setActor(CaliperActor::generateActor($wgUser));
        $event->setSession(CaliperEntity::session($wgUser));
        $event->setEdApp(CaliperEntity::mediaWiki());


        if (!$event->getEventTime()) {
            $event->setEventTime(new \DateTime('@'. time()));
        }
    }
}