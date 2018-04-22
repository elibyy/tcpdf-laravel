<?php

namespace Elibyy\TCPDF;

use Illuminate\Support\Facades\Config;
use setasign\Fpdi\TcpdfFpdi;

class FpdiTCPDFHelper extends TcpdfFpdi
{
    protected $headerCallback;

    protected $footerCallback;

    public function Header()
    {
        if ($this->headerCallback != null && is_callable($this->headerCallback)) {
            $cb = $this->headerCallback;
            $cb($this);
        } else {
            if (Config::get('tcpdf.use_original_header')) {
                parent::Header();
            }
        }
    }

    public function Footer()
    {
        if ($this->footerCallback != null && is_callable($this->footerCallback)) {
            $cb = $this->footerCallback;
            $cb($this);
        } else {
            if (Config::get('tcpdf.use_original_footer')) {
                parent::Footer();
            }
        }
    }

    public function setHeaderCallback($callback)
    {
        $this->headerCallback = $callback;
    }

    public function setFooterCallback($callback)
    {
        $this->footerCallback = $callback;
    }

}