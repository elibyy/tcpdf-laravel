<?php

namespace Elibyy\TCPDF;

use Illuminate\Support\Facades\Config;

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
        $class = Config::get('tcpdf.use_fpdi') ? FpdiTCPDFHelper::class : TCPDFHelper::class;

        $this->tcpdf = new $class(
            Config::get('tcpdf.page_orientation', 'P'),
            Config::get('tcpdf.page_units', 'mm'),
            static::$format ? static::$format : Config::get('tcpdf.page_format', 'A4'),
            Config::get('tcpdf.unicode', true),
            Config::get('tcpdf.encoding', 'UTF-8')
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
