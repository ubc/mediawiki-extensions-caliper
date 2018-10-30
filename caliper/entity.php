<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\ResourceIRI;
use CaliperExtension\caliper\CaliperSensor;
use IMSGlobal\Caliper\entities\agent\Person;
use IMSGlobal\Caliper\entities\agent\SoftwareApplication;
use IMSGlobal\Caliper\entities\EntityType;
use IMSGlobal\Caliper\entities\session\Session;
use IMSGlobal\Caliper\entities\reading\WebPage;
use IMSGlobal\Caliper\entities\reading\Document;

class CaliperEntity {
    public static function media_wiki() {
        global $wgVersion;

        $eduApp = (new SoftwareApplication( ResourceIRI::media_wiki() ))
            ->setName("MediaWiki")
            ->setVersion($wgVersion);

        return $eduApp;
    }

    public static function session(\User &$user) {
        global $wgRequest;

        $session = (new Session( ResourceIRI::user_session($wgRequest->getSessionId()->getId()) ))
            ->setUser(CaliperActor::generateActor($user));

        return $session;
    }


    public static function wikiPage(\WikiPage &$wikiPage) {
        if ($wikiPage->exists()) {
            $currentRevision = $wikiPage->getRevision();
            $currentRevisionUser = \User::newFromId( $wikiPage->getUser() );
            $firstRevision = $wikiPage->getOldestRevision();

            $creators = array();
            foreach ($wikiPage->getContributors() as $creator) {
                if ($creator->getId()) {
                    $creators[] = CaliperActor::generateActor($creator);
                }
            }
            if ($currentRevisionUser->getId()) {
                $creators[] = CaliperActor::generateActor($currentRevisionUser);
            }

            $wikiPageEntity = (new Document( ResourceIRI::wikiPage($wikiPage->getId()) ))
                ->setName($wikiPage->getTitle()->getText())
                ->setIsPartOf( CaliperEntity::webpage($wikiPage->getTitle()->getPrefixedURL()) )
                ->setVersion( ResourceIRI::wikiPageRevision($currentRevision->getId()) )
                ->setCreators($creators)
                ->setDateModified(CaliperSensor::mediawikiTimestampToDateTime($wikiPage->getTimestamp()))
                ->setDateCreated(CaliperSensor::mediawikiTimestampToDateTime($firstRevision->getTimestamp()))
                ->setDatePublished(CaliperSensor::mediawikiTimestampToDateTime($firstRevision->getTimestamp()));

            $extensions = array();
            $redirect = $wikiPage->followRedirect();
            if ($redirect) {
                $extensions['redirect'] = true;
                if ($redirect instanceof \Title) {
                    $extensions['redirectTo'] = ResourceIRI::wikiPage($redirect->getArticleID());
                }
            }
            if (count($extensions) > 0) {
                $wikiPageEntity->setExtensions($extensions);
            }

            return $wikiPageEntity;
        } else {
            $webPage = CaliperEntity::webpage($wikiPage->getTitle()->getPrefixedURL());

            $extensions = $webPage->getExtensions() ?: [];
            $extensions['wikiPageExists'] = false;
            $webPage->setExtensions($extensions);

            return $webPage;
        }
    }

    public static function wikiPageRevision(\Revision &$revision) {
        $wikiPage = \Wikipage::newFromId( $revision->getPage() );
        $currentRevisionUser = \User::newFromId( $revision->getUser() );

        $creators = [CaliperActor::generateActor($currentRevisionUser)];

        $extensions = [
            'comment' => $revision->getComment()
        ];

        $wikiPageRevisionEntity = (new Document( ResourceIRI::wikiPageRevision($revision->getId()) ))
            ->setName($revision->getTitle()->getText())
            ->setIsPartOf( CaliperEntity::wikiPage($wikiPage) )
            ->setCreators($creators)
            ->setDateModified(CaliperSensor::mediawikiTimestampToDateTime($revision->getTimestamp()))
            ->setDateCreated(CaliperSensor::mediawikiTimestampToDateTime($revision->getTimestamp()))
            ->setDatePublished(CaliperSensor::mediawikiTimestampToDateTime($revision->getTimestamp()))
            ->setExtensions($extensions);

        return $wikiPageRevisionEntity;
    }

    public static function webpage($relativePath) {
        if (!is_string($relativePath)) {
            throw new \InvalidArgumentException(__METHOD__ . ': string expected');
        }
        $webPage = (new WebPage( ResourceIRI::webpage($relativePath) ));

        return $webPage;
    }
}