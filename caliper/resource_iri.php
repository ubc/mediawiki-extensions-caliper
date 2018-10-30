<?php
namespace CaliperExtension\caliper;


class ResourceIRI {
    public static function getBaseUrl() {
        global $wgCaliperAppBaseUrl;
        global $wgRequest;

        if ($wgCaliperAppBaseUrl) {
            return rtrim($wgCaliperAppBaseUrl, '/');
        }

        $baseUrl = $wgRequest->getFullRequestURL();
        $baseUrl = str_replace($wgRequest->getRequestURL(), "", $baseUrl);
        return rtrim($baseUrl, '/');
    }

    public static function media_wiki() {
        return self::getBaseUrl();
    }

    public static function actor_homepage($user_id) {
        if (!is_string($user_id)) {
            throw new \InvalidArgumentException(__METHOD__ . ': string expected');
        }
        return self::getBaseUrl() . "/User:".$user_id;
    }

    public static function user_session($session_id) {
        if (!is_string($session_id)) {
            throw new \InvalidArgumentException(__METHOD__ . ': string expected');
        }
        return self::getBaseUrl() . '/session/' . $session_id;
    }

    public static function wikiPage($wikiPageId) {
        if (!is_integer($wikiPageId)) {
            throw new \InvalidArgumentException(__METHOD__ . ': integer expected');
        }
        return self::getBaseUrl() . "/index.php?curid=" . $wikiPageId;
    }

    public static function wikiPageRevision($revisionId) {
        if (!is_integer($revisionId)) {
            throw new \InvalidArgumentException(__METHOD__ . ': integer expected');
        }
        return self::getBaseUrl() . "/index.php?oldid=" . $revisionId;
    }

    public static function webpage($relativePath) {
        if (!is_string($relativePath)) {
            throw new \InvalidArgumentException(__METHOD__ . ': string expected');
        }
        # remove query string from relativePath if present
        return self::getBaseUrl() . "/" . $relativePath;
    }

}