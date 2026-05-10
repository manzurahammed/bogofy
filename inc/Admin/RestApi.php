<?php
/**
 * REST API endpoints.
 *
 * @package BuyOneGetOne\Admin
 */

namespace BuyOneGetOne\Admin;

use BuyOneGetOne\Models\Rule;
use BuyOneGetOne\Models\RuleRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Class RestApi
 *
 * Handles REST API endpoint registration and callbacks.
 */
class RestApi {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'buy-one-get-one/v1';

	/**
	 * Rule repository.
	 *
	 * @var RuleRepository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new RuleRepository();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		// Rules endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/rules',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rules' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => $this->get_rules_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_rule' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rules/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rule' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_rule' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_rule' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rules/(?P<id>\d+)/status',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => array( $this, 'update_rule_status' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/rules/bulk',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'bulk_action' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Settings endpoints.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Stats endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Product search endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/products/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_products' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'q' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Category search endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/categories/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_categories' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'q' => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Check user permission.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'buy-one-get-one' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Get rules collection parameters.
	 *
	 * @return array
	 */
	private function get_rules_collection_params() {
		return array(
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => 20,
				'sanitize_callback' => 'absint',
			),
			'status'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'  => array(
				'default'           => 'priority',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'    => array(
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get all rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function get_rules( WP_REST_Request $request ) {
		$args = array(
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
			'status'   => $request->get_param( 'status' ),
			'search'   => $request->get_param( 'search' ),
			'orderby'  => $request->get_param( 'orderby' ),
			'order'    => $request->get_param( 'order' ),
		);

		$rules = $this->repository->get_all( $args );
		$total = $this->repository->get_count( $args );

		$data = array();
		foreach ( $rules as $rule ) {
			$data[] = $rule->to_array();
		}

		$response = new WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', ceil( $total / $args['per_page'] ) );

		return $response;
	}

	/**
	 * Get a single rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_rule( WP_REST_Request $request ) {
		$id   = absint( $request->get_param( 'id' ) );
		$rule = $this->repository->get( $id );

		if ( ! $rule ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Rule not found.', 'buy-one-get-one' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $rule->to_array(), 200 );
	}

	/**
	 * Create a new rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_rule( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$rule = Rule::from_request( $data );

		$validation = $rule->validate();
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$id = $this->repository->create( $rule );

		if ( false === $id ) {
			return new WP_Error(
				'create_failed',
				__( 'Failed to create rule.', 'buy-one-get-one' ),
				array( 'status' => 500 )
			);
		}

		$created_rule = $this->repository->get( $id );
		return new WP_REST_Response( $created_rule->to_array(), 201 );
	}

	/**
	 * Update an existing rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_rule( WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );

		$existing = $this->repository->get( $id );
		if ( ! $existing ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Rule not found.', 'buy-one-get-one' ),
				array( 'status' => 404 )
			);
		}

		$data     = $request->get_json_params();
		$data['id'] = $id;
		$rule     = Rule::from_request( $data );

		$validation = $rule->validate();
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$success = $this->repository->update( $rule );

		if ( ! $success ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update rule.', 'buy-one-get-one' ),
				array( 'status' => 500 )
			);
		}

		$updated_rule = $this->repository->get( $id );
		return new WP_REST_Response( $updated_rule->to_array(), 200 );
	}

	/**
	 * Delete a rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_rule( WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );

		$existing = $this->repository->get( $id );
		if ( ! $existing ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Rule not found.', 'buy-one-get-one' ),
				array( 'status' => 404 )
			);
		}

		$success = $this->repository->delete( $id );

		if ( ! $success ) {
			return new WP_Error(
				'delete_failed',
				__( 'Failed to delete rule.', 'buy-one-get-one' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Update rule status.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_rule_status( WP_REST_Request $request ) {
		$id   = absint( $request->get_param( 'id' ) );
		$data = $request->get_json_params();

		$existing = $this->repository->get( $id );
		if ( ! $existing ) {
			return new WP_Error(
				'rule_not_found',
				__( 'Rule not found.', 'buy-one-get-one' ),
				array( 'status' => 404 )
			);
		}

		$status = sanitize_text_field( $data['status'] ?? '' );
		if ( ! in_array( $status, array( Rule::STATUS_ACTIVE, Rule::STATUS_INACTIVE ), true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid status.', 'buy-one-get-one' ),
				array( 'status' => 400 )
			);
		}

		$success = $this->repository->update_status( $id, $status );

		if ( ! $success ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update status.', 'buy-one-get-one' ),
				array( 'status' => 500 )
			);
		}

		$updated_rule = $this->repository->get( $id );
		return new WP_REST_Response( $updated_rule->to_array(), 200 );
	}

	/**
	 * Bulk action on rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function bulk_action( WP_REST_Request $request ) {
		$data   = $request->get_json_params();
		$action = sanitize_text_field( $data['action'] ?? '' );
		$ids    = array_map( 'absint', (array) ( $data['ids'] ?? array() ) );

		if ( empty( $ids ) ) {
			return new WP_Error(
				'no_ids',
				__( 'No rule IDs provided.', 'buy-one-get-one' ),
				array( 'status' => 400 )
			);
		}

		$affected = 0;

		switch ( $action ) {
			case 'delete':
				$affected = $this->repository->bulk_delete( $ids );
				break;

			case 'activate':
				$affected = $this->repository->bulk_update_status( $ids, Rule::STATUS_ACTIVE );
				break;

			case 'deactivate':
				$affected = $this->repository->bulk_update_status( $ids, Rule::STATUS_INACTIVE );
				break;

			default:
				return new WP_Error(
					'invalid_action',
					__( 'Invalid bulk action.', 'buy-one-get-one' ),
					array( 'status' => 400 )
				);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'affected' => $affected,
			),
			200
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		return new WP_REST_Response( Settings::get_all(), 200 );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		$data    = $request->get_json_params();
		$success = Settings::update( $data );

		if ( ! $success ) {
			return new WP_Error(
				'update_failed',
				__( 'Failed to update settings.', 'buy-one-get-one' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response( Settings::get_all(), 200 );
	}

	/**
	 * Get dashboard stats.
	 *
	 * @return WP_REST_Response
	 */
	public function get_stats() {
		$active_rules = $this->repository->get_count( array( 'status' => Rule::STATUS_ACTIVE ) );
		$total_rules  = $this->repository->get_count();

		// Get orders with BOGO items (placeholder - would need order meta implementation).
		$bogo_orders = 0;

		// Get total discount value (placeholder - would need order meta implementation).
		$total_discount = 0;

		return new WP_REST_Response(
			array(
				'active_rules'   => $active_rules,
				'total_rules'    => $total_rules,
				'bogo_orders'    => $bogo_orders,
				'total_discount' => $total_discount,
			),
			200
		);
	}

	/**
	 * Search products.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function search_products( WP_REST_Request $request ) {
		$search = $request->get_param( 'q' );

		$args = array(
			'status'  => 'publish',
			'limit'   => 20,
			's'       => $search,
			'orderby' => 'title',
			'order'   => 'ASC',
		);

		$products = wc_get_products( $args );
		$results  = array();

		foreach ( $products as $product ) {
			$results[] = array(
				'id'    => $product->get_id(),
				'name'  => $product->get_name(),
				'sku'   => $product->get_sku(),
				'price' => $product->get_price(),
				'image' => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
				'type'  => $product->get_type(),
			);
		}

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Search categories.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function search_categories( WP_REST_Request $request ) {
		$search = $request->get_param( 'q' );

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 20,
			'orderby'    => 'name',
			'order'      => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$categories = get_terms( $args );
		$results    = array();

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$results[] = array(
					'id'    => $category->term_id,
					'name'  => $category->name,
					'slug'  => $category->slug,
					'count' => $category->count,
				);
			}
		}

		return new WP_REST_Response( $results, 200 );
	}
}
