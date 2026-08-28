<?php
/**
 * BOGO Rule data model.
 *
 * @package Bogofy\Models
 */

namespace Bogofy\Models;

/**
 * Class Rule
 *
 * Represents a BOGO rule.
 */
class Rule {

	/**
	 * Rule types.
	 */
	const TYPE_BUY_X_GET_X            = 'buy_x_get_x';
	const TYPE_BUY_X_GET_Y            = 'buy_x_get_y';
	const TYPE_BUY_CAT_GET_FREE       = 'buy_cat_get_free';
	const TYPE_BUY_X_GET_X_DISCOUNTED = 'buy_x_get_x_discounted';

	/**
	 * Discount types.
	 */
	const DISCOUNT_FREE       = 'free';
	const DISCOUNT_PERCENTAGE = 'percentage';

	/**
	 * Apply to types.
	 */
	const APPLY_SPECIFIC_PRODUCTS   = 'specific_products';
	const APPLY_SPECIFIC_CATEGORIES = 'specific_categories';
	const APPLY_ALL_PRODUCTS        = 'all_products';

	/**
	 * Status types.
	 */
	const STATUS_ACTIVE   = 'active';
	const STATUS_INACTIVE = 'inactive';

	/**
	 * Rule ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Rule title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Rule type.
	 *
	 * @var string
	 */
	public $rule_type = self::TYPE_BUY_X_GET_X;

	/**
	 * Rule status.
	 *
	 * @var string
	 */
	public $status = self::STATUS_ACTIVE;

	/**
	 * Buy quantity.
	 *
	 * @var int
	 */
	public $buy_quantity = 1;

	/**
	 * Free quantity.
	 *
	 * @var int
	 */
	public $free_quantity = 1;

	/**
	 * Discount type.
	 *
	 * @var string
	 */
	public $discount_type = self::DISCOUNT_FREE;

	/**
	 * Discount value.
	 *
	 * @var float
	 */
	public $discount_value = 100.00;

	/**
	 * Apply to.
	 *
	 * @var string
	 */
	public $apply_to = self::APPLY_SPECIFIC_PRODUCTS;

	/**
	 * Buy product IDs.
	 *
	 * @var array
	 */
	public $buy_product_ids = array();

	/**
	 * Free product IDs.
	 *
	 * @var array
	 */
	public $free_product_ids = array();

	/**
	 * Category IDs.
	 *
	 * @var array
	 */
	public $category_ids = array();

	/**
	 * Max free quantity.
	 *
	 * @var int|null
	 */
	public $max_free_qty = null;

	/**
	 * Message template.
	 *
	 * @var string
	 */
	public $message_template = '';

	/**
	 * Priority.
	 *
	 * @var int
	 */
	public $priority = 10;

	/**
	 * Start date.
	 *
	 * @var string|null
	 */
	public $start_date = null;

	/**
	 * End date.
	 *
	 * @var string|null
	 */
	public $end_date = null;

	/**
	 * Created at.
	 *
	 * @var string
	 */
	public $created_at = '';

	/**
	 * Updated at.
	 *
	 * @var string
	 */
	public $updated_at = '';

	/**
	 * Create Rule from database row.
	 *
	 * @param object|array $data Database row.
	 *
	 * @return Rule
	 */
	public static function from_db( $data ) {
		$data = (array) $data;
		$rule = new self();

		$rule->id               = absint( $data['id'] ?? 0 );
		$rule->title            = $data['title'] ?? '';
		$rule->rule_type        = $data['rule_type'] ?? self::TYPE_BUY_X_GET_X;
		$rule->status           = $data['status'] ?? self::STATUS_ACTIVE;
		$rule->buy_quantity     = absint( $data['buy_quantity'] ?? 1 );
		$rule->free_quantity    = absint( $data['free_quantity'] ?? 1 );
		$rule->discount_type    = $data['discount_type'] ?? self::DISCOUNT_FREE;
		$rule->discount_value   = floatval( $data['discount_value'] ?? 100.00 );
		$rule->apply_to         = $data['apply_to'] ?? self::APPLY_SPECIFIC_PRODUCTS;
		$rule->buy_product_ids  = self::decode_json( $data['buy_product_ids'] ?? '' );
		$rule->free_product_ids = self::decode_json( $data['free_product_ids'] ?? '' );
		$rule->category_ids     = self::decode_json( $data['category_ids'] ?? '' );
		$rule->max_free_qty     = isset( $data['max_free_qty'] ) ? absint( $data['max_free_qty'] ) : null;
		$rule->message_template = $data['message_template'] ?? '';
		$rule->priority         = absint( $data['priority'] ?? 10 );
		$rule->start_date       = $data['start_date'] ?? null;
		$rule->end_date         = $data['end_date'] ?? null;
		$rule->created_at       = $data['created_at'] ?? '';
		$rule->updated_at       = $data['updated_at'] ?? '';

		return $rule;
	}

