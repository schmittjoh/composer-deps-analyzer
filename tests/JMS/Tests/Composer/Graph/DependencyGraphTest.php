<?php

namespace JMS\Tests\Composer\Graph;

use JMS\Composer\Graph\DependencyGraph;

class DependencyGraphTest extends \PHPUnit_Framework_TestCase
{
    public function testImplicitCreationOfRootPackage()
    {
        $graph = new DependencyGraph();
        $packages = $graph->getPackages();
        
        $this->assertCount(1, $packages);
        $this->assertTrue($graph->isRootPackage($packages['__root']));
    }
}