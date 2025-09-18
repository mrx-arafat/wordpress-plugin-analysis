<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Initialize this version of the REST API.
 *
 * @package Revenue
 */

namespace Revenue;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Revenue_Server {
	use SingletonTrait;

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {

		if ( ! isset( $this->controllers['revenue/v1']['campaigns'] ) ) {
			$this->controllers['revenue/v1']['campaigns'] = new Revenue_Campaign_REST_Controller();
		}
		$this->controllers['revenue/v1']['campaigns']->register_routes();

		if ( ! isset( $this->controllers['revenue/v1']['analytics'] ) ) {
			$this->controllers['revenue/v1']['analytics'] = new Revenue_Analytics_REST_Controller();
		}
		$this->controllers['revenue/v1']['analytics']->register_routes();
		if ( ! isset( $this->controllers['revenue/v1']['settings'] ) ) {
			$this->controllers['revenue/v1']['settings'] = new Revenue_Settings_REST_Controller();
		}
		$this->controllers['revenue/v1']['settings']->register_routes();

	}

}
