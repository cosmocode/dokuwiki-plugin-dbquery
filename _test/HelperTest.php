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
}
