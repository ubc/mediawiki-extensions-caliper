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


Next you need to load the extension by adding the following to `CustomExtensions.php`

    wfLoadExtension('caliper');


Finally you need to add the database table for failed jobs using

    php maintenance/update.php

### Settings

`CaliperHost`: The Caliper LRS endpoint (default: null).

`CaliperAPIKey`: The Caliper LRS endpoint API key (default: null).

`CaliperAppBaseUrl`: If set, will override the base url used by the request (default: null). This is useful for ensure that Internationalized Resource Identifiers (IRI) are consistent event with changes to the Wiki's url.

`CaliperUseJobQueue` If true, will use MediaWiki's job queue to emit Caliper events (default: true). Requires cron capabilities to use effectively (using the end of requests is likely not a good idea since each page view generates at least one Caliper event). If off, events will be emitted immediately. This may cause problems if there are any issues with the LRS.

Both `CaliperHost` and `CaliperAPIKey` settings but be set to something or else no events will be emitted (treated as extension being disabled).

### Customizing Caliper Actor Object

Use the `SetCaliperActorObject` hook in order to create customized actor objects if needed. You need to create the actor based on external login like LDAP which also uses an external url for id. This hook will only be run for logged in users (unauthorized users will automatically use an anonymous user identifier).

### Event emission failure

Failed event emits have the full envelope json stored in the `caliper_failed_events` table along with the error message. Currently these events are not send again (future addition).

### CaliperEmitEventJob shortcomings

The Caliper library event objects are sent in params. This is normally a really bad idea since updates to the library or this extension can cause errors/loss of events. BUT the Caliper library currently doesn't support converting JSON into a Caliper event so we can't do the better option of serializing the event for the job queue params and then de-serializing back into a Caliper library event in the job.

For similar reasons failed events are not retried (since there is no easy way to load the event/envelope json).