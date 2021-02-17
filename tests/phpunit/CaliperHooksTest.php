<?php
/**
 * @group Database
 */
class CaliperHooksTest extends MediaWikiTestCase {
    protected function setUp() {
        parent::setUp();
        global $wgRequest, $wgUser;
        global $wgCaliperHost, $wgCaliperAPIKey,$wgCaliperAppBaseUrl, $wgCaliperUseJobQueue;

        $wgCaliperHost = 'http://caliper.host.org/api/in';
        $wgCaliperAPIKey = 'some_test_key';
        $wgCaliperAppBaseUrl = 'http://example.org';
        $wgCaliperUseJobQueue = true;

        $this->title = Title::newFromText("Fake Page");
        $this->page = WikiPage::factory($this->title);
        $this->user = parent::getTestUser()->getUser();

        $this->requestContext = RequestContext::getMain();
        $this->requestContext->setTitle($this->title);
        $this->requestContext->setUser($this->user);
        $this->request = $this->requestContext->getRequest();
        $this->request->setRequestURL($this->title->getFullURL());
        $this->request->setHeaders(array(

        ));

        $wgRequest = $this->request;
        $wgUser = $this->user;
    }

    protected function tearDown() {
        parent::tearDown();
        global $wgRequest, $wgUser;
        global $wgCaliperHost, $wgCaliperAPIKey,$wgCaliperAppBaseUrl, $wgCaliperUseJobQueue;
        unset( $wgCaliperHost );
        unset( $wgCaliperAPIKey );
        unset( $wgCaliperAppBaseUrl );
        unset( $wgCaliperUseJobQueue );
    }

    public function testOnBeforePageDisplay() {
        global $wgRequest, $wgUser;
        $out = new OutputPage($this->requestContext);
        $skin = $this->requestContext->getSkin();

        error_log("wgRequest1");
        error_log(var_export($wgRequest, true));

        CaliperHooks::onBeforePageDisplay($out, $skin);
        $this->assertTrue( true );
    }
}
