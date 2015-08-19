<?php

namespace JMS\Tests\Composer;

use JMS\Composer\Graph\PackageNode;
use Symfony\Component\Filesystem\Filesystem;
use JMS\Composer\Graph\DependencyEdge;
use JMS\Composer\DependencyAnalyzer;
use JMS\Composer\Graph\DependencyGraph;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DependencyAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DependencyAnalyzer */
    private $analyzer;

    /** @var Filesystem */
    private $fs;

    public function testUpperCasePhp()
    {
        $graph = $this->analyzer->analyzeComposerData(<<<'COMPOSER'
{
    "require": {
        "PHP": ">= 5.2",
        "ExT-foo": "dev-master"
    }
}
COMPOSER
        );

        $this->assertCount(3, $graph->getPackages());
    }

    /**
     * @expectedException JMS\Composer\Exception\MissingLockFileException
     */
    public function testThrowsExceptionWhenLockFileIsMissing()
    {
        $this->analyzer->analyzeComposerData(<<<'COMPOSER'
{
   "name": "foo/bar",
   "require": {
       "asdf/foo": "1.*"
   }
}
COMPOSER
        );
    }

    public function testRedundantPackage()
    {
        $graph = $this->analyzer->analyzeComposerData(
            file_get_contents(__DIR__.'/Fixture/redundant_package.json'),
            file_get_contents(__DIR__.'/Fixture/redundant_package.lock')
        );

        $this->assertEquals(file_get_contents(__DIR__.'/Fixture/redundant_package_graph.txt'), $this->dumpGraph($graph));
    }

    /**
     * @group bug-duplicate
     */
    public function testDuplicatePackageBug()
    {
        $graph = $this->analyzer->analyzeComposerData(
            file_get_contents(__DIR__.'/Fixture/prophecy_composer.json'),
            file_get_contents(__DIR__.'/Fixture/prophecy_composer.lock')
        );

        $this->assertEquals(file_get_contents(__DIR__.'/Fixture/prophecy_graph.txt'), $this->dumpGraph($graph));
    }

    /**
     * @dataProvider getAnalyzeTests
     * @param string $configName
     * @throws \InvalidArgumentException
     */
    public function testAnalyze($configName)
    {
        if ( ! is_file($depsGraphPath = __DIR__.'/Fixture/'.$configName.'_graph.txt')) {
            throw new \InvalidArgumentException(sprintf('The dependency graph "%s" does not exist.', $depsGraphPath));
        }

        $graph = $this->analyze($configName);
        $this->assertEquals(file_get_contents($depsGraphPath), $this->dumpGraph($graph));
    }

    public function getAnalyzeTests()
    {
        $tests = array();

        $tests[] = array('no_deps');
        $tests[] = array('regular_deps');
        $tests[] = array('open_source_lib');
        $tests[] = array('dev_stability');
        $tests[] = array('aliased_public_lib');
        $tests[] = array('aggregate_package');
        $tests[] = array('unavailable_dev_package');
        $tests[] = array('dep_only_on_php_and_ext');
        $tests[] = array('mixed_case');

        return $tests;
    }

    public function testDirectoryIsSetForNoDepsPackage()
    {
        $graph = $this->analyze('no_deps');
        $this->assertTrue($graph->getRootPackage()->hasAttribute('dir'));
    }

    public function setUp()
    {
        $this->analyzer = new DependencyAnalyzer();
        $this->fs = new Filesystem();
    }

    private function analyze($configName)
    {
        if ( ! is_file($configPath = __DIR__.'/Fixture/'.$configName.'_composer.json')) {
            throw new \InvalidArgumentException(sprintf('The root config "%s" does not exist.', $configPath));
        }

        if ( is_file($lockPath = __DIR__.'/Fixture/'.$configName.'_composer.lock')) {
            return $this->analyzer->analyzeComposerData(file_get_contents($configPath), file_get_contents($lockPath));
        }

        $tmpDir = tempnam(sys_get_temp_dir(), 'dependencyAnalyzer');
        $this->fs->remove($tmpDir);
        $this->fs->mkdir($tmpDir);
        $this->fs->copy($configPath, $tmpDir.'/composer.json');

        $this->install($tmpDir);

        $graph = $this->analyzer->analyze($tmpDir);
        $this->fs->remove($tmpDir);

        return $graph;
    }

    private function install($dir)
    {
        $proc = new Process('php '.__DIR__.'/../../../../vendor/bin/composer install --dev', $dir);
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }
    }

    private function dumpGraph(DependencyGraph $graph)
    {
        $packages = $graph->getPackages();
        usort($packages, function(PackageNode $a, PackageNode $b) use ($graph) {
            if ($graph->isRootPackage($a)) {
                return -1;
            }

            if ($graph->isRootPackage($b)) {
                return 1;
            }

            return strcmp($a->getName(), $b->getName());
        });

        $txt = '';
        foreach ($packages as $package) {
            if ('' !== $txt) {
                $txt .= "\n\n";
            }

            $name = $package->getName();
            if ($graph->isRootPackage($package)) {
                $name .= ' (Root)';
            }

            $txt .= $name."\n".str_repeat('=', strlen($name))."\n";
            $txt .= "Version: ".($package->getVersion() ?: '<null>')."\n";

            if (null !== $ref = $package->getSourceReference()) {
                $txt .= "Source-Reference: ".$ref."\n";
            }

            if (count($outEdges = $package->getOutEdges()) > 0) {
                usort($outEdges, function(DependencyEdge $a, DependencyEdge $b) {
                    return strcmp($a->getDestPackage()->getName(), $b->getDestPackage()->getName());
                });

                foreach ($outEdges as $edge) {
                    assert($edge instanceof DependencyEdge);
                    $txt .= "-> ".$edge->getDestPackage()->getName()."\n";
                }
            }
        }

        return $txt;
    }
}