Composer Dependency Analyzer [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/schmittjoh/composer-deps-analyzer/badges/quality-score.png?s=9bfccbd612e0d6d891ef14b16b262230fbf6cc08)](https://scrutinizer-ci.com/g/schmittjoh/composer-deps-analyzer/)
============================
This library allows you to build a dependency graph for an installed composer project.

Usage is quite simple:

```php
<?php

$analyzer = new \JMS\Composer\DependencyAnalyzer();
$graph = $analyzer->analyze($dir);
```

``$graph`` is a directed graph with the packages as nodes.
