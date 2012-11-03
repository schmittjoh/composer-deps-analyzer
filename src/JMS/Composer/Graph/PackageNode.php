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
    private $repositoryId;
    private $name;
    private $data;
    private $version;
    private $sourceReference;
    private $inEdges = array();
    private $outEdges = array();
    private $attributes;

    public function __construct($name, array $data = array(), array $attributes = array())
    {
        $this->name = $name;
        $this->data = $data;
        $this->attributes = $attributes;
    }

    public function setRepositoryId($id)
    {
        $this->repositoryId = $id;
    }

    public function isPhpExtension()
    {
        return 0 === strpos($this->getQualifiedName(), 'ext-');
    }

    public function getQualifiedName()
    {
        if ( ! $this->hasAttribute('dir')) {
            return $this->name;
        }

        $repositoryId = $this->repositoryId ?: 'packagist';

        return $repositoryId.'__'.$this->name;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function hasAttribute($key)
    {
        return isset($this->attributes[$key]);
    }

    public function getAttribute($key)
    {
        if ( ! isset($this->attributes[$key])) {
            throw new \InvalidArgumentException(sprintf('The attribute "%s" does not exist.', $key));
        }

        return $this->attributes[$key];
    }

    public function getData()
    {
        return $this->data;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getSourceReference()
    {
        return $this->sourceReference;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function setSourceReference($ref)
    {
        $this->sourceReference = $ref;
    }

    public function getInEdges()
    {
        return $this->inEdges;
    }

    public function getOutEdges()
    {
        return $this->outEdges;
    }

    public function addInEdge(DependencyEdge $edge)
    {
        $this->inEdges[] = $edge;
    }

    public function addOutEdge(DependencyEdge $edge)
    {
        $this->outEdges[] = $edge;
    }

    public function __toString()
    {
        return sprintf('PackageNode(qualifiedName = %s, version = %s, ref = %s, hasDir = %s)',
                $this->getQualifiedName(),
                $this->version,
                $this->sourceReference ?: 'null',
                $this->hasAttribute('dir') ? 'true' : 'false');
    }
}