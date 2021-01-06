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
            $codes = $hlp->loadCodeBlocksFromPage($data['name']);
            $result = $hlp->executeQuery($codes['_']);
        } catch (\Exception $e) {
            msg(hsc($e->getMessage()), -1);
            return true;
        }

        if (count($result) === 1 && isset($result[0]['status']) && isset($codes[$result[0]['status']])) {
            $this->renderStatus($result, $codes[$result[0]['status']], $renderer);
        } else {
            $this->renderResultTable($result, $renderer);
        }

        return true;
    }

    /**
     * Render given result via the given status HTML
     *
     * @param string[][] $result
     * @param string $html
     * @param Doku_Renderer $R
     */
    public function renderStatus($result, $html, Doku_Renderer $R)
    {
        $value = isset($result[0]['result']) ? $result[0]['result'] : '';
        $html = str_replace(':result', hsc($value), $html);
        $R->doc .= $html;
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

