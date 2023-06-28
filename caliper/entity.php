<?php
namespace CaliperExtension\caliper;

use CaliperExtension\caliper\ResourceIRI;
use CaliperExtension\caliper\CaliperSensor;
use IMSGlobal\Caliper\entities\agent\SoftwareApplication;
use IMSGlobal\Caliper\entities\session\Session;
use IMSGlobal\Caliper\entities\reading\WebPage;
use IMSGlobal\Caliper\entities\reading\Document;

class CaliperEntity {
    public static function mediaWiki() {
        global $wgVersion;

        $eduApp = (new SoftwareApplication( ResourceIRI::mediaWiki() ))
            ->setName("MediaWiki")
            ->setVersion($wgVersion);

        return $eduApp;
    }

    public static function session(\User &$user) {
        global $wgRequest;
        $headers = $wgRequest->getAllHeaders();
        $session_id_object = $wgRequest->getSessionId();
        $session_id = $session_id_object ? $session_id_object->getId() : 'null';

        $session = (new Session( ResourceIRI::user_session($session_id) ))
            ->setUser( CaliperActor::generateActor($user) )
            ->setClient( CaliperEntity::client($session_id) );

		$extensions = [];
        if (array_key_exists('REFERER', $headers)) {
            $extensions['referer'] = $headers['REFERER'];
        }
		if ( [] !== $extensions ) {
			$session->setExtensions( $extensions );
		}

        return $session;
    }

	public static function client( $session_id ) {
        global $wgRequest;
        $headers = $wgRequest->getAllHeaders();

        $user_client = ( new SoftwareApplication( ResourceIRI::user_client( $session_id ) ) );

        if (array_key_exists('HOST', $headers)) {
            $user_client->setHost( $headers['HOST'] );
        }
        if (array_key_exists('USER-AGENT', $headers)) {
            $user_client->setUserAgent( $headers['USER-AGENT'] );
        }

        if ($wgRequest->getIP()) {
			$user_client->setIpAddress( $wgRequest->getIP() );
        }

		return $user_client;
	}


    public static function wikiPage(\WikiPage &$wikiPage) {
        if ($wikiPage->exists()) {
            $currentRevision = $wikiPage->getRevisionRecord();
            $currentRevisionUser = \User::newFromId( $wikiPage->getUser() );
            # getOldestRevision is deprecated
            #$firstRevision = $wikiPage->getOldestRevision();

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
                ->setDateModified(CaliperSensor::mediawikiTimestampToDateTime($wikiPage->getTimestamp()));
                #->setDateCreated(CaliperSensor::mediawikiTimestampToDateTime($firstRevision->getTimestamp()))
                #->setDatePublished(CaliperSensor::mediawikiTimestampToDateTime($firstRevision->getTimestamp()));

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
