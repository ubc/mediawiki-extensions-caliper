<?php
use CaliperExtension\caliper\CaliperSensor;

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
        $eventJson = $this->params['eventJson'];
        // throws errors allowing job retry
        CaliperSensor::_sendEvent($eventJson, true);
		return true;
	}
}