<?php

use CaliperExtension\caliper\CaliperSensor;
use MediaWiki\MediaWikiServices;

/**
 * @group Database
 * @covers \CaliperHooks
 * @covers \CaliperExtension\caliper\CaliperSensor
 */
class CaliperHooksTest extends MediaWikiIntegrationTestCase {
    /** @var \Title */
    private $title;

    /** @var \WikiPage */
    private $page;

    /** @var \User */
    private $user;

    /** @var \RequestContext */
    private $requestContext;

    protected function setUp(): void {
        parent::setUp();

        // The extension reads `global $wgCaliper*`, so set the globals directly.
        $this->setMwGlobals([
            'wgCaliperHost' => 'http://caliper.host.org/api/in',
            'wgCaliperAPIKey' => 'some_test_key',
            'wgCaliperAppBaseUrl' => 'http://example.org',
            'wgCaliperUseJobQueue' => true,
        ]);

        $services = MediaWikiServices::getInstance();

        $this->title = $services->getTitleFactory()->newFromText('CaliperFakePage');
        $this->page = $services->getWikiPageFactory()->newFromTitle($this->title);
        $this->user = $this->getTestUser()->getUser();

        $this->requestContext = \RequestContext::getMain();
        $this->requestContext->setTitle($this->title);
        $this->requestContext->setUser($this->user);
    }

    public function testCaliperDisabledWhenHostMissing(): void {
        $this->setMwGlobals(['wgCaliperHost' => null]);
        $this->assertFalse(CaliperSensor::caliperEnabled());
    }

    public function testCaliperEnabledWithFullConfig(): void {
        $this->assertTrue(CaliperSensor::caliperEnabled());
    }

    public function testOnBeforePageDisplayDoesNotThrow(): void {
        $out = $this->requestContext->getOutput();
        $skin = $this->requestContext->getSkin();

        \CaliperHooks::onBeforePageDisplay($out, $skin);
        $this->addToAssertionCount(1);
    }

    public function testOnUserLoginCompleteDoesNotThrow(): void {
        $injectHtml = '';
        \CaliperHooks::onUserLoginComplete($this->user, $injectHtml, true);
        $this->addToAssertionCount(1);
    }

    public function testOnUserLogoutDoesNotThrow(): void {
        \CaliperHooks::onUserLogout($this->user);
        $this->addToAssertionCount(1);
    }

    /**
     * Drives the full save path so PageSaveComplete fires the new
     * onPageSaveComplete handler with a real RevisionRecord + EditResult.
     */
    public function testPageSaveCompleteFiresHookWithoutError(): void {
        $content = new \WikitextContent('Initial content');
        $status = $this->page->doUserEditContent($content, $this->user, 'create page');
        $this->assertTrue($status->isOK());
    }
}
