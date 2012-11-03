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
     */
    public function analyze($dir)
    {
        if ( ! is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" does not exist.', $dir));
        }
        $dir = realpath($dir);

        if ( ! is_file($dir.'/composer.json')) {
            $graph = new DependencyGraph();
            $graph->getRootPackage()->setAttribute('dir', $dir);

            return $graph;
        }

        $rootPackageData = json_decode(file_get_contents($dir.'/composer.json'), true);

        // If there are is no composer.lock file, then either the project has no
        // dependencies, or the dependencies were not installed.
        if ( ! is_file($dir.'/composer.lock')) {
            if ($this->hasDependencies($rootPackageData)) {
                throw new \RuntimeException(sprintf('You need to run "composer install --dev" in "%s" before analyzing dependencies.', $dir));
            }

            $graph = new DependencyGraph();
            $graph->getRootPackage()->setAttribute('dir', $dir);

            return $graph;
        }

        // The vendor directory is also only created when a package has dependencies,
        // but since we handle the no-dependency case already in the previous if, at
        // this point there really must be a directory.
        $vendorDir = $dir.'/'.(isset($rootPackageData['config']['vendor-dir']) ? $rootPackageData['config']['vendor-dir'] : 'vendor');
        if ( ! is_dir($vendorDir)) {
            throw new \RuntimeException(sprintf('The vendor directory "%s" could not be found.', $vendorDir));
        }

        if ( ! isset($rootPackageData['name'])) {
            $rootPackageData['name'] = '__root';
        }
        $graph = new DependencyGraph(new PackageNode($rootPackageData['name'], $rootPackageData));
        $graph->getRootPackage()->setAttribute('dir', $dir);

        // Add regular packages.
        if (is_file($installedFile = $vendorDir.'/composer/installed.json')) {
            foreach (json_decode(file_get_contents($installedFile), true) as $packageData) {
                $package = $graph->createPackage($packageData['name'], $packageData);
                $package->setAttribute('dir', $vendorDir.'/'.$packageData['name']);
                $this->processLockedData($graph, $packageData);
            }
        }

        // Add development packages.
        if (is_file($installedDevFile = $vendorDir.'/composer/installed_dev.json')) {
            foreach (json_decode(file_get_contents($installedDevFile), true) as $packageData) {
                $package = $graph->createPackage($packageData['name'], $packageData);
                $package->setAttribute('dir', $vendorDir.'/'.$packageData['name']);
                $this->processLockedData($graph, $packageData);
            }
        }

        // Connect dependent packages.
        foreach ($graph->getPackages() as $packageNode) {
            $packageData = $packageNode->getData();

            if (isset($packageData['require'])) {
                foreach ($packageData['require'] as $name => $version) {
                    $graph->connect($packageData['name'], $name, $version);
                }
            }

            if (isset($packageData['require-dev'])) {
                foreach ($packageData['require-dev'] as $name => $version) {
                    $graph->connect($packageData['name'], $name, $version);
                }
            }
        }

        // Populate graph with versions, and source references.
        $lockData = json_decode(file_get_contents($dir.'/composer.lock'), true);
        if (isset($lockData['packages'])) {
            foreach ($lockData['packages'] as $lockedPackageData) {
                $this->processLockedData($graph, $lockedPackageData);
            }
        }
        if (isset($lockData['packages-dev'])) {
            foreach ($lockData['packages-dev'] as $lockedPackageData) {
                $this->processLockedData($graph, $lockedPackageData);
            }
        }

        return $graph;
    }

    private function processLockedData(DependencyGraph $graph, array $lockedPackageData)
    {
        $package = $graph->getPackage($lockedPackageData['name']);
        $package->setVersion($lockedPackageData['version']);

        if (isset($lockedPackageData[$lockedPackageData['installation-source']]['reference'])
                && $lockedPackageData['version'] !== $lockedPackageData[$lockedPackageData['installation-source']]['reference']) {
            $package->setSourceReference($lockedPackageData[$lockedPackageData['installation-source']]['reference']);
        }
    }

    private function hasDependencies(array $config)
    {
        if (isset($config['require']) && ! empty($config['require'])) {
            return true;
        }

        if (isset($config['require-dev']) && ! empty($config['require-dev'])) {
            return true;
        }

        return false;
    }
}
