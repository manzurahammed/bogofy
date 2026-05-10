<?php
/**
 * Hook and filter registration manager.
 *
 * @package BuyOneGetOne\Core
 */

namespace BuyOneGetOne\Core;

/**
 * Class Loader
 *
 * Manages the registration of all hooks and filters.
 */
class Loader {

	/**
	 * Array of actions to register.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Array of filters to register.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Add an action to the collection.
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Component object.
	 * @param string $callback      Callback method.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 *
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter to the collection.
	 *
	 * @param string $hook          Hook name.
	 * @param object $component     Component object.
	 * @param string $callback      Callback method.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 *
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add hook to collection.
	 *
	 * @param array  $hooks         Hooks array.
	 * @param string $hook          Hook name.
	 * @param object $component     Component object.
	 * @param string $callback      Callback method.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Number of accepted arguments.
	 *
	 * @return array
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
