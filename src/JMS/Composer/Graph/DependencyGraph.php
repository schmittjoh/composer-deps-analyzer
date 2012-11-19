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

namespace JMS\Composer\Graph;

use JMS\Composer\Graph\PackageNode;

/**
 * Graph of dependencies.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DependencyGraph
{
    /**
     * @var PackageNode[]
     */
    private $packages = array();
    /**
     * @var PackageNode
     */
    private $rootPackage;

    public function __construct(PackageNode $rootPackage = null)
    {
        if (null !== $rootPackage) {
            $this->packages[$rootPackage->getName()] = $rootPackage;
        }

        $this->rootPackage = $rootPackage ?: $this->getOrCreate('__root');
    }

    public function getRootPackage()
    {
        return $this->rootPackage;
    }

    public function isRootPackage(PackageNode $node)
    {
        return $node === $this->rootPackage;
    }

    public function getPackages()
    {
        return $this->packages;
    }

    public function getPackage($name)
    {
        return isset($this->packages[$name]) ? $this->packages[$name] : null;
    }

    public function hasPackage($name)
    {
        return isset($this->packages[$name]);
    }

    public function createPackage($name, array $data = array())
    {
        if (isset($this->packages[$name])) {
            throw new \InvalidArgumentException(sprintf('The package "%s" already exists.', $name));
        }

        return $this->packages[$name] = new PackageNode($name, $data);
    }

    public function connect($packageA, $packageB, $versionConstraint)
    {
        $nodeA = $this->getOrCreate($packageA);
        $nodeB = $this->getOrCreate($packageB);

        // Do not add duplicate connections.
        foreach ($nodeA->getOutEdges() as $edge) {
            if ($edge->getDestPackage() === $nodeB) {
                return;
            }
        }

        $edge = new DependencyEdge($nodeA, $nodeB, $versionConstraint);
        $nodeA->addOutEdge($edge);
        $nodeB->addInEdge($edge);
    }

    /**
     * Searches the graph for an aggregate package that contains the given package.
     *
     * @param string $packageName the name of the contained package
     *
     * @return PackageNode|null the aggregate package
     */
    public function getAggregatePackageContaining($packageName)
    {
        foreach ($this->packages as $packageNode) {
            if ($packageNode->replaces($packageName)) {
                return $packageNode;
            }
        }

        return null;
    }

    private function getOrCreate($package)
    {
        if (isset($this->packages[$package])) {
            return $this->packages[$package];
        }

        return $this->packages[$package] = new PackageNode($package);
    }
}