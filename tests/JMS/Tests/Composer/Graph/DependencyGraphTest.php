<?php

namespace JMS\Tests\Composer\Graph;

use JMS\Composer\Graph\DependencyGraph;

class DependencyGraphTest extends \PHPUnit_Framework_TestCase
{
    public function testImplicitCreationOfRootPackage()
    {
        $graph = new DependencyGraph();
        $this->assertCount(1, $graph->getPackages());
        $this->assertTrue($graph->isRootPackage($graph->getPackages()['__root']));
    }
}