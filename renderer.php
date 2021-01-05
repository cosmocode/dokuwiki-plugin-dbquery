<?php

/**
 * DokuWiki Plugin dbquery (Renderer Component)
 *
 * Extracts code blocks from pages
 *
 * @todo this needs to be extended to get all the HTML blocks from sub sections
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class renderer_plugin_dbquery extends \Doku_Renderer
{
    /** @var bool remember if the first code block has been found already */
    protected $codeFound = false;

    /** @inheritDoc */
    public function getFormat()
    {
        return 'dbquery';
    }

    /** @inheritDoc */
    public function code($text, $lang = null, $file = null)
    {
        if ($this->codeFound) return;
        $this->codeFound = true;
        $this->doc = $text;
    }

}
