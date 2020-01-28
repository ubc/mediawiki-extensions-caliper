<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\CaliperSensor;
use CaliperExtension\caliper\ResourceIRI;
use IMSGlobal\Caliper\entities\agent\Person;

class CaliperActor {
    private static function _generateWikimediaActor(\User &$user) {
        return (new Person( ResourceIRI::actor_homepage($user->getName()) ))
            ->setName($user->getName())
            ->setDateCreated(CaliperSensor::mediaWikiTimestampToDateTime($user->getRegistration()));
    }

    public static function generateActor(\User &$user) {
        # happens when not logged in
        if (!$user->getId()) {
            return Person::makeAnonymous();
        }

        $actor = null;
        \Hooks::run('SetCaliperActorObject', [ &$actor, &$user ]);

        return $actor !== null ? $actor : self::_generateWikimediaActor($user);
    }
}
