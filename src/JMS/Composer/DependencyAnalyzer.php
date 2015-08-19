<?php

/*
 * Copyright 2012 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Composer;

use JMS\Composer\Exception\MissingLockFileException;
use JMS\Composer\Graph\PackageNode;
use JMS\Composer\Graph\DependencyGraph;

/**
 * Analyzes dependencies of a project, and returns them as a graph.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DependencyAnalyzer
{
    /**
     * @param string $dir
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return \JMS\Composer\Graph\DependencyGraph
     */
    public function analyze($dir)
    {
        if ( ! is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist.', $dir));
        }

        if (stream_is_local($dir)) {
            $dir = realpath($dir);
        }

        if ( ! is_file($dir.'/composer.json')) {
            $graph = new DependencyGraph();
            $graph->getRootPackage()->setAttribute('dir', $dir);

            return $graph;
        }

        return $this->analyzeComposerData(
            file_get_contents($dir.'/composer.json'),
            is_file($dir.'/composer.lock') ? file_get_contents($dir.'/composer.lock') : null,
            $dir
        );
    }

    public function analyzeComposerData($composerJsonData, $composerLockData = null, $dir = null)
    {
        $rootPackageData = $this->parseJson($composerJsonData);
        if ( ! isset($rootPackageData['name'])) {
            $rootPackageData['name'] = '__root';
        }

        // If there is no composer.lock file, then either the project has no
        // dependencies, or the dependencies were not installed.
        if (empty($composerLockData)) {
            if ($this->hasDependencies($rootPackageData)) {
                throw new MissingLockFileException();
            }

            $graph = new DependencyGraph(new PackageNode($rootPackageData['name'], $rootPackageData));
            $graph->getRootPackage()->setAttribute('dir', $dir);

            // Connect built-in dependencies for example on the PHP version, or
            // on PHP extensions. For these, composer does not create a composer.lock.
            if (isset($rootPackageData['require'])) {
                foreach ($rootPackageData['require'] as $name => $versionConstraint) {
                    $this->connect($graph, $rootPackageData['name'], $name, $versionConstraint);
                }
            }

            if (isset($rootPackageData['require-dev'])) {
                foreach ($rootPackageData['require-dev'] as $name => $versionConstraint) {
                    $this->connect($graph, $rootPackageData['name'], $name, $versionConstraint);
                }
            }

            return $graph;
        }

        $graph = new DependencyGraph(new PackageNode($rootPackageData['name'], $rootPackageData));
        $graph->getRootPackage()->setAttribute('dir', $dir);

        $vendorDir = $dir.'/'.(isset($rootPackageData['config']['vendor-dir']) ? $rootPackageData['config']['vendor-dir'] : 'vendor');
        $lockData = $this->parseJson($composerLockData);

        // Add regular packages.
        if (isset($lockData['packages'])) {
            $this->addPackages($graph, $lockData['packages'], $vendorDir);
        }

        // Add development packages.
        if (isset($lockData['packages-dev'])) {
            $this->addPackages($graph, $lockData['packages-dev'], $vendorDir);
        }

        // Connect dependent packages.
        foreach ($graph->getPackages() as $packageNode) {
            $packageData = $packageNode->getData();

            if (isset($packageData['require'])) {
                foreach ($packageData['require'] as $name => $version) {
                    $this->connect($graph, $packageData['name'], $name, $version);
                }
            }

            if (isset($packageData['require-dev'])) {
                foreach ($packageData['require-dev'] as $name => $version) {
                    $this->connect($graph, $packageData['name'], $name, $version);
                }
            }
        }

        return $graph;
    }

    private function addPackages(DependencyGraph $graph, array $packages, $vendorDir)
    {
        foreach ($packages as $packageData) {
            if ($graph->isRootPackageName($packageData['name']) || $graph->hasPackage($packageData['name'])) {
                continue;
            }

            $package = $graph->createPackage($packageData['name'], $packageData);
            $package->setAttribute('dir', $vendorDir.'/'.$packageData['name']);
            $this->processLockedData($graph, $packageData);
        }
    }

    private function parseJson($data)
    {
        $parsedData = json_decode($data, true);
        if ($parsedData === false) {
            throw new \RuntimeException('Could not parse JSON data.');
        }

        return $parsedData;
    }

    private function connect(DependencyGraph $graph, $sourceName, $destName, $version)
    {
        // If the dest package is available, just connect it.
        if ($graph->hasPackage($destName)) {
            $graph->connect($sourceName, $destName, $version);

            return;
        }

        // If the dest package is not available, let's check to see if there is
        // some aggregate package that replaces our dest package, and connect to
        // this package.
        if (null !== $aggregatePackage = $graph->getAggregatePackageContaining($destName)) {
            $graph->connect($sourceName, $aggregatePackage->getName(), $version);

            return;
        }

        // If we reach this, we have stumbled upon a package that is only available
        // if the source package is installed with dev dependencies. We still add
        // the connection, but we will not have any data about the dest package.
        $graph->connect($sourceName, $destName, $version);
    }

    private function processLockedData(DependencyGraph $graph, array $lockedPackageData)
    {
        $packageName = null;
        if (isset($lockedPackageData['name'])) {
            $packageName = $lockedPackageData['name'];
        } else if (isset($lockedPackageData['package'])) {
            $packageName = $lockedPackageData['package'];
        }

        if (null === $packageName) {
            return;
        }

        $package = $graph->getPackage($packageName);
        if (null === $package) {
            return;
        }

        $package->setVersion($lockedPackageData['version']);

        if (isset($lockedPackageData['source']['reference'])
                && $lockedPackageData['version'] !== $lockedPackageData['source']['reference']) {
            $package->setSourceReference($lockedPackageData['source']['reference']);
        }
    }

    /**
     * @param array $config
     *
     * @return bool
     */
    private function hasDependencies(array $config)
    {
        if (isset($config['require']) && $this->hasUserlandDependency($config['require'])) {
            return true;
        }

        if (isset($config['require-dev']) && $this->hasUserlandDependency($config['require-dev'])) {
            return true;
        }

        return false;
    }

    /**
     * @param array $requires
     *
     * @return bool
     */
    private function hasUserlandDependency(array $requires)
    {
        if (empty($requires)) {
            return false;
        }

        foreach ($requires as $name => $versionConstraint) {
            if ('php' === strtolower($name)) {
                continue;
            }

            if (0 === stripos($name, 'ext-')) {
                continue;
            }

            return true;
        }

        return false;
    }
}
