<?php
/**
 * StoreApi tests.
 *
 * @package Bogofy\Tests\Unit\Frontend
 */

namespace {

	if ( ! class_exists( 'Bogo_Cart_Product' ) ) {
		class Bogo_Cart_Product {
			private $regular;
			private $price;

			public function __construct( $regular, $price ) {
				$this->regular = $regular;
				$this->price   = $price;
			}

			public function get_regular_price() {
				return $this->regular;
			}

			public function get_price() {
				return $this->price;
			}
		}
	}

	if ( ! class_exists( 'WC_Cart' ) ) {
		class WC_Cart {
			private $items;

			public function __construct( $items = array() ) {
				$this->items = $items;
			}

			public function get_cart() {
				return $this->items;
			}
		}
	}

	if ( ! function_exists( 'WC' ) ) {
		function WC() {
			return (object) array( 'cart' => $GLOBALS['__bogo_cart'] ?? null );
		}
	}
}

namespace Bogofy\Tests\Unit\Frontend {

	use Bogofy\Cart\CartHandler;
	use Bogofy\Frontend\StoreApi;
	use Brain\Monkey;
	use PHPUnit\Framework\TestCase;

	/**
	 * Class StoreApiTest
	 */
	class StoreApiTest extends TestCase {

		/**
		 * Set up test fixtures.
		 */
		protected function setUp(): void {
			parent::setUp();
			bogo_tests_setup();
		}

		/**
		 * Tear down test fixtures.
		 */
		protected function tearDown(): void {
			$GLOBALS['__bogo_cart'] = null;
			bogo_tests_teardown();
			parent::tearDown();
		}

		/**
		 * cart_item_data flags free BOGO items.
		 */
		public function test_cart_item_data_flags_free_item() {
			$api = new StoreApi();

			$free = array( CartHandler::BOGO_ITEM_KEY => true );
			$this->assertTrue( $api->cart_item_data( $free )['is_free'] );
			$this->assertFalse( $api->cart_item_data( array() )['is_free'] );
		}

		/**
		 * cart_data returns raw minor units + currency shape (no HTML).
		 */
		public function test_cart_data_returns_raw_savings_and_currency() {
			Monkey\Functions\when( 'wc_get_price_decimals' )->justReturn( 2 );
			Monkey\Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
			Monkey\Functions\when( 'get_woocommerce_currency_symbol' )->justReturn( '$' );
			Monkey\Functions\when( 'get_option' )->alias(
				function ( $key, $default_value = false ) {
					return 'woocommerce_currency_pos' === $key ? 'left' : $default_value;
				}
			);

			// Free gift line: regular 20, priced 0, qty 2 => 40.00 saved.
			$GLOBALS['__bogo_cart'] = new \WC_Cart(
				array(
					array(
						CartHandler::BOGO_RULE_KEY => 4,
						'data'                     => new \Bogo_Cart_Product( 20.0, 0.0 ),
						'quantity'                 => 2,
					),
				)
			);

			$data = ( new StoreApi() )->cart_data();

			$this->assertSame( 4000, $data['savings_minor'] );
			$this->assertSame( 'USD', $data['currency_code'] );
			$this->assertSame( 2, $data['currency_minor_unit'] );
			$this->assertSame( '$', $data['currency_prefix'] );
			$this->assertSame( '', $data['currency_suffix'] );
			$this->assertArrayNotHasKey( 'savings_html', $data );
		}
	}
}
