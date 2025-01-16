<?php
namespace Elibyy\TCPDF\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \TCPDF AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
 * @method static \TCPDF AddSpotColor($name, $c, $m, $y, $k)
 * @method static \TCPDF AddSpotColorHtml($name, $c, $m, $y, $k)
 * @method static \TCPDF AddTTFFont($fontfamily, $fontstyle, $filename, $subset = 'default', $enc = '', $embed = true)
 * @method static \TCPDF SetTitle($title, $isUTF8 = false)
 * @method static \TCPDF SetSubject($subject, $isUTF8 = false)
 * @method static \TCPDF SetAuthor($author, $isUTF8 = false)
 * @method static \TCPDF SetKeywords($keywords, $isUTF8 = false)
 * @method static \TCPDF SetHeaderData($ln = '', $lw = 0, $ht = '', $hs = '', $tc = array(0, 0, 0), $lc = array(0, 0, 0))
 * @method static \TCPDF SetCreator($creator, $isUTF8 = false)
 * @method static \TCPDF writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '')
 * @method static \TCPDF Write($h, $txt, $link = '')
 * @method static \TCPDF Output($name = '', $dest = '')
 * @method static \TCPDF reset()
 * @method static \TCPDF SetAutoPageBreak($auto, $margin = 0)
 * @method static \TCPDF SetMargins($left, $top, $right = -1, $keepmargins = false)
 * @method static \TCPDF SetProtection($permissions = array(), $user_pass = '', $owner_pass = null, $mode = 0, $pubkeys = null)
 * @method static \TCPDF SetPageOrientation($orientation)
 * @method static \TCPDF SetPageFormat($format, $orientation = '')
 * @method static \TCPDF SetImageScale($scale)
 * @method static \TCPDF Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
 * @method static \TCPDF MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false)
 * @method static \TCPDF Ln($h = null)
 * @mixin \TCPDF
 */
class TCPDF extends Facade
{
	protected static function getFacadeAccessor(){return 'tcpdf';}
}