	/**
	 * Create Rule from request data.
	 *
	 * @param array $data Request data.
	 *
	 * @return Rule
	 */
	public static function from_request( $data ) {
		$rule = new self();

		if ( isset( $data['id'] ) ) {
			$rule->id = absint( $data['id'] );
		}

		$rule->title            = sanitize_text_field( $data['title'] ?? '' );
		$rule->rule_type        = sanitize_text_field( $data['rule_type'] ?? self::TYPE_BUY_X_GET_X );
		$rule->status           = sanitize_text_field( $data['status'] ?? self::STATUS_ACTIVE );
		$rule->buy_quantity     = absint( $data['buy_quantity'] ?? 1 );
		$rule->free_quantity    = absint( $data['free_quantity'] ?? 1 );
		$rule->discount_type    = sanitize_text_field( $data['discount_type'] ?? self::DISCOUNT_FREE );
		$rule->discount_value   = floatval( $data['discount_value'] ?? 100.00 );
		$rule->apply_to         = sanitize_text_field( $data['apply_to'] ?? self::APPLY_SPECIFIC_PRODUCTS );
		$rule->buy_product_ids  = array_map( 'absint', (array) ( $data['buy_product_ids'] ?? array() ) );
		$rule->free_product_ids = array_map( 'absint', (array) ( $data['free_product_ids'] ?? array() ) );
		$rule->category_ids     = array_map( 'absint', (array) ( $data['category_ids'] ?? array() ) );
		$rule->max_free_qty     = isset( $data['max_free_qty'] ) && '' !== $data['max_free_qty'] ? absint( $data['max_free_qty'] ) : null;
		$rule->message_template = sanitize_text_field( $data['message_template'] ?? '' );
		$rule->priority         = absint( $data['priority'] ?? 10 );
		$rule->start_date       = ! empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : null;
		$rule->end_date         = ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null;

		return $rule;
	}

	/**
	 * Convert rule to array for database storage.
	 *
	 * @return array
	 */
	public function to_db_array() {
		return array(
			'title'            => $this->title,
			'rule_type'        => $this->rule_type,
			'status'           => $this->status,
			'buy_quantity'     => $this->buy_quantity,
			'free_quantity'    => $this->free_quantity,
			'discount_type'    => $this->discount_type,
			'discount_value'   => $this->discount_value,
			'apply_to'         => $this->apply_to,
			'buy_product_ids'  => wp_json_encode( $this->buy_product_ids ),
			'free_product_ids' => wp_json_encode( $this->free_product_ids ),
			'category_ids'     => wp_json_encode( $this->category_ids ),
			'max_free_qty'     => $this->max_free_qty,
			'message_template' => $this->message_template,
			'priority'         => $this->priority,
			'start_date'       => $this->start_date,
			'end_date'         => $this->end_date,
		);
	}

	/**
	 * Convert rule to array for API response.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'               => $this->id,
			'title'            => $this->title,
			'rule_type'        => $this->rule_type,
			'status'           => $this->status,
			'buy_quantity'     => $this->buy_quantity,
			'free_quantity'    => $this->free_quantity,
			'discount_type'    => $this->discount_type,
			'discount_value'   => $this->discount_value,
			'apply_to'         => $this->apply_to,
			'buy_product_ids'  => $this->buy_product_ids,
			'free_product_ids' => $this->free_product_ids,
			'category_ids'     => $this->category_ids,
			'max_free_qty'     => $this->max_free_qty,
			'message_template' => $this->message_template,
			'priority'         => $this->priority,
			'start_date'       => $this->start_date,
			'end_date'         => $this->end_date,
			'created_at'       => $this->created_at,
			'updated_at'       => $this->updated_at,
		);
	}

	/**
	 * Validate rule data.
	 *
	 * @return true|\WP_Error
	 */
	public function validate() {
		if ( empty( $this->title ) ) {
			return new \WP_Error( 'invalid_title', __( 'Rule title is required.', 'bogofy' ) );
		}

		$valid_types = array(
			self::TYPE_BUY_X_GET_X,
			self::TYPE_BUY_X_GET_Y,
			self::TYPE_BUY_CAT_GET_FREE,
			self::TYPE_BUY_X_GET_X_DISCOUNTED,
		);

		if ( ! in_array( $this->rule_type, $valid_types, true ) ) {
			return new \WP_Error( 'invalid_rule_type', __( 'Invalid rule type.', 'bogofy' ) );
		}

		if ( $this->buy_quantity < 1 ) {
			return new \WP_Error( 'invalid_buy_quantity', __( 'Buy quantity must be at least 1.', 'bogofy' ) );
		}

		if ( $this->free_quantity < 1 ) {
			return new \WP_Error( 'invalid_free_quantity', __( 'Free quantity must be at least 1.', 'bogofy' ) );
		}

		if ( self::DISCOUNT_PERCENTAGE === $this->discount_type ) {
			if ( $this->discount_value <= 0 || $this->discount_value > 100 ) {
				return new \WP_Error( 'invalid_discount', __( 'Discount percentage must be between 1 and 100.', 'bogofy' ) );
			}
		}

		return true;
	}

	/**
	 * Check if rule is currently active (considering dates).
	 *
	 * @return bool
	 */
	public function is_active() {
		if ( self::STATUS_ACTIVE !== $this->status ) {
			return false;
		}

		$now = current_time( 'mysql' );

		if ( $this->start_date && $now < $this->start_date ) {
			return false;
		}

		if ( $this->end_date && $now > $this->end_date ) {
			return false;
		}

		return true;
	}

	/**
	 * Decode JSON string to array.
	 *
	 * @param string $json JSON string.
	 *
	 * @return array
	 */
	private static function decode_json( $json ) {
		if ( empty( $json ) ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Get rule type label.
	 *
	 * @return string
	 */
	public function get_type_label() {
		$labels = array(
			self::TYPE_BUY_X_GET_X            => __( 'Buy X Get X Free', 'bogofy' ),
			self::TYPE_BUY_X_GET_Y            => __( 'Buy X Get Y Free', 'bogofy' ),
			self::TYPE_BUY_CAT_GET_FREE       => __( 'Buy from Category Get Free', 'bogofy' ),
			self::TYPE_BUY_X_GET_X_DISCOUNTED => __( 'Buy X Get X Discounted', 'bogofy' ),
		);

		return $labels[ $this->rule_type ] ?? $this->rule_type;
	}
}
