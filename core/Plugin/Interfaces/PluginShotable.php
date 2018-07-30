<?php

namespace EApp\Plugin\Interfaces;

interface PluginShotable
{
	/**
	 * The plugin used a short tag
	 *
	 * @return mixed
	 */
	public function toShortTag();

	/**
	 * Does the plugin use a short tag?
	 * @return boolean
	 */
	public function hasShortTag();

	/**
	 * Get plugin content data
	 *
	 * @return string
	 */
	public function getContent();

	/**
	 * Return short name
	 *
	 * @return string
	 */
	public static function getShotName();
}