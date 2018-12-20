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

class CaliperHooks {
    // Page Navigation To Event
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

    // Login Event
    public static function onUserLoginComplete(\User &$user, $inject_html, $direct)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return;
        }

        $event = (new SessionEvent())
            ->setAction(new Action(Action::LOGGED_IN))
            ->setObject(CaliperEntity::mediaWiki());

        CaliperSensor::sendEvent($event);
    }

    // Logout Event (cannot use UserLogoutComplete as user no longer set)
    public static function onUserLogout(User &$user)
    {
        if (!CaliperSensor::caliperEnabled()) {
            return true;
        }

        $event = (new SessionEvent())
            ->setAction(new Action(Action::LOGGED_OUT))
            ->setObject(CaliperEntity::mediaWiki());

        CaliperSensor::sendEvent($event);

        return true;
    }

    // Create/Edit WikiPage Event
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

    // Deactivate WikiPage Event (cannot use ArticleDeleteComplete as wikiPage no longer exists)
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

    // Activate WikiPage Event
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

    // Modified WikiPage Event (protect)
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



    // Modified WikiPage Event (change title)
    // Also Create new WikiPage Event (if creating a redirect)
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

    // Modified WikiPage Event (merge history)
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

    // Modified WikiPage Event (delete/restore revisions)
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

    // Subscribed WikiPage Event
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

    // Unsubscribed WikiPage Event
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

    // Review WikiPage Revision Event
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
