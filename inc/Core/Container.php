<?php
/**
 * Minimal service container.
 *
 * @package Bogofy\Core
 */

namespace Bogofy\Core;

/**
 * Class Container
 *
 * A tiny dependency-injection container. Services are registered as factory
 * closures and resolved lazily as shared (singleton) instances, so the wiring
 * of the plugin lives in one place instead of being scattered across `new`
 * calls in each class.
 */
class Container {

	/**
	 * Registered service factories, keyed by service id.
	 *
	 * @var array<string, callable>
	 */
	private $factories = array();

	/**
	 * Resolved shared instances, keyed by service id.
	 *
	 * @var array<string, mixed>
	 */
	private $instances = array();

	/**
	 * Register a shared service factory.
	 *
	 * @param string   $id      Service identifier (usually a fully-qualified class name).
	 * @param callable $factory Factory that receives this container and returns the service.
	 *
	 * @return void
	 */
	public function set( $id, callable $factory ) {
		$this->factories[ $id ] = $factory;
		unset( $this->instances[ $id ] );
	}

	/**
	 * Resolve a service, building it once and caching the instance.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException When the service is not registered.
	 */
	public function get( $id ) {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Service "%s" is not registered in the container.', $id )
			);
		}

		$this->instances[ $id ] = call_user_func( $this->factories[ $id ], $this );

		return $this->instances[ $id ];
	}

	/**
	 * Determine whether a service is registered.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		return isset( $this->factories[ $id ] ) || array_key_exists( $id, $this->instances );
	}
}
