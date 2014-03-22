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

class PackageNode
{
    /**
     * @var string
     */
    private $repositoryId;
    /**
     * @var string
     */
    private $name;
    /**
     * @var array
     */
    private $data;
    /**
     * @var string
     */
    private $version;
    /**
     * @var string
     */
    private $sourceReference;
    /**
     * @var DependencyEdge[]
     */
    private $inEdges = array();
    /**
     * @var DependencyEdge[]
     */
    private $outEdges = array();
    /**
     * @var array
     */
    private $attributes;

    /**
     * @param string $name
     * @param array  $data
     * @param array  $attributes
     */
    public function __construct($name, array $data = array(), array $attributes = array())
    {
        $this->name = $name;
        $this->data = $data;
        $this->attributes = $attributes;
    }

    /**
     * @param string $id
     */
    public function setRepositoryId($id)
    {
        $this->repositoryId = $id;
    }

    public function isPhpExtension()
    {
        return 0 === stripos($this->getQualifiedName(), 'ext-');
    }

    public function isPhpRuntime()
    {
        return strtolower($this->getQualifiedName()) === 'php';
    }

    /**
     * @return string
     */
    public function getQualifiedName()
    {
        if ( ! $this->hasAttribute('dir')) {
            return $this->name;
        }

        $repositoryId = $this->repositoryId ?: 'packagist';

        return $repositoryId.'__'.$this->name;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * @param $key
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getAttribute($key)
    {
        if ( ! isset($this->attributes[$key])) {
            throw new \InvalidArgumentException(sprintf('The attribute "%s" does not exist.', $key));
        }

        return $this->attributes[$key];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getSourceReference()
    {
        return $this->sourceReference;
    }

    /**
     * @param $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @param $ref
     */
    public function setSourceReference($ref)
    {
        $this->sourceReference = $ref;
    }

    /**
     * @return DependencyEdge[]
     */
    public function getInEdges()
    {
        return $this->inEdges;
    }

    /**
     * @return DependencyEdge[]
     */
    public function getOutEdges()
    {
        return $this->outEdges;
    }

    /**
     * @param DependencyEdge $edge
     */
    public function addInEdge(DependencyEdge $edge)
    {
        $this->inEdges[] = $edge;
    }

    /**
     * @param DependencyEdge $edge
     */
    public function addOutEdge(DependencyEdge $edge)
    {
        $this->outEdges[] = $edge;
    }

    /**
     * @param $package
     *
     * @return bool
     */
    public function replaces($package)
    {
        return $this->hasDataPackageKey('replace', $package);
    }

    /**
     * Checks if this package has the given $packageName in its $data[$dataKey] hash.
     *
     * @param string $dataKey
     * @param string $packageName
     *
     * @return bool
     */
    public function hasDataPackageKey($dataKey, $packageName)
    {
        if (isset($this->data[$dataKey]) && is_array($this->data[$dataKey])) {
            $packageName = strtolower($packageName);
            foreach (array_keys($this->data[$dataKey]) as $k) {
                if (strtolower($k) === $packageName) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('PackageNode(qualifiedName = %s, version = %s, ref = %s, hasDir = %s)',
                $this->getQualifiedName(),
                $this->version,
                $this->sourceReference ?: 'null',
                $this->hasAttribute('dir') ? 'true' : 'false');
    }
}