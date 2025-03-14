<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * DokuWiki Plugin dbquery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class syntax_plugin_dbquery_macro extends SyntaxPlugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'normal';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 155;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~DBQUERY:.*?~~', $mode, 'plugin_dbquery_macro');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $macro = strtolower(trim(substr($match, 10, -2)));
        return [$macro];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        [$name, $value] = sexplode('=', $data[0], 2);
        $name = trim($name);
        $value = trim($value);
        if (!$value) $value = true;

        $renderer->info['dbquery'][$name] = $value;
        return true;
    }
}
