# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MediaWiki extension that emits IMS Caliper Analytics events to a Learning Record Store (LRS). It hooks into MediaWiki page lifecycle events (page views, logins, edits, deletes, moves, etc.) and converts them into Caliper events delivered over HTTP.

This is a PHP MediaWiki extension (not a standalone application). It must be installed inside a MediaWiki instance under `extensions/caliper/` and loaded via `wfLoadExtension('caliper')`.

## Common Commands

```bash
# Install PHP dependencies (pulls in the vendored caliper-php package from packages/caliper-php.zip)
composer install

# Run the test suite (must be run from a MediaWiki install — these are MediaWiki PHPUnit tests)
php tests/phpunit/phpunit.php extensions/caliper/tests/phpunit/

# Run a single test
php tests/phpunit/phpunit.php extensions/caliper/tests/phpunit/CaliperHooksTest.php

# Process queued Caliper events (in production, this is run via cron)
php maintenance/runJobs.php
```

## Architecture

### Event Pipeline

MediaWiki hook → `CaliperHooks` (`caliper_hooks.php`) → `CaliperSensor::sendEvent()` (`caliper/sensor.php`) → either:

1. **Job queue path** (`$wgCaliperUseJobQueue = true`, default): serialize the event envelope to JSON and enqueue a `CaliperEmitEventJob` (`caliper_event_job.php`). The job runs `CaliperSensor::_sendEvent()` later via `runJobs.php`. Failures throw, allowing job retry.
2. **Synchronous path** (`$wgCaliperUseJobQueue = false`): POST to the LRS immediately via curl. Failures are logged, never thrown — a broken LRS will not break page rendering.

`caliperEnabled()` returns false if either `$wgCaliperHost` or `$wgCaliperAPIKey` is unset, OR if the wiki is in read-only mode and the job queue is enabled (the queue isn't writable in read-only mode, so we deliberately skip emission rather than crash).

### Layers

- `caliper_hooks.php` — Static hook handlers. Each one builds a Caliper event from MediaWiki state, attaches extensions (custom metadata), then calls `CaliperSensor::sendEvent()`. The hook list in `extension.json` is the source of truth for which events are tracked.
- `caliper/sensor.php` — Singleton wrapper around the upstream `IMSGlobal\Caliper\Sensor`. Owns the curl call and the job-queue dispatch. Also exposes timestamp helpers (MediaWiki's `YmdHis` ↔ ISO 8601).
- `caliper/event.php` — `CaliperEvent::addDefaults()` stamps every outgoing event with the actor, session, edApp, and eventTime. Called from `CaliperSensor::sendEvent()` before serialization.
- `caliper/actor.php` — Generates the Caliper actor (`Person`) for the current user. Anonymous users get `Person::makeAnonymous()`. Logged-in users go through the **`SetCaliperActorObject` hook** first (lets host wikis swap in LDAP/SSO identifiers), and only fall back to a default Wikimedia-style actor if no consumer overrides it.
- `caliper/entity.php` — Builds Caliper entities (WikiPage `Document`, `WebPage`, `Session`, `SoftwareApplication` for client + edApp).
- `caliper/resource_iri.php` — Builds the IRIs that identify wiki pages, revisions, sessions, and the actor homepage. Honors `$wgCaliperAppBaseUrl` to keep IRIs stable when the wiki URL changes; otherwise derives the base from the current request.

### Configuration

Defined in `extension.json` and consumed as PHP globals:

| Global | Purpose |
|---|---|
| `$wgCaliperHost` | LRS endpoint URL |
| `$wgCaliperAPIKey` | LRS bearer token (sent as `Authorization: Bearer <key>`) |
| `$wgCaliperAppBaseUrl` | Override base URL used when constructing IRIs — important so IRIs stay stable across deployments |
| `$wgCaliperUseJobQueue` | Toggle async (default) vs sync emission |

### Vendored Caliper PHP Library

`composer.json` does **not** install `1EdTech/caliper-php` from Packagist. Instead it declares a local `package` repository pointing at `packages/caliper-php.zip` (a snapshot of `1EdTech/caliper-php` at commit `c7e34e2`, version pinned as `imsglobal/caliper:1.2.0`). When upgrading the upstream library, replace the zip and bump the `reference` comment — do not switch the source to Packagist without coordinating with downstream wikis.

### Filtering Out Noise in `onBeforePageDisplay`

`onBeforePageDisplay` is the hottest hook (every page view). It deliberately drops:
- requests for the favicon (matched by basename of `$wgFavicon`)
- Kubernetes liveness/readiness probes (matched by `kube-probe` substring in the User-Agent)

Add new probe filters here rather than higher in the stack.

## Testing Notes

Tests extend `MediaWikiTestCase` and require a running MediaWiki test environment — they cannot be run with plain `vendor/bin/phpunit` from this directory. The existing `CaliperHooksTest` sets fake `$wgCaliper*` globals in `setUp()` and unsets them in `tearDown()` so other tests aren't polluted.

## MediaWiki Compatibility

Targets MediaWiki ≥ 1.40. Earlier hook/API names were migrated:

- `PageContentSaveComplete` → `PageSaveComplete` (signature now uses `RevisionRecord` + `EditResult`; `isUndo` is derived from `EditResult::isRevert() && getRevertMethod() === REVERT_UNDO`, `baseRevId` from `RevisionRecord::getParentId()`).
- `TitleMoveComplete` → `PageMoveComplete`.
- `\Hooks::run('SetCaliperActorObject', ...)` → `MediaWikiServices::getInstance()->getHookContainer()->run(...)`.
- `\Revision::newFromId` → `RevisionLookup::getRevisionById`.
- `\WikiPage::newFromId` / `\WikiPage::factory` → `WikiPageFactory::newFromID` / `newFromTitle`.
- `\User::newFromId` → `UserFactory::newFromId`.
- `JobQueueGroup::singleton()` → `MediaWikiServices::getInstance()->getJobQueueGroup()`.
- `WikiPage::getRevision()` / `WikiPage::getUser()` → `WikiPage::getRevisionRecord()` and read user via `RevisionRecord::getUser()`.

Several still-emitted-but-deprecated hooks are kept as-is for now (`ArticleDelete`, `ArticleProtectComplete`, `ArticleMergeComplete`, `ArticleRevisionVisibilitySet`, `WatchArticleComplete`, `UnwatchArticleComplete`, `MarkPatrolledComplete`). When MediaWiki removes them, migrate to `PageDeleteComplete`, `PageMoveComplete` (already done), `WatchedItem*` hooks, etc.
