<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\CaliperSensor;
use CaliperExtension\caliper\ResourceIRI;
use IMSGlobal\Caliper\entities\agent\Person;
use IMSGlobal\Caliper\entities\EntityType;

class CaliperActor {
    private static function _generateAnonymousActor(\User &$anon_user) {
        return (new Person( 'http://purl.imsglobal.org/ctx/caliper/v1p1/Person' ));
    }

    private static function _generateWikimediaActor(\User &$user) {
        return (new Person( ResourceIRI::actor_homepage($user->getName()) ))
            ->setName($user->getName())
            ->setDateCreated(CaliperSensor::mediaWikiTimestampToDateTime($user->getRegistration()));
    }

    public static function generateActor(\User &$user) {
        # happens when not logged in
        if (!$user->getId()) {
            return self::_generateAnonymousActor($user);
        }

        $actor = null;
        \Hooks::run('SetCaliperActorObject', [ &$actor, &$user ]);

        return $actor !== null ? $actor : self::_generateWikimediaActor($user);
    }
}
