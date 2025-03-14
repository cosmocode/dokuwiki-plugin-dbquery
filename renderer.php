<?php

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

/**
 * DokuWiki Plugin dbquery (Renderer Component)
 *
 * Extracts code blocks from pages and returns them as JSON
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class renderer_plugin_dbquery extends \Doku_Renderer
{
    protected $codeBlocks = [];
    protected $lastHeader = '';

    /** @inheritDoc */
    public function getFormat()
    {
        return 'dbquery';
    }

    /** @inheritDoc */
    public function header($text, $level, $pos)
    {
        $this->lastHeader = $text;
    }

    /** @inheritDoc */
    public function code($text, $lang = null, $file = null)
    {
        if (!isset($this->codeBlocks['_'])) {
            // first code block is always the SQL query
            $this->codeBlocks['_'] = trim($text);
        } else {
            // all other code blocks are treated as HTML named by their header
            $this->codeBlocks[$this->lastHeader] = trim($text);
        }
    }

    /** @inheritdoc */
    public function document_start()
    {
        parent::document_start();
        $this->info['dbquery']['transpose'] = false;
    }

    /** @inheritDoc */
    public function document_end()
    {
        $this->doc = json_encode([
            'codeblocks' => $this->codeBlocks,
            'macros' => $this->info['dbquery'],
        ]);
    }
}
