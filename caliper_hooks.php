<?php

use IMSGlobal\Caliper\events\Event;
use IMSGlobal\Caliper\events\NavigationEvent;
use IMSGlobal\Caliper\events\SessionEvent;

use IMSGlobal\Caliper\actions\Action;

use IMSGlobal\Caliper\entities\agent\Organization;
use IMSGlobal\Caliper\entities\agent\Person;
use IMSGlobal\Caliper\entities\agent\SoftwareApplication;
use IMSGlobal\Caliper\entities\DigitalResource;
use IMSGlobal\Caliper\entities\lis\Membership;
use IMSGlobal\Caliper\entities\session\Session;

use CaliperExtension\caliper\ResourceIRI;
use CaliperExtension\caliper\CaliperEntity;
use CaliperExtension\caliper\CaliperSensor;

/*
Hooks Used
-------------------------------
BeforePageDisplay       -

UserLoginComplete       -
UserLogout              - cannot use UserLogoutComplete since user is no longer available

PageContentSaveComplete -
ArticleDelete           - cannot use ArticleDeleteComplete since the wikipage is no longer available
ArticleUndelete         - no Complete available
ArticleProtectComplete  -
TitleMoveComplete       -
ArticleMergeComplete    -
ArticleRevisionVisibilitySet - no Complete available

WatchArticleComplete    -
UnwatchArticleComplete  -

MarkPatrolledComplete   -


Hooks Not Used
-------------------------------
ArticleRevisionUndeleted    - not needed (looks like PageContentSaveComplete handles it)
EmailUserComplete           - Not really interested
UploadComplete              - not needed (looks like PageContentSaveComplete handles it since a new page is created)
ArticleRollbackComplete     - not needed (looks like PageContentSaveComplete handles it)
*/

