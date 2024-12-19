<?php
namespace dokuwiki\plugin\dbquery\test;

use DokuWikiTest;

/**
 * General tests for the dbquery plugin
 *
 * @group plugin_dbquery
 * @group plugins
 */
class HelperTest extends DokuWikiTest
{
    protected $pluginsEnabled = array('dbquery');
    /** @var \helper_plugin_dbquery $hlp */
    protected $hlp;

    /** @inheritDoc */
    public function setUp(): void
    {
        parent::setUp();
        $this->hlp = plugin_load('helper', 'dbquery');
    }

    public function testGatherVariables()
    {
        global $INFO;
        $INFO['id'] = 'foo:bar:baz';

        $expected = [
            ':user' => '',
            ':mail' => '',
            ':groups' => [],
            ':id' => ':foo:bar:baz',
            ':page' => 'baz',
            ':ns' => ':foo:bar',
        ];
        $actual = $this->hlp->gatherVariables();
        $this->assertEquals($expected, $actual);
    }

    public function testGatherVariablesUser()
    {
        global $INFO;
        /** @var \auth_plugin_authplain $auth */
        global $auth;

        $INFO['id'] = 'foo:bar:baz';
        $auth->createUser('foo', 'bar', 'My Test User', 'foo@example.com', ['one', 'two', 'three']);
        auth_login('foo', 'bar');

        $expected = [
            ':user' => 'foo',
            ':mail' => 'foo@example.com',
            ':groups' => ['one', 'two', 'three'],
            ':id' => ':foo:bar:baz',
            ':page' => 'baz',
            ':ns' => ':foo:bar',
        ];
        $actual = $this->hlp->gatherVariables();
        $this->assertEquals($expected, $actual);
    }

    public function testPrepareStatement()
    {
        $pdo = $this->hlp->getPDO('sqlite:', '', '');

        $parameters = [
            ':user' => 'foo',
            ':mail' => 'foo@example.com',
            ':groups' => ['one', 'two', 'three'],
            ':id' => ':foo:bar:baz',
            ':page' => 'baz',
            ':ns' => ':foo:bar',
        ];
        $sql = 'SELECT :user, :mail, :id, :page, :ns WHERE \'foo\' in (:groups)';
        $sth = $this->hlp->prepareStatement($pdo, $sql, $parameters);

        $actual = $sth->queryString;
        $expected = 'SELECT :user, :mail, :id, :page, :ns WHERE \'foo\' in (:group0,:group1,:group2)';
        $this->assertEquals($expected, $actual);
    }

    public function testGetDsnAliases()
    {
        $conf = "nouser mysql:host=localhost;port=3306;dbname=testdb1\n\n".
                "nopass mysql:host=localhost;port=3306;dbname=testdb2 user\n".
                "both mysql:host=localhost;port=3306;dbname=testdb3 user pass\n";

        $expect = [
            '_' => ['dsn' => 'mysql:host=localhost;port=3306;dbname=testdb1', 'user' => 'dfu', 'pass' => 'dfp'],
            'nouser' => ['dsn' => 'mysql:host=localhost;port=3306;dbname=testdb1', 'user' => 'dfu', 'pass' => 'dfp'],
            'nopass' => ['dsn' => 'mysql:host=localhost;port=3306;dbname=testdb2', 'user' => 'user', 'pass' => 'dfp'],
            'both' => ['dsn' => 'mysql:host=localhost;port=3306;dbname=testdb3', 'user' => 'user', 'pass' => 'pass'],
        ];

        $actual = $this->callInaccessibleMethod($this->hlp, 'getDsnAliases', [$conf, 'dfu', 'dfp']);
        $this->assertEquals($expect, $actual);
    }

    public function testGetDsnAliasesLegacy()
    {
        $conf = 'mysql:host=localhost;port=3306;dbname=testdb1';

        $expect = [
            '_' => ['dsn' => 'mysql:host=localhost;port=3306;dbname=testdb1', 'user' => 'dfu', 'pass' => 'dfp'],
        ];

        $actual = $this->callInaccessibleMethod($this->hlp, 'getDsnAliases', [$conf, 'dfu', 'dfp']);
        $this->assertEquals($expect, $actual);
    }
}
