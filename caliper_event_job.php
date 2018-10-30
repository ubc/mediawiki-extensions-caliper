<?php
use CaliperExtension\caliper\CaliperSensor;

// IMPORTANT NOTE: Caliper library event objects are sent in params
// This is normally a really bad idea since updates to the library or this extension
// can cause errors/loss of events
// BUT the Caliper library currently doesn't support converting JSON into a Caliper event
// so we can't do the better option of serializing the event for the job queue params
// and then de-serializing back into a Caliper library event in the job.
class CaliperEmitEventJob extends \Job {
	public function __construct( $title, $params ) {
		parent::__construct( 'caliperEmitEvent', $title, $params );
	}

	/**
	 * Execute the job
	 *
	 * @return bool
	 */
	public function run() {
        $event = $this->params['event'];
        CaliperSensor::_sendEvent($event);
		return true;
	}
}