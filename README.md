# mediawiki-extensions-caliper


### Setup Extension

#### Download the extension

Download/copy the project into your MediaWiki extensions folder

    mkdir -p /var/www/html/extensions/caliper
    curl -Ls https://github.com/ubc/mediawiki-extensions-caliper/archive/master.tar.gz | tar xz --strip=1 -C /var/www/html/extensions/caliper

#### Install the extension

Caliper events are emitted using the MediaWiki job queue. It is highly recommended to use run the jobs in cron mode as each page view will generate at least one Caliper event.

Assuming your are working from the MediaWiki project folder

Add in `LocalSettings.php`

    $wgJobRunRate = 0

You should also add the following into `LocalSettings.php` for passing Caliper settings via environment variables

    $wgCaliperHost = loadenv('CaliperHost');
    $wgCaliperAPIKey = loadenv('CaliperAPIKey');
    $wgCaliperAppBaseUrl = loadenv('CaliperAppBaseUrl', null);
    $wgCaliperUseJobQueue = loadenv('wgCaliperUseJobQueue', true);


Next you need to load the extension by adding the following to `CustomExtensions.php`

    wfLoadExtension('caliper');


Finally you can run the job queue with:

    php maintenance/runJobs.php

### Settings

`CaliperHost`: The Caliper LRS endpoint (default: null).

`CaliperAPIKey`: The Caliper LRS endpoint API key (default: null).

`CaliperAppBaseUrl`: If set, will override the base url used by the request (default: null). This is useful for ensure that Internationalized Resource Identifiers (IRI) are consistent event with changes to the Wiki's url.

`CaliperUseJobQueue` If true, will use MediaWiki's job queue to emit Caliper events (default: true). Requires cron capabilities to use effectively (using the end of requests is likely not a good idea since each page view generates at least one Caliper event). If off, events will be emitted immediately. This may cause problems if there are any issues with the LRS.

Both `CaliperHost` and `CaliperAPIKey` settings but be set to something or else no events will be emitted (treated as extension being disabled).

### Customizing Caliper Actor Object

Use the `SetCaliperActorObject` hook in order to create customized actor objects if needed. You need to create the actor based on external login like LDAP which also uses an external url for id. This hook will only be run for logged in users (unauthorized users will automatically use an anonymous user identifier).

### Event emission failure

If `CaliperUseJobQueue` is enabled, then the MediaWiki job queue will handle retries. If `wgCaliperUseJobQueue` is disabled, failed events are logged (full JSON) and no errors are thrown.
