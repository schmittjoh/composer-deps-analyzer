<?php

namespace JMS\Tests\Composer\Graph;

use JMS\Composer\Graph\PackageNode;

class PackageNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getIsPhpRuntimeTests
     */
    public function testIsPhpRuntime($name, $outcome)
    {
        $node = new PackageNode($name);
        $this->assertSame($outcome, $node->isPhpRuntime());
    }

    public function getIsPhpRuntimeTests()
    {
        return array(
            array('php', true),
            array('Php', true),
            array('PHP', true),
            array('php-foo', false),
            array('php/asdf', false),
            array('asdf', false),
            array('ext-asdf', false)
        );
    }

    /**
     * @dataProvider getIsPhpExtensionTests
     */
    public function testIsPhpExtension($name, $outcome)
    {
        $node = new PackageNode($name);
        $this->assertSame($outcome, $node->isPhpExtension());
    }

    public function getIsPhpExtensionTests()
    {
        return array(
            array('ext-foo', true),
            array('Ext-asdf', true),
            array('EXT-bar', true),
            array('ext/foo', false),
            array('php', false),
            array('asdf', false),
        );
    }
}