<?php

namespace Elibyy\TCPDF;

use TCPDF_COLORS;
use TCPDF_STATIC;

class TCPDFHelper extends \TCPDF
{
    protected $headerCallback;

    protected $footerCallback;

    public function Header()
    {
        if ($this->headerCallback != null && is_callable($this->headerCallback)) {
            $cb = $this->headerCallback;
            $cb($this);
        }
    }

    public function Footer()
    {
        if ($this->footerCallback != null && is_callable($this->footerCallback)) {
            $cb = $this->footerCallback;
            $cb($this);
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

    /**
     * Adds a javascript form field.
     *
     * @param $type (string) field type
     * @param $name (string) field name
     * @param $x (int) horizontal position
     * @param $y (int) vertical position
     * @param $w (int) width
     * @param $h (int) height
     * @param $prop (array) javascript field properties. Possible values are described on official Javascript for
     *              Acrobat API reference.
     *
     * @protected
     * @author Denis Van Nuffelen, Nicola Asuni
     * @since  2.1.002 (2008-02-12)
     */
    protected function _addfield($type, $name, $x, $y, $w, $h, $prop)
    {
        if ($this->rtl) {
            $x = $x - $w;
        }
        // the followind avoid fields duplication after saving the document
        $this->javascript .= "if (getField('tcpdfdocsaved').value != 'saved') {";
        $k = $this->k;
        $this->javascript .= sprintf("f" . $name . "=this.addField('%s','%s',%u,[%F,%F,%F,%F]);", $name, $type,
                $this->PageNo() - 1, $x * $k, ($this->h - $y) * $k + 1, ($x + $w) * $k,
                ($this->h - $y - $h) * $k + 1) . "\n";
        $this->javascript .= 'f' . $name . '.textSize=' . $this->FontSizePt . ";\n";
        foreach ($prop as $key => $val) {
            if (strcmp(substr($key, -5), 'Color') == 0) {
                $val = TCPDF_COLORS::_JScolor($val);
            } else {
                $val = "'" . $val . "'";
            }
            $this->javascript .= 'f' . $name . '.' . $key . '=' . $val . ";\n";
        }
        if ($this->rtl) {
            $this->x -= $w;
        } else {
            $this->x += $w;
        }
        $this->javascript .= '}';
    }

    /**
     * Returns the HTML DOM array.
     *
     * @param $html (string) html code
     *
     * @return array
     * @protected
     * @since 3.2.000 (2008-06-20)
     */
    protected function getHtmlDomArray($html)
    {
        // array of CSS styles ( selector => properties).
        $css = [];
        // get CSS array defined at previous call
        $matches = [];
        if (preg_match_all('/<cssarray>([^\<]*)<\/cssarray>/isU', $html, $matches) > 0) {
            if (isset($matches[1][0])) {
                $css = array_merge($css, json_decode($this->unhtmlentities($matches[1][0]), true));
            }
            $html = preg_replace('/<cssarray>(.*?)<\/cssarray>/isU', '', $html);
        }
        // extract external CSS files
        $matches = [];
        if (preg_match_all('/<link([^\>]*)>/isU', $html, $matches) > 0) {
            foreach ($matches[1] as $key => $link) {
                $type = [];
                if (preg_match('/type[\s]*=[\s]*"text\/css"/', $link, $type)) {
                    $type = [];
                    preg_match('/media[\s]*=[\s]*"([^"]*)"/', $link, $type);
                    // get 'all' and 'print' media, other media types are discarded
                    // (all, braille, embossed, handheld, print, projection, screen, speech, tty, tv)
                    if (empty($type) OR (isset($type[1]) AND (($type[1] == 'all') OR ($type[1] == 'print')))) {
                        $type = [];
                        if (preg_match('/href[\s]*=[\s]*"([^"]*)"/', $link, $type) > 0) {
                            // read CSS data file
                            $cssdata = TCPDF_STATIC::fileGetContents(trim($type[1]));
                            if (($cssdata !== false) AND (strlen($cssdata) > 0)) {
                                $css = array_merge($css, TCPDF_STATIC::extractCSSproperties($cssdata));
                            }
                        }
                    }
                }
            }
        }
        // extract style tags
        $matches = [];
        if (preg_match_all('/<style([^\>]*)>([^\<]*)<\/style>/isU', $html, $matches) > 0) {
            foreach ($matches[1] as $key => $media) {
                $type = [];
                preg_match('/media[\s]*=[\s]*"([^"]*)"/', $media, $type);
                // get 'all' and 'print' media, other media types are discarded
                // (all, braille, embossed, handheld, print, projection, screen, speech, tty, tv)
                if (empty($type) OR (isset($type[1]) AND (($type[1] == 'all') OR ($type[1] == 'print')))) {
                    $cssdata = $matches[2][$key];
                    $css = array_merge($css, TCPDF_STATIC::extractCSSproperties($cssdata));
                }
            }
        }
        // create a special tag to contain the CSS array (used for table content)
        $csstagarray = '<cssarray>' . htmlentities(json_encode($css)) . '</cssarray>';
        // remove head and style blocks
        $html = preg_replace('/<head([^\>]*)>(.*?)<\/head>/siU', '', $html);
        $html = preg_replace('/<style([^\>]*)>([^\<]*)<\/style>/isU', '', $html);
        // define block tags
        $blocktags = [
            'blockquote',
            'br',
            'dd',
            'dl',
            'div',
            'dt',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'li',
            'ol',
            'p',
            'pre',
            'ul',
            'tcpdf',
            'table',
            'tr',
            'td',
        ];
        // define self-closing tags
        $selfclosingtags = ['area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta'];
        // remove all unsupported tags (the line below lists all supported tags)
        $html = strip_tags($html,
            '<marker/><a><b><blockquote><body><br><br/><dd><del><div><dl><dt><em><font><form><h1><h2><h3><h4><h5><h6><hr><hr/><i><img><input><label><li><ol><option><p><pre><s><select><small><span><strike><strong><sub><sup><table><tablehead><tcpdf><td><textarea><th><thead><tr><tt><u><ul>');
        //replace some blank characters
        $html = preg_replace('/<pre/', '<xre', $html); // preserve pre tag
        $html = preg_replace('/<(table|tr|td|th|tcpdf|blockquote|dd|div|dl|dt|form|h1|h2|h3|h4|h5|h6|br|hr|li|ol|ul|p)([^\>]*)>[\n\r\t]+/',
            '<\\1\\2>', $html);
        $html = preg_replace('@(\r\n|\r)@', "\n", $html);
        $repTable = ["\t" => ' ', "\0" => ' ', "\x0B" => ' ', "\\" => "\\\\"];
        $html = strtr($html, $repTable);
        $offset = 0;
        while (($offset < strlen($html)) AND ($pos = strpos($html, '</pre>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 6));
            while (preg_match("'<xre([^\>]*)>(.*?)\n(.*?)</pre>'si", $html_b)) {
                // preserve newlines on <pre> tag
                $html_b = preg_replace("'<xre([^\>]*)>(.*?)\n(.*?)</pre>'si", "<xre\\1>\\2<br />\\3</pre>", $html_b);
            }
            while (preg_match("'<xre([^\>]*)>(.*?)" . $this->re_space['p'] . "(.*?)</pre>'" . $this->re_space['m'],
                $html_b)) {
                // preserve spaces on <pre> tag
                $html_b = preg_replace("'<xre([^\>]*)>(.*?)" . $this->re_space['p'] . "(.*?)</pre>'" . $this->re_space['m'],
                    "<xre\\1>\\2&nbsp;\\3</pre>", $html_b);
            }
            $html = $html_a . $html_b . substr($html, $pos + 6);
            $offset = strlen($html_a . $html_b);
        }
        $offset = 0;
        while (($offset < strlen($html)) AND ($pos = strpos($html, '</textarea>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 11));
            while (preg_match("'<textarea([^\>]*)>(.*?)\n(.*?)</textarea>'si", $html_b)) {
                // preserve newlines on <textarea> tag
                $html_b = preg_replace("'<textarea([^\>]*)>(.*?)\n(.*?)</textarea>'si",
                    "<textarea\\1>\\2<TBR>\\3</textarea>", $html_b);
                $html_b = preg_replace("'<textarea([^\>]*)>(.*?)[\"](.*?)</textarea>'si",
                    "<textarea\\1>\\2''\\3</textarea>", $html_b);
            }
            $html = $html_a . $html_b . substr($html, $pos + 11);
            $offset = strlen($html_a . $html_b);
        }
        $html = preg_replace('/([\s]*)<option/si', '<option', $html);
        $html = preg_replace('/<\/option>([\s]*)/si', '</option>', $html);
        $offset = 0;
        while (($offset < strlen($html)) AND ($pos = strpos($html, '</option>', $offset)) !== false) {
            $html_a = substr($html, 0, $offset);
            $html_b = substr($html, $offset, ($pos - $offset + 9));
            while (preg_match("'<option([^\>]*)>(.*?)</option>'si", $html_b)) {
                $html_b = preg_replace("'<option([\s]+)value=\"([^\"]*)\"([^\>]*)>(.*?)</option>'si",
                    "\\2#!TaB!#\\4#!NwL!#", $html_b);
                $html_b = preg_replace("'<option([^\>]*)>(.*?)</option>'si", "\\2#!NwL!#", $html_b);
            }
            $html = $html_a . $html_b . substr($html, $pos + 9);
            $offset = strlen($html_a . $html_b);
        }
        if (preg_match("'</select'si", $html)) {
            $html = preg_replace("'<select([^\>]*)>'si", "<select\\1 opt=\"", $html);
            $html = preg_replace("'#!NwL!#</select>'si", "\" />", $html);
        }
        $html = str_replace("\n", ' ', $html);
        // restore textarea newlines
        $html = str_replace('<TBR>', "\n", $html);
        // remove extra spaces from code
        $html = preg_replace('/[\s]+<\/(table|tr|ul|ol|dl)>/', '</\\1>', $html);
        $html = preg_replace('/' . $this->re_space['p'] . '+<\/(td|th|li|dt|dd)>/' . $this->re_space['m'], '</\\1>',
            $html);
        $html = preg_replace('/[\s]+<(tr|td|th|li|dt|dd)/', '<\\1', $html);
        $html = preg_replace('/' . $this->re_space['p'] . '+<(ul|ol|dl|br)/' . $this->re_space['m'], '<\\1', $html);
        $html = preg_replace('/<\/(table|tr|td|th|blockquote|dd|dt|dl|div|dt|h1|h2|h3|h4|h5|h6|hr|li|ol|ul|p)>[\s]+</',
            '</\\1><', $html);
        $html = preg_replace('/<\/(td|th)>/', '<marker style="font-size:0"/></\\1>', $html);
        $html = preg_replace('/<\/table>([\s]*)<marker style="font-size:0"\/>/', '</table>', $html);
        $html = preg_replace('/' . $this->re_space['p'] . '+<img/' . $this->re_space['m'], chr(32) . '<img', $html);
        $html = preg_replace('/<img([^\>]*)>[\s]+([^\<])/xi', '<img\\1>&nbsp;\\2', $html);
        $html = preg_replace('/<img([^\>]*)>/xi', '<img\\1><span><marker style="font-size:0"/></span>', $html);
        $html = preg_replace('/<xre/', '<pre', $html); // restore pre tag
        $html = preg_replace('/<textarea([^\>]*)>([^\<]*)<\/textarea>/xi', '<textarea\\1 value="\\2" />', $html);
        $html = preg_replace('/<li([^\>]*)><\/li>/', '<li\\1>&nbsp;</li>', $html);
        $html = preg_replace('/<li([^\>]*)>' . $this->re_space['p'] . '*<img/' . $this->re_space['m'],
            '<li\\1><font size="1">&nbsp;</font><img', $html);
        $html = preg_replace('/<([^\>\/]*)>[\s]/', '<\\1>&nbsp;', $html); // preserve some spaces
        $html = preg_replace('/[\s]<\/([^\>]*)>/', '&nbsp;</\\1>', $html); // preserve some spaces
        $html = preg_replace('/<su([bp])/', '<zws/><su\\1', $html); // fix sub/sup alignment
        $html = preg_replace('/<\/su([bp])>/', '</su\\1><zws/>', $html); // fix sub/sup alignment
        $html = preg_replace('/' . $this->re_space['p'] . '+/' . $this->re_space['m'], chr(32),
            $html); // replace multiple spaces with a single space
        // trim string
        $html = $this->stringTrim($html);
        // fix br tag after li
        $html = preg_replace('/<li><br([^\>]*)>/', '<li> <br\\1>', $html);
        // fix first image tag alignment
        $html = preg_replace('/^<img/', '<span style="font-size:0"><br /></span> <img', $html, 1);
        // pattern for generic tag
        $tagpattern = '/(<[^>]+>)/';
        // explodes the string
        $a = preg_split($tagpattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        // count elements
        $maxel = count($a);
        $elkey = 0;
        $key = 0;
        // create an array of elements
        $dom = [];
        $dom[$key] = [];
        // set inheritable properties fot the first void element
        // possible inheritable properties are: azimuth, border-collapse, border-spacing, caption-side, color, cursor, direction, empty-cells, font, font-family, font-stretch, font-size, font-size-adjust, font-style, font-variant, font-weight, letter-spacing, line-height, list-style, list-style-image, list-style-position, list-style-type, orphans, page, page-break-inside, quotes, speak, speak-header, text-align, text-indent, text-transform, volume, white-space, widows, word-spacing
        $dom[$key]['tag'] = false;
        $dom[$key]['block'] = false;
        $dom[$key]['value'] = '';
        $dom[$key]['parent'] = 0;
        $dom[$key]['hide'] = false;
        $dom[$key]['fontname'] = $this->FontFamily;
        $dom[$key]['fontstyle'] = $this->FontStyle;
        $dom[$key]['fontsize'] = $this->FontSizePt;
        $dom[$key]['font-stretch'] = $this->font_stretching;
        $dom[$key]['letter-spacing'] = $this->font_spacing;
        $dom[$key]['stroke'] = $this->textstrokewidth;
        $dom[$key]['fill'] = (($this->textrendermode % 2) == 0);
        $dom[$key]['clip'] = ($this->textrendermode > 3);
        $dom[$key]['line-height'] = $this->cell_height_ratio;
        $dom[$key]['bgcolor'] = false;
        $dom[$key]['fgcolor'] = $this->fgcolor; // color
        $dom[$key]['strokecolor'] = $this->strokecolor;
        $dom[$key]['align'] = '';
        $dom[$key]['listtype'] = '';
        $dom[$key]['text-indent'] = 0;
        $dom[$key]['text-transform'] = '';
        $dom[$key]['border'] = [];
        $dom[$key]['dir'] = $this->rtl ? 'rtl' : 'ltr';
        $thead = false; // true when we are inside the THEAD tag
        ++$key;
        $level = [];
        array_push($level, 0); // root
        while ($elkey < $maxel) {
            $dom[$key] = [];
            $element = $a[$elkey];
            $dom[$key]['elkey'] = $elkey;
            if (preg_match($tagpattern, $element)) {
                // html tag
                $element = substr($element, 1, -1);
                // get tag name
                preg_match('/[\/]?([a-zA-Z0-9]*)/', $element, $tag);
                $tagname = strtolower($tag[1]);
                // check if we are inside a table header
                if ($tagname == 'thead') {
                    if ($element[0] == '/') {
                        $thead = false;
                    } else {
                        $thead = true;
                    }
                    ++$elkey;
                    continue;
                }
                $dom[$key]['tag'] = true;
                $dom[$key]['value'] = $tagname;
                if (in_array($dom[$key]['value'], $blocktags)) {
                    $dom[$key]['block'] = true;
                } else {
                    $dom[$key]['block'] = false;
                }
                if ($element[0] == '/') {
                    // *** closing html tag
                    $dom[$key]['opening'] = false;
                    $dom[$key]['parent'] = end($level);
                    array_pop($level);
                    $dom[$key]['hide'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['hide'];
                    $dom[$key]['fontname'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontname'];
                    $dom[$key]['fontstyle'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontstyle'];
                    $dom[$key]['fontsize'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fontsize'];
                    $dom[$key]['font-stretch'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['font-stretch'];
                    $dom[$key]['letter-spacing'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['letter-spacing'];
                    $dom[$key]['stroke'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['stroke'];
                    $dom[$key]['fill'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fill'];
                    $dom[$key]['clip'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['clip'];
                    $dom[$key]['line-height'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['line-height'];
                    $dom[$key]['bgcolor'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['bgcolor'];
                    $dom[$key]['fgcolor'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['fgcolor'];
                    $dom[$key]['strokecolor'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['strokecolor'];
                    $dom[$key]['align'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['align'];
                    $dom[$key]['text-transform'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['text-transform'];
                    $dom[$key]['dir'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['dir'];
                    if (isset($dom[($dom[($dom[$key]['parent'])]['parent'])]['listtype'])) {
                        $dom[$key]['listtype'] = $dom[($dom[($dom[$key]['parent'])]['parent'])]['listtype'];
                    }
                    // set the number of columns in table tag
                    if (($dom[$key]['value'] == 'tr') AND (!isset($dom[($dom[($dom[$key]['parent'])]['parent'])]['cols']))) {
                        $dom[($dom[($dom[$key]['parent'])]['parent'])]['cols'] = $dom[($dom[$key]['parent'])]['cols'];
                    }
                    if (($dom[$key]['value'] == 'td') OR ($dom[$key]['value'] == 'th')) {
                        $dom[($dom[$key]['parent'])]['content'] = $csstagarray;
                        for ($i = ($dom[$key]['parent'] + 1); $i < $key; ++$i) {
                            $dom[($dom[$key]['parent'])]['content'] .= stripslashes($a[$dom[$i]['elkey']]);
                        }
                        $key = $i;
                        // mark nested tables
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('<table', '<table nested="true"',
                            $dom[($dom[$key]['parent'])]['content']);
                        // remove thead sections from nested tables
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('<thead>', '',
                            $dom[($dom[$key]['parent'])]['content']);
                        $dom[($dom[$key]['parent'])]['content'] = str_replace('</thead>', '',
                            $dom[($dom[$key]['parent'])]['content']);
                    }
                    // store header rows on a new table
                    if (($dom[$key]['value'] == 'tr') AND ($dom[($dom[$key]['parent'])]['thead'] === true)) {
                        if (TCPDF_STATIC::empty_string($dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'])) {
                            $dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'] = $csstagarray . $a[$dom[($dom[($dom[$key]['parent'])]['parent'])]['elkey']];
                        }
                        for ($i = $dom[$key]['parent']; $i <= $key; ++$i) {
                            $dom[($dom[($dom[$key]['parent'])]['parent'])]['thead'] .= $a[$dom[$i]['elkey']];
                        }
                        if (!isset($dom[($dom[$key]['parent'])]['attribute'])) {
                            $dom[($dom[$key]['parent'])]['attribute'] = [];
                        }
                        // header elements must be always contained in a single page
                        $dom[($dom[$key]['parent'])]['attribute']['nobr'] = 'true';
                    }
                    if (($dom[$key]['value'] == 'table') AND (!TCPDF_STATIC::empty_string($dom[($dom[$key]['parent'])]['thead']))) {
                        // remove the nobr attributes from the table header
                        $dom[($dom[$key]['parent'])]['thead'] = str_replace(' nobr="true"', '',
                            $dom[($dom[$key]['parent'])]['thead']);
                        $dom[($dom[$key]['parent'])]['thead'] .= '</tablehead>';
                    }
                } else {
                    // *** opening or self-closing html tag
                    $dom[$key]['opening'] = true;
                    $dom[$key]['parent'] = end($level);
                    if ((substr($element, -1, 1) == '/') OR (in_array($dom[$key]['value'], $selfclosingtags))) {
                        // self-closing tag
                        $dom[$key]['self'] = true;
                    } else {
                        // opening tag
                        array_push($level, $key);
                        $dom[$key]['self'] = false;
                    }
                    // copy some values from parent
                    $parentkey = 0;
                    if ($key > 0) {
                        $parentkey = $dom[$key]['parent'];
                        $dom[$key]['hide'] = $dom[$parentkey]['hide'];
                        $dom[$key]['fontname'] = $dom[$parentkey]['fontname'];
                        $dom[$key]['fontstyle'] = $dom[$parentkey]['fontstyle'];
                        $dom[$key]['fontsize'] = $dom[$parentkey]['fontsize'];
                        $dom[$key]['font-stretch'] = $dom[$parentkey]['font-stretch'];
                        $dom[$key]['letter-spacing'] = $dom[$parentkey]['letter-spacing'];
                        $dom[$key]['stroke'] = $dom[$parentkey]['stroke'];
                        $dom[$key]['fill'] = $dom[$parentkey]['fill'];
                        $dom[$key]['clip'] = $dom[$parentkey]['clip'];
                        $dom[$key]['line-height'] = $dom[$parentkey]['line-height'];
                        $dom[$key]['bgcolor'] = $dom[$parentkey]['bgcolor'];
                        $dom[$key]['fgcolor'] = $dom[$parentkey]['fgcolor'];
                        $dom[$key]['strokecolor'] = $dom[$parentkey]['strokecolor'];
                        $dom[$key]['align'] = $dom[$parentkey]['align'];
                        $dom[$key]['listtype'] = $dom[$parentkey]['listtype'];
                        $dom[$key]['text-indent'] = $dom[$parentkey]['text-indent'];
                        $dom[$key]['text-transform'] = $dom[$parentkey]['text-transform'];
                        $dom[$key]['border'] = [];
                        $dom[$key]['dir'] = $dom[$parentkey]['dir'];
                    }
                    // get attributes
                    preg_match_all('/([^=\s]*)[\s]*=[\s]*"([^"]*)"/', $element, $attr_array, PREG_PATTERN_ORDER);
                    $dom[$key]['attribute'] = []; // reset attribute array
                    foreach ($attr_array[1] as $id => $name) {
                        $dom[$key]['attribute'][strtolower($name)] = $attr_array[2][$id];
                    }
                    if (!empty($css)) {
                        // merge CSS style to current style
                        list($dom[$key]['csssel'], $dom[$key]['cssdata']) = TCPDF_STATIC::getCSSdataArray($dom, $key,
                            $css);
                        $dom[$key]['attribute']['style'] = TCPDF_STATIC::getTagStyleFromCSSarray($dom[$key]['cssdata']);
                    }
                    // split style attributes
                    if (isset($dom[$key]['attribute']['style']) AND !empty($dom[$key]['attribute']['style'])) {
                        // get style attributes
                        preg_match_all('/([^;:\s]*):([^;]*)/', $dom[$key]['attribute']['style'], $style_array,
                            PREG_PATTERN_ORDER);
                        $dom[$key]['style'] = []; // reset style attribute array
                        foreach ($style_array[1] as $id => $name) {
                            // in case of duplicate attribute the last replace the previous
                            $dom[$key]['style'][strtolower($name)] = trim($style_array[2][$id]);
                        }
                        // --- get some style attributes ---
                        // text direction
                        if (isset($dom[$key]['style']['direction'])) {
                            $dom[$key]['dir'] = $dom[$key]['style']['direction'];
                        }
                        // display
                        if (isset($dom[$key]['style']['display'])) {
                            $dom[$key]['hide'] = (trim(strtolower($dom[$key]['style']['display'])) == 'none');
                        }
                        // font family
                        if (isset($dom[$key]['style']['font-family'])) {
                            $dom[$key]['fontname'] = $this->getFontFamilyName($dom[$key]['style']['font-family']);
                        }
                        // list-style-type
                        if (isset($dom[$key]['style']['list-style-type'])) {
                            $dom[$key]['listtype'] = trim(strtolower($dom[$key]['style']['list-style-type']));
                            if ($dom[$key]['listtype'] == 'inherit') {
                                $dom[$key]['listtype'] = $dom[$parentkey]['listtype'];
                            }
                        }
                        // text-indent
                        if (isset($dom[$key]['style']['text-indent'])) {
                            $dom[$key]['text-indent'] = $this->getHTMLUnitToUnits($dom[$key]['style']['text-indent']);
                            if ($dom[$key]['text-indent'] == 'inherit') {
                                $dom[$key]['text-indent'] = $dom[$parentkey]['text-indent'];
                            }
                        }
                        // text-transform
                        if (isset($dom[$key]['style']['text-transform'])) {
                            $dom[$key]['text-transform'] = $dom[$key]['style']['text-transform'];
                        }
                        // font size
                        if (isset($dom[$key]['style']['font-size'])) {
                            $fsize = trim($dom[$key]['style']['font-size']);
                            $dom[$key]['fontsize'] = $this->getHTMLFontUnits($fsize, $dom[0]['fontsize'],
                                $dom[$parentkey]['fontsize'], 'pt');
                        }
                        // font-stretch
                        if (isset($dom[$key]['style']['font-stretch'])) {
                            $dom[$key]['font-stretch'] = $this->getCSSFontStretching($dom[$key]['style']['font-stretch'],
                                $dom[$parentkey]['font-stretch']);
                        }
                        // letter-spacing
                        if (isset($dom[$key]['style']['letter-spacing'])) {
                            $dom[$key]['letter-spacing'] = $this->getCSSFontSpacing($dom[$key]['style']['letter-spacing'],
                                $dom[$parentkey]['letter-spacing']);
                        }
                        // line-height (internally is the cell height ratio)
                        if (isset($dom[$key]['style']['line-height'])) {
                            $lineheight = trim($dom[$key]['style']['line-height']);
                            switch ($lineheight) {
                                // A normal line height. This is default
                                case 'normal':
                                    {
                                        $dom[$key]['line-height'] = $dom[0]['line-height'];
                                        break;
                                    }
                                case 'inherit':
                                    {
                                        $dom[$key]['line-height'] = $dom[$parentkey]['line-height'];
                                    }
                                default:
                                    {
                                        if (is_numeric($lineheight)) {
                                            // convert to percentage of font height
                                            $lineheight = ($lineheight * 100) . '%';
                                        }
                                        $dom[$key]['line-height'] = $this->getHTMLUnitToUnits($lineheight, 1, '%',
                                            true);
                                        if (substr($lineheight, -1) !== '%') {
                                            if ($dom[$key]['fontsize'] <= 0) {
                                                $dom[$key]['line-height'] = 1;
                                            } else {
                                                $dom[$key]['line-height'] = (($dom[$key]['line-height'] - $this->cell_padding['T'] - $this->cell_padding['B']) / $dom[$key]['fontsize']);
                                            }
                                        }
                                    }
                            }
                        }
                        // font style
                        if (isset($dom[$key]['style']['font-weight'])) {
                            if (strtolower($dom[$key]['style']['font-weight'][0]) == 'n') {
                                if (strpos($dom[$key]['fontstyle'], 'B') !== false) {
                                    $dom[$key]['fontstyle'] = str_replace('B', '', $dom[$key]['fontstyle']);
                                }
                            } else {
                                if (strtolower($dom[$key]['style']['font-weight'][0]) == 'b') {
                                    $dom[$key]['fontstyle'] .= 'B';
                                }
                            }
                        }
                        if (isset($dom[$key]['style']['font-style']) AND (strtolower($dom[$key]['style']['font-style'][0]) == 'i')) {
                            $dom[$key]['fontstyle'] .= 'I';
                        }
                        // font color
                        if (isset($dom[$key]['style']['color']) AND (!TCPDF_STATIC::empty_string($dom[$key]['style']['color']))) {
                            $dom[$key]['fgcolor'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['style']['color'],
                                $this->spot_colors);
                        } else {
                            if ($dom[$key]['value'] == 'a') {
                                $dom[$key]['fgcolor'] = $this->htmlLinkColorArray;
                            }
                        }
                        // background color
                        if (isset($dom[$key]['style']['background-color']) AND (!TCPDF_STATIC::empty_string($dom[$key]['style']['background-color']))) {
                            $dom[$key]['bgcolor'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['style']['background-color'],
                                $this->spot_colors);
                        }
                        // text-decoration
                        if (isset($dom[$key]['style']['text-decoration'])) {
                            $decors = explode(' ', strtolower($dom[$key]['style']['text-decoration']));
                            foreach ($decors as $dec) {
                                $dec = trim($dec);
                                if (!TCPDF_STATIC::empty_string($dec)) {
                                    if ($dec[0] == 'u') {
                                        // underline
                                        $dom[$key]['fontstyle'] .= 'U';
                                    } else {
                                        if ($dec[0] == 'l') {
                                            // line-through
                                            $dom[$key]['fontstyle'] .= 'D';
                                        } else {
                                            if ($dec[0] == 'o') {
                                                // overline
                                                $dom[$key]['fontstyle'] .= 'O';
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($dom[$key]['value'] == 'a') {
                                $dom[$key]['fontstyle'] = $this->htmlLinkFontStyle;
                            }
                        }
                        // check for width attribute
                        if (isset($dom[$key]['style']['width'])) {
                            $dom[$key]['width'] = $dom[$key]['style']['width'];
                        }
                        // check for height attribute
                        if (isset($dom[$key]['style']['height'])) {
                            $dom[$key]['height'] = $dom[$key]['style']['height'];
                        }
                        // check for text alignment
                        if (isset($dom[$key]['style']['text-align'])) {
                            $dom[$key]['align'] = strtoupper($dom[$key]['style']['text-align'][0]);
                        }
                        // check for CSS border properties
                        if (isset($dom[$key]['style']['border'])) {
                            $borderstyle = $this->getCSSBorderStyle($dom[$key]['style']['border']);
                            if (!empty($borderstyle)) {
                                $dom[$key]['border']['LTRB'] = $borderstyle;
                            }
                        }
                        if (isset($dom[$key]['style']['border-color'])) {
                            $brd_colors = preg_split('/[\s]+/', trim($dom[$key]['style']['border-color']));
                            if (isset($brd_colors[3])) {
                                $dom[$key]['border']['L']['color'] = TCPDF_COLORS::convertHTMLColorToDec($brd_colors[3],
                                    $this->spot_colors);
                            }
                            if (isset($brd_colors[1])) {
                                $dom[$key]['border']['R']['color'] = TCPDF_COLORS::convertHTMLColorToDec($brd_colors[1],
                                    $this->spot_colors);
                            }
                            if (isset($brd_colors[0])) {
                                $dom[$key]['border']['T']['color'] = TCPDF_COLORS::convertHTMLColorToDec($brd_colors[0],
                                    $this->spot_colors);
                            }
                            if (isset($brd_colors[2])) {
                                $dom[$key]['border']['B']['color'] = TCPDF_COLORS::convertHTMLColorToDec($brd_colors[2],
                                    $this->spot_colors);
                            }
                        }
                        if (isset($dom[$key]['style']['border-width'])) {
                            $brd_widths = preg_split('/[\s]+/', trim($dom[$key]['style']['border-width']));
                            if (isset($brd_widths[3])) {
                                $dom[$key]['border']['L']['width'] = $this->getCSSBorderWidth($brd_widths[3]);
                            }
                            if (isset($brd_widths[1])) {
                                $dom[$key]['border']['R']['width'] = $this->getCSSBorderWidth($brd_widths[1]);
                            }
                            if (isset($brd_widths[0])) {
                                $dom[$key]['border']['T']['width'] = $this->getCSSBorderWidth($brd_widths[0]);
                            }
                            if (isset($brd_widths[2])) {
                                $dom[$key]['border']['B']['width'] = $this->getCSSBorderWidth($brd_widths[2]);
                            }
                        }
                        if (isset($dom[$key]['style']['border-style'])) {
                            $brd_styles = preg_split('/[\s]+/', trim($dom[$key]['style']['border-style']));
                            if (isset($brd_styles[3]) AND ($brd_styles[3] != 'none')) {
                                $dom[$key]['border']['L']['cap'] = 'square';
                                $dom[$key]['border']['L']['join'] = 'miter';
                                $dom[$key]['border']['L']['dash'] = $this->getCSSBorderDashStyle($brd_styles[3]);
                                if ($dom[$key]['border']['L']['dash'] < 0) {
                                    $dom[$key]['border']['L'] = [];
                                }
                            }
                            if (isset($brd_styles[1])) {
                                $dom[$key]['border']['R']['cap'] = 'square';
                                $dom[$key]['border']['R']['join'] = 'miter';
                                $dom[$key]['border']['R']['dash'] = $this->getCSSBorderDashStyle($brd_styles[1]);
                                if ($dom[$key]['border']['R']['dash'] < 0) {
                                    $dom[$key]['border']['R'] = [];
                                }
                            }
                            if (isset($brd_styles[0])) {
                                $dom[$key]['border']['T']['cap'] = 'square';
                                $dom[$key]['border']['T']['join'] = 'miter';
                                $dom[$key]['border']['T']['dash'] = $this->getCSSBorderDashStyle($brd_styles[0]);
                                if ($dom[$key]['border']['T']['dash'] < 0) {
                                    $dom[$key]['border']['T'] = [];
                                }
                            }
                            if (isset($brd_styles[2])) {
                                $dom[$key]['border']['B']['cap'] = 'square';
                                $dom[$key]['border']['B']['join'] = 'miter';
                                $dom[$key]['border']['B']['dash'] = $this->getCSSBorderDashStyle($brd_styles[2]);
                                if ($dom[$key]['border']['B']['dash'] < 0) {
                                    $dom[$key]['border']['B'] = [];
                                }
                            }
                        }
                        $cellside = ['L' => 'left', 'R' => 'right', 'T' => 'top', 'B' => 'bottom'];
                        foreach ($cellside as $bsk => $bsv) {
                            if (isset($dom[$key]['style']['border-' . $bsv])) {
                                $borderstyle = $this->getCSSBorderStyle($dom[$key]['style']['border-' . $bsv]);
                                if (!empty($borderstyle)) {
                                    $dom[$key]['border'][$bsk] = $borderstyle;
                                }
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-color'])) {
                                $dom[$key]['border'][$bsk]['color'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['style']['border-' . $bsv . '-color'],
                                    $this->spot_colors);
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-width'])) {
                                $dom[$key]['border'][$bsk]['width'] = $this->getCSSBorderWidth($dom[$key]['style']['border-' . $bsv . '-width']);
                            }
                            if (isset($dom[$key]['style']['border-' . $bsv . '-style'])) {
                                $dom[$key]['border'][$bsk]['dash'] = $this->getCSSBorderDashStyle($dom[$key]['style']['border-' . $bsv . '-style']);
                                if ($dom[$key]['border'][$bsk]['dash'] < 0) {
                                    $dom[$key]['border'][$bsk] = [];
                                }
                            }
                        }
                        // check for CSS padding properties
                        if (isset($dom[$key]['style']['padding'])) {
                            $dom[$key]['padding'] = $this->getCSSPadding($dom[$key]['style']['padding']);
                        } else {
                            $dom[$key]['padding'] = $this->cell_padding;
                        }
                        foreach ($cellside as $psk => $psv) {
                            if (isset($dom[$key]['style']['padding-' . $psv])) {
                                $dom[$key]['padding'][$psk] = $this->getHTMLUnitToUnits($dom[$key]['style']['padding-' . $psv],
                                    0, 'px', false);
                            }
                        }
                        // check for CSS margin properties
                        if (isset($dom[$key]['style']['margin'])) {
                            $dom[$key]['margin'] = $this->getCSSMargin($dom[$key]['style']['margin']);
                        } else {
                            $dom[$key]['margin'] = $this->cell_margin;
                        }
                        foreach ($cellside as $psk => $psv) {
                            if (isset($dom[$key]['style']['margin-' . $psv])) {
                                $dom[$key]['margin'][$psk] = $this->getHTMLUnitToUnits(str_replace('auto', '0',
                                    $dom[$key]['style']['margin-' . $psv]), 0, 'px', false);
                            }
                        }
                        // check for CSS border-spacing properties
                        if (isset($dom[$key]['style']['border-spacing'])) {
                            $dom[$key]['border-spacing'] = $this->getCSSBorderMargin($dom[$key]['style']['border-spacing']);
                        }
                        // page-break-inside
                        if (isset($dom[$key]['style']['page-break-inside']) AND ($dom[$key]['style']['page-break-inside'] == 'avoid')) {
                            $dom[$key]['attribute']['nobr'] = 'true';
                        }
                        // page-break-before
                        if (isset($dom[$key]['style']['page-break-before'])) {
                            if ($dom[$key]['style']['page-break-before'] == 'always') {
                                $dom[$key]['attribute']['pagebreak'] = 'true';
                            } else {
                                if ($dom[$key]['style']['page-break-before'] == 'left') {
                                    $dom[$key]['attribute']['pagebreak'] = 'left';
                                } else {
                                    if ($dom[$key]['style']['page-break-before'] == 'right') {
                                        $dom[$key]['attribute']['pagebreak'] = 'right';
                                    }
                                }
                            }
                        }
                        // page-break-after
                        if (isset($dom[$key]['style']['page-break-after'])) {
                            if ($dom[$key]['style']['page-break-after'] == 'always') {
                                $dom[$key]['attribute']['pagebreakafter'] = 'true';
                            } else {
                                if ($dom[$key]['style']['page-break-after'] == 'left') {
                                    $dom[$key]['attribute']['pagebreakafter'] = 'left';
                                } else {
                                    if ($dom[$key]['style']['page-break-after'] == 'right') {
                                        $dom[$key]['attribute']['pagebreakafter'] = 'right';
                                    }
                                }
                            }
                        }
                    }
                    if (isset($dom[$key]['attribute']['display'])) {
                        $dom[$key]['hide'] = (trim(strtolower($dom[$key]['attribute']['display'])) == 'none');
                    }
                    if (isset($dom[$key]['attribute']['border']) AND ($dom[$key]['attribute']['border'] != 0)) {
                        $borderstyle = $this->getCSSBorderStyle($dom[$key]['attribute']['border'] . ' solid black');
                        if (!empty($borderstyle)) {
                            $dom[$key]['border']['LTRB'] = $borderstyle;
                        }
                    }
                    // check for font tag
                    if ($dom[$key]['value'] == 'font') {
                        // font family
                        if (isset($dom[$key]['attribute']['face'])) {
                            $dom[$key]['fontname'] = $this->getFontFamilyName($dom[$key]['attribute']['face']);
                        }
                        // font size
                        if (isset($dom[$key]['attribute']['size'])) {
                            if ($key > 0) {
                                if ($dom[$key]['attribute']['size'][0] == '+') {
                                    $dom[$key]['fontsize'] = $dom[($dom[$key]['parent'])]['fontsize'] + intval(substr($dom[$key]['attribute']['size'],
                                            1));
                                } else {
                                    if ($dom[$key]['attribute']['size'][0] == '-') {
                                        $dom[$key]['fontsize'] = $dom[($dom[$key]['parent'])]['fontsize'] - intval(substr($dom[$key]['attribute']['size'],
                                                1));
                                    } else {
                                        $dom[$key]['fontsize'] = intval($dom[$key]['attribute']['size']);
                                    }
                                }
                            } else {
                                $dom[$key]['fontsize'] = intval($dom[$key]['attribute']['size']);
                            }
                        }
                    }
                    // force natural alignment for lists
                    if ((($dom[$key]['value'] == 'ul') OR ($dom[$key]['value'] == 'ol') OR ($dom[$key]['value'] == 'dl'))
                        AND (!isset($dom[$key]['align']) OR TCPDF_STATIC::empty_string($dom[$key]['align']) OR ($dom[$key]['align'] != 'J'))) {
                        if ($this->rtl) {
                            $dom[$key]['align'] = 'R';
                        } else {
                            $dom[$key]['align'] = 'L';
                        }
                    }
                    if (($dom[$key]['value'] == 'small') OR ($dom[$key]['value'] == 'sup') OR ($dom[$key]['value'] == 'sub')) {
                        if (!isset($dom[$key]['attribute']['size']) AND !isset($dom[$key]['style']['font-size'])) {
                            $dom[$key]['fontsize'] = $dom[$key]['fontsize'] * K_SMALL_RATIO;
                        }
                    }
                    if (($dom[$key]['value'] == 'strong') OR ($dom[$key]['value'] == 'b')) {
                        $dom[$key]['fontstyle'] .= 'B';
                    }
                    if (($dom[$key]['value'] == 'em') OR ($dom[$key]['value'] == 'i')) {
                        $dom[$key]['fontstyle'] .= 'I';
                    }
                    if ($dom[$key]['value'] == 'u') {
                        $dom[$key]['fontstyle'] .= 'U';
                    }
                    if (($dom[$key]['value'] == 'del') OR ($dom[$key]['value'] == 's') OR ($dom[$key]['value'] == 'strike')) {
                        $dom[$key]['fontstyle'] .= 'D';
                    }
                    if (!isset($dom[$key]['style']['text-decoration']) AND ($dom[$key]['value'] == 'a')) {
                        $dom[$key]['fontstyle'] = $this->htmlLinkFontStyle;
                    }
                    if (($dom[$key]['value'] == 'pre') OR ($dom[$key]['value'] == 'tt')) {
                        $dom[$key]['fontname'] = $this->default_monospaced_font;
                    }
                    if (!empty($dom[$key]['value']) AND ($dom[$key]['value'][0] == 'h') AND (intval($dom[$key]['value']{1}) > 0) AND (intval($dom[$key]['value']{1}) < 7)) {
                        // headings h1, h2, h3, h4, h5, h6
                        if (!isset($dom[$key]['attribute']['size']) AND !isset($dom[$key]['style']['font-size'])) {
                            $headsize = (4 - intval($dom[$key]['value']{1})) * 2;
                            $dom[$key]['fontsize'] = $dom[0]['fontsize'] + $headsize;
                        }
                        if (!isset($dom[$key]['style']['font-weight'])) {
                            $dom[$key]['fontstyle'] .= 'B';
                        }
                    }
                    if (($dom[$key]['value'] == 'table')) {
                        $dom[$key]['rows'] = 0; // number of rows
                        $dom[$key]['trids'] = []; // IDs of TR elements
                        $dom[$key]['thead'] = ''; // table header rows
                    }
                    if (($dom[$key]['value'] == 'tr')) {
                        $dom[$key]['cols'] = 0;
                        if ($thead) {
                            $dom[$key]['thead'] = true;
                            // rows on thead block are printed as a separate table
                        } else {
                            $dom[$key]['thead'] = false;
                            // store the number of rows on table element
                            ++$dom[($dom[$key]['parent'])]['rows'];
                            // store the TR elements IDs on table element
                            array_push($dom[($dom[$key]['parent'])]['trids'], $key);
                        }
                    }
                    if (($dom[$key]['value'] == 'th') OR ($dom[$key]['value'] == 'td')) {
                        if (isset($dom[$key]['attribute']['colspan'])) {
                            $colspan = intval($dom[$key]['attribute']['colspan']);
                        } else {
                            $colspan = 1;
                        }
                        $dom[$key]['attribute']['colspan'] = $colspan;
                        $dom[($dom[$key]['parent'])]['cols'] += $colspan;
                    }
                    // text direction
                    if (isset($dom[$key]['attribute']['dir'])) {
                        $dom[$key]['dir'] = $dom[$key]['attribute']['dir'];
                    }
                    // set foreground color attribute
                    if (isset($dom[$key]['attribute']['color']) AND (!TCPDF_STATIC::empty_string($dom[$key]['attribute']['color']))) {
                        $dom[$key]['fgcolor'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['attribute']['color'],
                            $this->spot_colors);
                    } else {
                        if (!isset($dom[$key]['style']['color']) AND ($dom[$key]['value'] == 'a')) {
                            $dom[$key]['fgcolor'] = $this->htmlLinkColorArray;
                        }
                    }
                    // set background color attribute
                    if (isset($dom[$key]['attribute']['bgcolor']) AND (!TCPDF_STATIC::empty_string($dom[$key]['attribute']['bgcolor']))) {
                        $dom[$key]['bgcolor'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['attribute']['bgcolor'],
                            $this->spot_colors);
                    }
                    // set stroke color attribute
                    if (isset($dom[$key]['attribute']['strokecolor']) AND (!TCPDF_STATIC::empty_string($dom[$key]['attribute']['strokecolor']))) {
                        $dom[$key]['strokecolor'] = TCPDF_COLORS::convertHTMLColorToDec($dom[$key]['attribute']['strokecolor'],
                            $this->spot_colors);
                    }
                    // check for width attribute
                    if (isset($dom[$key]['attribute']['width'])) {
                        $dom[$key]['width'] = $dom[$key]['attribute']['width'];
                    }
                    // check for height attribute
                    if (isset($dom[$key]['attribute']['height'])) {
                        $dom[$key]['height'] = $dom[$key]['attribute']['height'];
                    }
                    // check for text alignment
                    if (isset($dom[$key]['attribute']['align']) AND (!TCPDF_STATIC::empty_string($dom[$key]['attribute']['align'])) AND ($dom[$key]['value'] !== 'img')) {
                        $dom[$key]['align'] = strtoupper($dom[$key]['attribute']['align'][0]);
                    }
                    // check for text rendering mode (the following attributes do not exist in HTML)
                    if (isset($dom[$key]['attribute']['stroke'])) {
                        // font stroke width
                        $dom[$key]['stroke'] = $this->getHTMLUnitToUnits($dom[$key]['attribute']['stroke'],
                            $dom[$key]['fontsize'], 'pt', true);
                    }
                    if (isset($dom[$key]['attribute']['fill'])) {
                        // font fill
                        if ($dom[$key]['attribute']['fill'] == 'true') {
                            $dom[$key]['fill'] = true;
                        } else {
                            $dom[$key]['fill'] = false;
                        }
                    }
                    if (isset($dom[$key]['attribute']['clip'])) {
                        // clipping mode
                        if ($dom[$key]['attribute']['clip'] == 'true') {
                            $dom[$key]['clip'] = true;
                        } else {
                            $dom[$key]['clip'] = false;
                        }
                    }
                } // end opening tag
            } else {
                // text
                $dom[$key]['tag'] = false;
                $dom[$key]['block'] = false;
                $dom[$key]['parent'] = end($level);
                $dom[$key]['dir'] = $dom[$dom[$key]['parent']]['dir'];
                if (!empty($dom[$dom[$key]['parent']]['text-transform'])) {
                    // text-transform for unicode requires mb_convert_case (Multibyte String Functions)
                    if (function_exists('mb_convert_case')) {
                        $ttm = [
                            'capitalize' => MB_CASE_TITLE,
                            'uppercase' => MB_CASE_UPPER,
                            'lowercase' => MB_CASE_LOWER,
                        ];
                        if (isset($ttm[$dom[$dom[$key]['parent']]['text-transform']])) {
                            $element = mb_convert_case($element, $ttm[$dom[$dom[$key]['parent']]['text-transform']],
                                $this->encoding);
                        }
                    } else {
                        if (!$this->isunicode) {
                            switch ($dom[$dom[$key]['parent']]['text-transform']) {
                                case 'capitalize':
                                    {
                                        $element = ucwords(strtolower($element));
                                        break;
                                    }
                                case 'uppercase':
                                    {
                                        $element = strtoupper($element);
                                        break;
                                    }
                                case 'lowercase':
                                    {
                                        $element = strtolower($element);
                                        break;
                                    }
                            }
                        }
                    }
                }
                $dom[$key]['value'] = stripslashes($this->unhtmlentities($element));
            }
            ++$elkey;
            ++$key;
        }

        return $dom;
    }

}