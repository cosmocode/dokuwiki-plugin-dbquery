<?php

/**
 * DokuWiki Plugin dbquery (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class helper_plugin_dbquery extends dokuwiki\Extension\Plugin
{

    /**
     * @param string $name Page name of the query
     * @throws \Exception
     */
    public function loadQueryFromPage($name)
    {

        $name = cleanID($name);
        $id = $this->getConf('namespace') . ':' . $name;
        if (!page_exists($id)) throw new \Exception("No query named '$name' found");

        $doc = p_cached_output(wikiFN($id), 'dbquery');
        // FIXME handle additional stuff later

        return trim($doc);
    }

    /**
     * Opens a database connection, executes the query and returns the result
     *
     * @param string $query
     * @param string[] $params
     * @return array
     * @throws \PDOException
     * @todo should we keep the DB connection around for subsequent queries?
     * @todo should we allow SELECT queries only for additional security?
     */
    public function executeQuery($query, $params)
    {
        $pdo = new PDO($this->getConf('dsn'), $this->getConf('user'), $this->getConf('pass'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sth = $pdo->prepare($query);
        $sth->execute($params);
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth->closeCursor();

        return $data;
    }
}
