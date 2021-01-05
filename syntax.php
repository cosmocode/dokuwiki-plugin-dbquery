<?php

/**
 * DokuWiki Plugin dbquery (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class syntax_plugin_dbquery extends DokuWiki_Syntax_Plugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'substition';
    }

    /** @inheritDoc */
    public function getPType()
    {
        return 'block';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 135;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('QUERY:\w+', $mode, 'plugin_dbquery');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return ['name' => substr($match, 6)];
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') {
            return false;
        }

        /** @var helper_plugin_dbquery $hlp */
        $hlp = plugin_load('helper', 'dbquery');
        try {
            $query = $hlp->loadQueryFromPage($data['name']);
            $result = $hlp->executeQuery($query);
        } catch (\Exception $e) {
            msg(hsc($e->getMessage()), -1);
            return true;
        }

        $this->renderResultTable($result, $renderer);

        return true;
    }

    /**
     * Render the given result as a table
     *
     * @param string[][] $result
     * @param Doku_Renderer $R
     */
    public function renderResultTable($result, Doku_Renderer $R)
    {
        global $lang;

        if (!count($result)) {
            $R->cdata($lang['nothingfound']);
            return;
        }

        $R->table_open();
        $R->tablerow_open();
        foreach (array_keys($result[0]) as $header) {
            $R->tableheader_open();
            $R->cdata($header);
            $R->tableheader_close();
        }
        $R->tablerow_close();

        foreach ($result as $row) {
            $R->tablerow_open();
            foreach ($row as $cell) {
                $R->tablecell_open();
                $R->cdata($cell);
                $R->tablecell_close();
            }
            $R->tablerow_close();
        }
        $R->table_close();
    }
}

