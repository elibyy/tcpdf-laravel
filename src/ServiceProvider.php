<?php

namespace Elibyy\TCPDF;

use Config;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

/**
 * Class ServiceProvider
 * @version 1.0
 * @package Elibyy\TCPDF
 */
class ServiceProvider extends LaravelServiceProvider
{
	protected $constantsMap = [
		'K_PATH_FONTS' => 'font_directory',
		'K_PATH_IMAGES' => 'image_directory',
		'K_TCPDF_THROW_EXCEPTION_ERROR' => 'tcpdf_throw_exception'
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$configPath = dirname(__FILE__) . '/../config/tcpdf.php';
		$this->mergeConfigFrom($configPath, 'laravel-tcpdf');
		$this->app->singleton('tcpdf', function ($app) {
			return new TCPDF($app);
		});
	}

	public function boot()
	{
		if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
			define('K_TCPDF_EXTERNAL_CONFIG', true);
		}
		foreach ($this->constantsMap as $key => $value) {
			$value = Config::get('laravel-tcpdf.' . $value, null);
			if (!is_null($value) && !defined($key)) {
				if (is_string($value) && strlen($value) == 0) {
					continue;
				}
				define($key, $value);
			}
		}
		$configPath = dirname(__FILE__) . '/../config/tcpdf.php';
		$this->publishes(array($configPath => config_path('tcpdf.php')), 'config');
	}

	public function provides()
	{
		return ['pdf'];
	}
}