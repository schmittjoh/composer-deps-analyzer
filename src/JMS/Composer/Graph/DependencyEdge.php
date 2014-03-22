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

class DependencyEdge
{
    /**
     * @var PackageNode
     */
    private $sourcePackage;
    /**
     * @var PackageNode
     */
    private $destPackage;
    /**
     * @var string
     */
    private $versionConstraint;

    /**
     * @param PackageNode $sourcePackage
     * @param PackageNode $destPackage
     * @param string      $versionConstraint
     */
    public function __construct(PackageNode $sourcePackage, PackageNode $destPackage, $versionConstraint)
    {
        $this->sourcePackage = $sourcePackage;
        $this->destPackage = $destPackage;
        $this->versionConstraint = $versionConstraint;
    }

    /**
     * @return string
     */
    public function getVersionConstraint()
    {
        return $this->versionConstraint;
    }

    /**
     * @return PackageNode
     */
    public function getSourcePackage()
    {
        return $this->sourcePackage;
    }

    /**
     * @return PackageNode
     */
    public function getDestPackage()
    {
        return $this->destPackage;
    }

    /**
     * @return bool
     */
    public function isDevDependency()
    {
        return $this->sourcePackage->hasDataPackageKey('require-dev', $this->destPackage->getName());
    }
}