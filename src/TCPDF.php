<?php

namespace Elibyy\TCPDF;

class TCPDF
{
	protected static $format;
	
	protected $app;
	/** @var  TCPDFHelper */
	protected $tcpdf;

	public function __construct($app)
	{
		$this->app = $app;
		$this->reset();
	}

	public function reset()
	{
		$this->tcpdf = new TCPDFHelper(
			config('tcpdf.page_orientation', 'P'),
			config('tcpdf.page_unit', 'mm'),
			static::$format ? static::$format : config('tcpdf.page_format', 'A4'),
			config('tcpdf.unicode', true),
			config('tcpdf.encoding', 'UTF-8')
		);
	}

	public static function changeFormat($format)
	{
		static::$format = $format;
	}

	public function __call($method, $args)
	{
		if (method_exists($this->tcpdf, $method)) {
			return call_user_func_array([$this->tcpdf, $method], $args);
		}
		throw new \RuntimeException(sprintf('the method %s does not exists in TCPDF', $method));
	}

	public function setHeaderCallback($headerCallback)
	{
		$this->tcpdf->setHeaderCallback($headerCallback);
	}

	public function setFooterCallback($footerCallback)
	{
		$this->tcpdf->setFooterCallback($footerCallback);
	}
}
