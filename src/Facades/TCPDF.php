<?php
namespace Elibyy\TCPDF\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \TCPDF
 */
class TCPDF extends Facade
{
	protected static function getFacadeAccessor(){return 'tcpdf';}
}
