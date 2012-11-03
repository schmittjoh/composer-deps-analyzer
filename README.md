Composer Dependency Analyzer
============================
This library allows you to build a dependency graph for an installed composer project.

Usage is quite simple:

```php
<?php

$analyzer = new \JMS\Composer\DependencyAnalyzer();
$graph = $analyzer->analyze($dir);
```

``$graph`` is a directed graph with the packages as nodes.