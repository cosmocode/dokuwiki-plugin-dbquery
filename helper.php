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
     * @return array
     * @throws \Exception
     */
    public function loadCodeBlocksFromPage($name)
    {

        $name = cleanID($name);
        $id = $this->getConf('namespace') . ':' . $name;
        if (!page_exists($id)) throw new \Exception("No query named '$name' found");

        $doc = p_cached_output(wikiFN($id), 'dbquery');

        return json_decode($doc, true);
    }

    /**
     * Opens a database connection, executes the query and returns the result
     *
     * @param string $query
     * @return array
     * @throws \PDOException
     * @todo should we keep the DB connection around for subsequent queries?
     * @todo should we allow SELECT queries only for additional security?
     */
    public function executeQuery($query)
    {

        $opts = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // always fetch as array
            PDO::ATTR_EMULATE_PREPARES => true, // emulating prepares allows us to reuse param names
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // we want exceptions, not error codes
        ];
        $pdo = new PDO(
            $this->getConf('dsn'),
            $this->getConf('user'),
            conf_decodeString($this->getConf('pass')),
            $opts
        );

        $params = $this->gatherVariables();
        $sth = $this->prepareStatement($pdo, $query, $params);
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth->closeCursor();

        return $data;
    }

    /**
     * Generate a prepared statement with bound parameters
     *
     * @param PDO $pdo
     * @param string $sql
     * @param string[] $parameters
     * @return PDOStatement
     */
    public function prepareStatement(\PDO $pdo, $sql, $parameters)
    {
        $sth = $pdo->prepare($sql);

        foreach ($parameters as $key => $val) {
            if (is_array($val)) continue;
            if (is_object($val)) continue;
            if (strpos($sql, $key) === false) continue; // skip if parameter is missing

            if (is_int($val)) {
                $sth->bindValue($key, $val, PDO::PARAM_INT);
            } else {
                $sth->bindValue($key, $val);
            }
        }

        return $sth;
    }

    /**
     * Get the standard replacement variables
     *
     * @return array
     */
    public function gatherVariables()
    {
        global $USERINFO;
        global $INFO;
        global $INPUT;

        // add leading colon
        $id = ':' . $INFO['id'];
        return [
            ':user' => $INPUT->server->str('REMOTE_USER'),
            ':mail' => $USERINFO['mail'] ?: '',
            ':groups' => $USERINFO['grps'] ? join(',', $USERINFO['grps']) : '', //FIXME escaping correct???
            ':id' => $id,
            ':page' => noNS($id),
            ':ns' => getNS($id), //FIXME check that leading colon exists
        ];
    }
}