class CaliperHooks {
    /*
    Page Navigation To Event
    Example to WikiPage:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "NavigationEvent",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "NavigatedTo",
      "object": {
        "id": "http://localhost:8888/index.php?curid=2",
        "type": "Document",
        "name": "Zombie",
        "dateCreated": "2018-10-26T19:34:21.000Z",
        "dateModified": "2018-11-02T20:59:53.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          },
          {
            "id": "http://media_wiki/ABC123PUID",
            "type": "Person",
            "name": "Lastname",
            "dateCreated": "2018-10-25T19:47:46.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-10-26T19:34:21.000Z",
        "version": "http://localhost:8888/index.php?oldid=89"
      },
      "eventTime": "2018-11-02T21:05:16.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/ci73c71at9nr564pll1domo9k4ncid0m",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:0736f2e5-beb8-4424-928b-11fb609c346d",
      "extensions": {
        "isMediaWikiArticle": true,
        "relativePath": "Zombie",
        "queryString": "",
        "absolutePath": "http://localhost:8080/Zombie",
        "absoluteUrl": "http://localhost:8080/Zombie",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }


    Example to non WikiPage:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "NavigationEvent",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "NavigatedTo",
      "object": {
        "id": "http://localhost:8888/New_Zombie",
        "type": "WebPage",
        "extensions": {
          "wikiPageExists": false
        }
      },
      "eventTime": "2018-11-02T21:11:15.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:1cb41c73-806a-40a8-afbd-d8f5930da2ef",
      "extensions": {
        "isMediaWikiArticle": true,
        "relativePath": "New_Zombie",
        "queryString": "",
        "absolutePath": "http://localhost:8080/New_Zombie",
        "absoluteUrl": "http://localhost:8080/New_Zombie",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onBeforePageDisplay(\OutputPage &$out, \Skin &$skin)
    {
        global $wgFavicon;
        global $wgUser;

        if (!CaliperSensor::caliperEnabled()) {
            return;
        }
        $request = $out->getRequest();

        $absoluteUrl = $request->getFullRequestURL();
        $relativeUrl = $request->getRequestURL();
        $relativePath = $request->getPathInfo()['title'];
        $faviconFileName = basename($wgFavicon);

        # do not track navigation events for the favicon
        if (strcasecmp($relativePath, $faviconFileName) == 0) {
            return;
        }

        $event = (new NavigationEvent())
            ->setAction(new Action(Action::NAVIGATED_TO));

        if ($out->isArticle()) {
            $wikiPage = $out->getWikiPage();
            $event->setObject(CaliperEntity::wikiPage($wikiPage));
        } else {
            $event->setObject(CaliperEntity::webpage($relativePath));
        }

        $queryString = explode("?", $relativeUrl);
        $queryString = count($queryString) > 1 ? $queryString[1] : '';
        $event->setExtensions([
            'isMediaWikiArticle' => $out->isArticle(),
            'relativePath' => $relativePath,
            'queryString' => $queryString,
            'absolutePath' => preg_replace('/\?.*|\#.*/', '',  $absoluteUrl),
            'absoluteUrl' => $absoluteUrl,
        ]);

        CaliperSensor::sendEvent($event);
    }

    /*
    Login Event
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "SessionEvent",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "LoggedIn",
      "object": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "eventTime": "2018-11-02T21:09:38.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:de21c82f-9557-4412-911d-152bbc2df2fa",
      "extensions": {
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=Special:UserLogin&returnto=Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onUserLoginComplete(\User &$user, $inject_html, $direct)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $event = (new SessionEvent())
            ->setAction(new Action(Action::LOGGED_IN))
            ->setObject(CaliperEntity::media_wiki());

        CaliperSensor::sendEvent($event);
    }

    /*
    Logout Event (cannot use UserLogoutComplete as user no longer set)
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "SessionEvent",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "LoggedOut",
      "object": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "eventTime": "2018-11-02T21:08:03.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/ci73c71at9nr564pll1domo9k4ncid0m",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:24e0dcb2-46c5-4081-9312-ee64ea814843",
      "extensions": {
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onUserLogout(User &$user)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return true;
        }

        $event = (new SessionEvent())
            ->setAction(new Action(Action::LOGGED_OUT))
            ->setObject(CaliperEntity::media_wiki());

        CaliperSensor::sendEvent($event);

        return true;
    }

    /*
    Create/Edit WikiPage Event
    Create Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Created",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:14:01.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=90"
      },
      "eventTime": "2018-11-02T21:14:01.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:6953b08f-adda-445b-bda0-efc05b80a35b",
      "extensions": {
        "summary": "THis is a new WikiPage",
        "isUndo": false,
        "isBasedOffRevision": false,
        "isMinor": false,
        "currentRevision": "http://localhost:8888/index.php?oldid=90",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=edit",
          "ipAddress": "192.168.80.1"
        }
      }
    }

    Edit Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:15:42.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=91"
      },
      "eventTime": "2018-11-02T21:15:42.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:03a39c1f-5213-44ea-aa1c-e5230b68065e",
      "extensions": {
        "summary": "Editing a WikiPage",
        "isUndo": false,
        "isBasedOffRevision": false,
        "isMinor": false,
        "currentRevision": "http://localhost:8888/index.php?oldid=91",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=edit",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onPageContentSaveComplete(\WikiPage &$wikiPage, \User &$user,
        $content, $summary, $isMinor, $isWatch, $section, &$flags,
        \Revision $revision, &$status, $baseRevId, $undidRevId )
    {
        if (!CaliperSensor::caliperEnabled()) {
            return true;
        }

        $currentRevision = $wikiPage->getRevision();
        $statusValue = $status->getValue();
        $action = array_key_exists('new', $statusValue) && $statusValue['new'] == true ?
            Action::CREATED : Action::MODIFIED;

        $extensions = [
            'summary' => $summary,
            'isUndo' => $undidRevId !== 0,
            'isBasedOffRevision' => $baseRevId !== false,
            'isMinor' => $isMinor == true,
            'currentRevision' => ResourceIRI::wikiPageRevision($currentRevision->getId())
        ];
        if ($baseRevId) {
            $extensions['baseRevisionId'] = ResourceIRI::wikiPageRevision($baseRevId);
        }
        if ($undidRevId !== 0) {
            $extensions['undoRevisionId'] = ResourceIRI::wikiPageRevision($undidRevId);
        }

        $event = (new Event())
            ->setAction(new Action($action))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);

        return true;
    }

    /*
    Deactivate WikiPage Event (cannot use ArticleDeleteComplete as wikiPage no longer exists)
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Deactivated",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:15:42.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=91"
      },
      "eventTime": "2018-11-02T21:17:17.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:735eba3f-e1a7-407e-b90b-637e6f1df3c8",
      "extensions": {
        "reason": "content was: \" sdf  dsf dsf sd f dsf dsf sdf dsf dsf sdfdsfdsf dsf sdf   adsf adsfdsafsadf asdf adsf asd   asdf asdf\", and the only contributor was \"[[Special:Contributions/Teacher1|Teacher1]]\" ([[User talk:Teacher1|talk]])",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=delete",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onArticleDelete(\WikiPage &$wikiPage, \User &$user,
        &$reason, &$error)
    {
        if (!CaliperSensor::caliperEnabled() || $error) {
            return;
        }

        $extensions = [
            'reason' => $reason
        ];
        $event = (new Event())
            ->setAction(new Action(Action::DEACTIVATED))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);
    }

    /*
    Activate WikiPage Event
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Activated",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:15:42.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=91"
      },
      "eventTime": "2018-11-02T21:19:23.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:f9cb907a-4130-40c2-a5c4-0c43139200fc",
      "extensions": {
        "comment": "Undeleting WikiPage",
        "restoredPages": [
          "http://localhost:8888/index.php?curid=46"
        ],
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/Special:Undelete/New_Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onArticleUndelete(\Title $title, $create,
        $comment, $oldPageId, $restoredPages )
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $wikiPage = \WikiPage::newFromId( $oldPageId );
        $restoredPageIRIs = [];
        foreach (array_keys($restoredPages) as $wikiPageId) {
            $restoredPageIRIs[] = ResourceIRI::wikiPage($wikiPageId);
        }

        $extensions = [
            'comment' => $comment,
            'restoredPages' => $restoredPageIRIs
        ];
        $event = (new Event())
            ->setAction(new Action(Action::ACTIVATED))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);
    }

    /*
    Modified WikiPage Event (protect)
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:42:19.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=94"
      },
      "eventTime": "2018-11-02T21:42:19.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:8358e2bc-0cb5-40c0-88d3-ecaf09c1fd3b",
      "extensions": {
        "protection": {
          "edit": "autoconfirmed",
          "move": "sysop"
        },
        "reason": "Changing proction levels",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=unprotect",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onArticleProtectComplete(\WikiPage &$wikiPage, \User &$user,
        $protect, $reason, $moveonly)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $extensions = [
            'protection' => $protect,
            'reason' => $reason,
            'moveonly' => $moveonly
        ];

        $event = (new Event())
            ->setAction(new Action(Action::MODIFIED))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);
    }



    /*
    Modified WikiPage Event (change title)
    Also Create new WikiPage Event (if creating a redirect)
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie (Moved)",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:43:58.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie_(Moved)",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=96"
      },
      "eventTime": "2018-11-02T21:43:59.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:6cc6a88b-b0fb-4429-889a-50ab2c5bc005",
      "extensions": {
        "moved": true,
        "reason": "Moving WikiPage",
        "createdRedirectPage": true,
        "redirectFrom": "http://localhost:8888/index.php?curid=47",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/Special:MovePage/New_Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }

    (potential) Create Redirect WikiPage Example
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Created",
      "object": {
        "id": "http://localhost:8888/index.php?curid=47",
        "type": "Document",
        "name": "New Zombie",
        "extensions": {
          "redirect": true,
          "redirectTo": "http://localhost:8888/index.php?curid=46"
        },
        "dateCreated": "2018-11-02T21:43:58.000Z",
        "dateModified": "2018-11-02T21:43:58.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:43:58.000Z",
        "version": "http://localhost:8888/index.php?oldid=97"
      },
      "eventTime": "2018-11-02T21:43:59.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:3f351452-2114-4425-be49-08bb27f9738b",
      "extensions": {
        "createdFromMove": true,
        "reason": "Moving WikiPage",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/Special:MovePage/New_Zombie",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    #
    public static function onTitleMoveComplete(\Title &$title, \Title &$newTitle,
        \User $user, $oldid, $newid, $reason, \Revision $revision)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $wikiPage = \WikiPage::newFromId( $oldid );
        $extensions = [
            'moved' => true,
            'reason' => $reason,
            'createdRedirectPage' => $newid !== 0
        ];
        if ($newid !== 0) {
            $extensions['redirectFrom'] = ResourceIRI::wikiPage($newid);
        }
        $event = (new Event())
            ->setAction(new Action(Action::MODIFIED))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);

        if ($newid !== 0) {
            $wikiPage = \WikiPage::newFromId( $newid );
            $extensions = [
                'createdFromMove' => true,
                'reason' => $reason
            ];

            $event = (new Event())
                ->setAction(new Action(Action::CREATED))
                ->setObject(CaliperEntity::wikiPage($wikiPage))
                ->setExtensions($extensions);

            CaliperSensor::sendEvent($event);
        }
    }

    /*
    Modified WikiPage Event (merge history)
    Destination Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=48",
        "type": "Document",
        "name": "New Zombie Merge History",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:46:05.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie_Merge_History",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=98"
      },
      "eventTime": "2018-11-02T21:48:11.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:142e22e8-bfac-4054-a4d9-e1a9e9aaa869",
      "extensions": {
        "mergedHistory": true,
        "wasSource": false,
        "wasDestination": true,
        "redirectFrom": "http://localhost:8888/index.php?curid=46",
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=Special%3AMergeHistory&submitted=1&mergepoint=&target=New_Zombie_%28Moved%29&dest=New_Zombie_Merge_History",
          "ipAddress": "192.168.80.1"
        }
      }
    }

    Source Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie (Moved)",
        "extensions": {
          "redirect": true,
          "redirectTo": "http://localhost:8888/index.php?curid=48"
        },
        "dateCreated": "2018-11-02T21:48:11.000Z",
        "dateModified": "2018-11-02T21:48:11.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie_(Moved)",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:48:11.000Z",
        "version": "http://localhost:8888/index.php?oldid=99"
      },
      "eventTime": "2018-11-02T21:48:11.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:3e2dcbd7-7fa0-40ae-b1df-3d5f1fb101c2",
      "extensions": {
        "mergedHistory": true,
        "wasSource": true,
        "wasDestination": false,
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=Special%3AMergeHistory&submitted=1&mergepoint=&target=New_Zombie_%28Moved%29&dest=New_Zombie_Merge_History",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onArticleMergeComplete($sourceTitle, $destTitle)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $sourceWikiPage = \WikiPage::newFromId( $sourceTitle->getArticleID() );
        $destWikiPage = \WikiPage::newFromId( $destTitle->getArticleID() );

        $extensions = [
            'mergedHistory' => true,
            'wasSource' => false,
            'wasDestination' => true,
            'redirectFrom' => ResourceIRI::wikiPage($sourceWikiPage->getId())
        ];
        $event = (new Event())
            ->setAction(new Action(Action::MODIFIED))
            ->setObject(CaliperEntity::wikiPage($destWikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);

        $extensions = [
            'mergedHistory' => true,
            'wasSource' => true,
            'wasDestination' => false
        ];
        $event = (new Event())
            ->setAction(new Action(Action::MODIFIED))
            ->setObject(CaliperEntity::wikiPage($sourceWikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);
    }

    /*
    Modified WikiPage Event (delete/restore revisions)
    Delete Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=48",
        "type": "Document",
        "name": "New Zombie Merge History",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:46:05.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie_Merge_History",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=98"
      },
      "eventTime": "2018-11-02T21:52:40.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:813370f7-8d77-40b7-b140-82141fd3fce9",
      "extensions": {
        "modifyRevisionVisibility": true,
        "revisions": {
          "http://localhost:8888/index.php?oldid=95": {
            "oldBits": 0,
            "newBits": 7
          },
          "http://localhost:8888/index.php?oldid=94": {
            "oldBits": 0,
            "newBits": 7
          },
          "http://localhost:8888/index.php?oldid=93": {
            "oldBits": 0,
            "newBits": 7
          }
        },
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie_Merge_History&action=revisiondelete&type=revision&ids%5B95%5D=1&ids%5B94%5D=1&ids%5B93%5D=1",
          "ipAddress": "192.168.80.1"
        }
      }
    }

    Restore Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Modified",
      "object": {
        "id": "http://localhost:8888/index.php?curid=48",
        "type": "Document",
        "name": "New Zombie Merge History",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:46:05.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie_Merge_History",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=98"
      },
      "eventTime": "2018-11-02T21:53:24.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:5b09374c-01a2-449b-8ce6-618ee2d78e7e",
      "extensions": {
        "modifyRevisionVisibility": true,
        "revisions": {
          "http://localhost:8888/index.php?oldid=95": {
            "oldBits": 7,
            "newBits": 0
          },
          "http://localhost:8888/index.php?oldid=94": {
            "oldBits": 7,
            "newBits": 0
          },
          "http://localhost:8888/index.php?oldid=93": {
            "oldBits": 7,
            "newBits": 0
          }
        },
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=Special:RevisionDelete&action=submit",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onArticleRevisionVisibilitySet($title, $ids, $visibilityChangeMap)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $wikiPage = \WikiPage::newFromId( $title->getArticleID() );
        $revisions = [];
        foreach ($visibilityChangeMap as $revisionId => $changes) {
            $revisions[ResourceIRI::wikiPageRevision($revisionId)] = $changes;
        }

        $extensions = [
            'modifyRevisionVisibility' => true,
            'revisions' => $revisions
        ];

        $event = (new Event())
            ->setAction(new Action(Action::MODIFIED))
            ->setObject(CaliperEntity::wikiPage($wikiPage))
            ->setExtensions($extensions);

        CaliperSensor::sendEvent($event);
    }

    /*
    Subscribed WikiPage Event
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Subscribed",
      "object": {
        "id": "http://localhost:8888/index.php?curid=46",
        "type": "Document",
        "name": "New Zombie",
        "dateCreated": "2018-11-02T21:14:01.000Z",
        "dateModified": "2018-11-02T21:14:01.000Z",
        "creators": [
          {
            "id": "http://media_wiki/MKO098PUID",
            "type": "Person",
            "name": "Teacher1",
            "dateCreated": "2018-10-25T18:58:07.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/New_Zombie",
          "type": "WebPage"
        },
        "datePublished": "2018-11-02T21:14:01.000Z",
        "version": "http://localhost:8888/index.php?oldid=90"
      },
      "eventTime": "2018-11-02T21:14:01.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:90d4279e-6248-4006-91cf-0d89a411c86c",
      "extensions": {
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=edit",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onWatchArticleComplete($user, $wikiPage)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $event = (new Event())
            ->setAction(new Action(Action::SUBSCRIBED))
            ->setObject(CaliperEntity::wikiPage($wikiPage));

        CaliperSensor::sendEvent($event);
    }

    /*
    Unsubscribed WikiPage Event
    Example:
    {
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Unsubscribed",
      "object": {
        "id": "http://localhost:8888/New_Zombie",
        "type": "WebPage",
        "extensions": {
          "wikiPageExists": false
        }
      },
      "eventTime": "2018-11-02T21:17:17.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:36094fac-0b1c-4071-a1f9-ce9148dbf2de",
      "extensions": {
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie&action=delete",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onUnwatchArticleComplete($user, $wikiPage)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $event = (new Event())
            ->setAction(new Action(Action::UNSUBSCRIBED))
            ->setObject(CaliperEntity::wikiPage($wikiPage));

        CaliperSensor::sendEvent($event);
    }

    /*
    Review WikiPage Revision Event
    Example:
{
      "@context": "http://purl.imsglobal.org/ctx/caliper/v1p1",
      "type": "Event",
      "actor": {
        "id": "http://media_wiki/MKO098PUID",
        "type": "Person",
        "name": "Teacher1",
        "dateCreated": "2018-10-25T18:58:07.000Z"
      },
      "action": "Reviewed",
      "object": {
        "id": "http://localhost:8888/index.php?oldid=101",
        "type": "Document",
        "name": "New Zombie Merge History",
        "extensions": {
          "comment": ""
        },
        "dateCreated": "2018-11-02T21:54:32.000Z",
        "dateModified": "2018-11-02T21:54:32.000Z",
        "creators": [
          {
            "id": "http://media_wiki/ABC123PUID",
            "type": "Person",
            "name": "Lastname",
            "dateCreated": "2018-10-25T19:47:46.000Z"
          }
        ],
        "isPartOf": {
          "id": "http://localhost:8888/index.php?curid=48",
          "type": "Document",
          "name": "New Zombie Merge History",
          "dateCreated": "2018-11-02T21:14:01.000Z",
          "dateModified": "2018-11-02T21:54:32.000Z",
          "creators": [
            {
              "id": "http://media_wiki/MKO098PUID",
              "type": "Person",
              "name": "Teacher1",
              "dateCreated": "2018-10-25T18:58:07.000Z"
            },
            {
              "id": "http://media_wiki/ABC123PUID",
              "type": "Person",
              "name": "Lastname",
              "dateCreated": "2018-10-25T19:47:46.000Z"
            }
          ],
          "isPartOf": {
            "id": "http://localhost:8888/New_Zombie_Merge_History",
            "type": "WebPage"
          },
          "datePublished": "2018-11-02T21:14:01.000Z",
          "version": "http://localhost:8888/index.php?oldid=101"
        },
        "datePublished": "2018-11-02T21:54:32.000Z"
      },
      "eventTime": "2018-11-02T21:55:01.000Z",
      "edApp": {
        "id": "http://localhost:8888",
        "type": "SoftwareApplication",
        "name": "MediaWiki",
        "version": "1.30.0"
      },
      "session": {
        "id": "http://localhost:8888/session/rsr5fd5imrkbovn88fofct9dt4fp6g1c",
        "type": "Session",
        "user": {
          "id": "http://media_wiki/MKO098PUID",
          "type": "Person",
          "name": "Teacher1",
          "dateCreated": "2018-10-25T18:58:07.000Z"
        }
      },
      "id": "urn:uuid:8b01fd84-cb5e-4438-adf4-1a6d945620d8",
      "extensions": {
        "browser-info": {
          "userAgent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:63.0) Gecko/20100101 Firefox/63.0",
          "referer": "http://localhost:8080/index.php?title=New_Zombie_Merge_History&diff=101&oldid=100",
          "ipAddress": "192.168.80.1"
        }
      }
    }
    */
    public static function onMarkPatrolledComplete($rcid, $user, $wcOnlySysopsCanPatrol)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $recentChange = \RecentChange::newFromId($rcid);
        $revision = \Revision::newFromId( $recentChange->getAttribute('rc_this_oldid') );

        $event = (new Event())
            ->setAction(new Action(Action::REVIEWED))
            ->setObject(CaliperEntity::wikiPageRevision($revision));

        CaliperSensor::sendEvent($event);
    }

}
