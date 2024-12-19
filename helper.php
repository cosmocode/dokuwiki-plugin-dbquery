<?php

/**
 * DokuWiki Plugin dbquery (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <dokuwiki@cosmocode.de>
 */
class helper_plugin_dbquery extends dokuwiki\Extension\Plugin
{
    /** @var PDO[] do not access directly, use getPDO instead */
    protected $pdo = [];

    /**
     * @param string $name Page name of the query
     * @return array
     * @throws \Exception
     */
    public function loadDataFromPage($name)
    {

        $name = cleanID($name);
        $id = $this->getConf('namespace') . ':' . $name;
        if (!page_exists($id)) throw new \Exception("No query named '$name' found");

        $doc = p_cached_output(wikiFN($id), 'dbquery');

        return json_decode($doc, true);
    }

    /**
     * Return the PDO object and cache it for the request
     *
     * Connections data can be null to use the info from the config
     *
     * @param string|null $dsn
     * @param string|null $user
     * @param string|null $pass
     * @return PDO
     */
    public function getPDO($dsn = null, $user = null, $pass = null)
    {
        $dsn = $dsn ?: $this->getConf('dsn');
        $user = $user ?: $this->getConf('user');
        $pass = $pass ?: conf_decodeString($this->getConf('pass'));
        $conid = md5($dsn . $user . $pass);

        if (isset($this->pdo[$conid])) return $this->pdo[$conid];

        $opts = [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // always fetch as array
            PDO::ATTR_EMULATE_PREPARES => true, // emulating prepares allows us to reuse param names
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // we want exceptions, not error codes
        ];

        $this->pdo[$conid] = new PDO($dsn, $user, $pass, $opts);
        return $this->pdo[$conid];
    }

    /**
     * Opens a database connection, executes the query and returns the result
     *
     * @param string $query
     * @return array
     * @throws \PDOException
     * @throws Exception
     */
    public function executeQuery($query, $dsnalias = null)
    {
        if (!preg_match('/^select /i', trim($query))) {
            throw new \Exception('For security reasons only SELECT statements are allowed in dbquery');
        }

        [$dsn, $user, $pass] = $this->getDSN($dsnalias);
        $pdo = $this->getPDO($dsn, $user, $pass);
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
     * @param array $parameters
     * @return PDOStatement
     */
    public function prepareStatement(\PDO $pdo, $sql, $parameters)
    {
        // prepare the groups
        $cnt = 0;
        $groupids = [];
        foreach ($parameters[':groups'] as $group) {
            $id = 'group' . $cnt++;
            $parameters[$id] = $group;
            $groupids[] = ":$id";
        }
        unset($parameters[':groups']);
        $sql = str_replace(':groups', join(',', $groupids), $sql);

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

        return [
            ':user' => $INPUT->server->str('REMOTE_USER'),
            ':mail' => $USERINFO['mail'] ?? '',
            ':groups' => $USERINFO['grps'] ?? [],
            ':id' => ':' . $INFO['id'],
            ':page' => noNS($INFO['id']),
            ':ns' => ':' . getNS($INFO['id']),
        ];
    }

    /**
     * Get the DSN, user and pass for a given alias
     *
     * @param string|null $alias null for default
     * @return [string, string, string] DSN, user, pass
     * @throws Exception
     */
    public function getDSN($alias = null)
    {
        static $aliases = null;
        if ($aliases === null) {
            $aliases = $this->getDsnAliases(
                $this->getConf('dsn'),
                $this->getConf('user'),
                $this->getConf('pass')
            );
        }

        if ($aliases === []) throw new \Exception('No DSN aliases defined');

        if ($alias === null || !isset($aliases[$alias])) {
            $alias = '_';
        }

        return [$aliases[$alias]['dsn'], $aliases[$alias]['user'], $aliases[$alias]['pass']];
    }

    /**
     * Load and parse the DSN configuration
     *
     * @param string $config
     * @param string $defaultuser
     * @param string $defaultpass
     * @return array
     */
    protected function getDsnAliases($config, $defaultuser, $defaultpass)
    {
        $aliases = [];
        $lines = explode("\n", $config);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;
            $parts = preg_split('/\s+/', $line, 4);

            if (count($parts) > 1) {
                $aliases[$parts[0]] = [
                    'dsn' => $parts[1],
                    'user' => $parts[2] ?? $defaultuser,
                    'pass' => $parts[3] ?? $defaultpass
                ];
            } else {
                $parts = ['', $parts[0]];
            }

            if (!isset($aliases['_'])) {
                $aliases['_'] = [
                    'dsn' => $parts[1],
                    'user' => $parts[2] ?? $defaultuser,
                    'pass' => $parts[3] ?? $defaultpass
                ];
            }
        }
        return $aliases;
    }
}
