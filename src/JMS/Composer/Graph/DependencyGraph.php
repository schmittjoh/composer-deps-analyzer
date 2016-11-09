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

    /**
     * @param PackageNode|null $rootPackage
     */
    public function __construct(PackageNode $rootPackage = null)
    {
        if (null !== $rootPackage) {
            $this->packages[strtolower($rootPackage->getName())] = $rootPackage;
        }

        $this->rootPackage = $rootPackage ?: $this->getOrCreate('__root');
    }

    /**
     * @return PackageNode
     */
    public function getRootPackage()
    {
        return $this->rootPackage;
    }

    /**
     * @param PackageNode $node
     *
     * @return bool
     */
    public function isRootPackage(PackageNode $node)
    {
        return $node === $this->rootPackage;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isRootPackageName($name)
    {
        return strtolower($this->rootPackage->getName()) === strtolower($name);
    }

    /**
     * @return PackageNode[]
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param string $name
     *
     * @return PackageNode|null
     */
    public function getPackage($name)
    {
        return isset($this->packages[$name = strtolower($name)]) ? $this->packages[$name] : null;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPackage($name)
    {
        return isset($this->packages[strtolower($name)]);
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @throws \InvalidArgumentException
     * @return PackageNode
     */
    public function createPackage($name, array $data = array())
    {
        if (isset($this->packages[strtolower($name)])) {
            throw new \InvalidArgumentException(sprintf('The package "%s" already exists.', $name));
        }

        return $this->packages[strtolower($name)] = new PackageNode($name, $data);
    }

    /**
     * @param string $packageA
     * @param string $packageB
     * @param string $versionConstraint
     */
    public function connect($packageA, $packageB, $versionConstraint)
    {
        $nodeA = $this->getOrCreate($packageA);
        $nodeB = $this->getOrCreate($packageB);

        // Do not connect the same package
        if ($packageA === $packageB){
            return;
        }

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

    /**
     * @param string $package
     *
     * @return PackageNode
     */
    private function getOrCreate($package)
    {
        if (isset($this->packages[strtolower($package)])) {
            return $this->packages[strtolower($package)];
        }

        return $this->packages[strtolower($package)] = new PackageNode($package);
    }
}