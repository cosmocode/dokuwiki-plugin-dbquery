<?php

namespace dokuwiki\plugin\dbquery\test;

use DokuWikiTest;

/**
 * Query execution tests for the dbquery plugin
 *
 * @group plugin_dbquery
 * @group plugins
 */
class QueryTest extends DokuWikiTest
{

    /**
     * @see testUrlParsing
     */
    public function provideLinkContent()
    {
        return [
            ['foo bar', '/^foo bar$/'],
            ['[[]]', '/^\[\[\]\]$/'],
            ['Nope [[wiki]]', '/^Nope \[\[wiki\]\]$/'],
            ['[[wiki]]', '/data-wiki-id="wiki">wiki</'],
            ['[[wiki|]]', '/data-wiki-id="wiki">wiki</'],
            ['[[wiki|Test]]', '/data-wiki-id="wiki">Test</'],
            ['[[http://foobar]]', '/href="http:\/\/foobar".*>.*foobar</'],
            ['[[http://foobar|Test]]', '/href="http:\/\/foobar".*>Test</'],
        ];
    }

    /**
     * @dataProvider provideLinkContent
     */
    public function testUrlParsing($content, $expect)
    {
        $plugin = new \syntax_plugin_dbquery_query();
        $R = new \Doku_Renderer_xhtml();

        $this->callInaccessibleMethod($plugin, 'cellFormat', [$content, $R]);

        $this->assertMatchesRegularExpression($expect, $R->doc);
    }
}
