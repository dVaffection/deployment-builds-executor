Build files executor
===

### Features
- tracks latest executed file
- sorts new files in timely order
- stops execution on error (executable script return code must be greater than zero)

### Usage
Constructor accepts 2 parameters:
- `$buildsDir` -- a directory where executable files are located
- `$latestBuildFilename` -- a writable file name where `BuildsExecutor` stores latest executed file name

#### List of new builds
```php
$buildsExecutor = new BuildsExecutor($buildsDir, $latestBuildFilename);
$buildFiles     = $buildsExecutor->getNewBuilds();
```

#### New builds execution
`BuildsExecutor::executeNewBuilds()` returns an instance of `Result`. `Result` contains 2 methods:
- `getReturnCode` -- obtain a return code, 0 if all the files were executed successfully, otherwise latest executed file return code
- `getOutput` -- an array of executed file outputs

```php
$buildsExecutor = new BuildsExecutor($buildsDir, $latestBuildFilename);
$result         = $buildsExecutor->executeNewBuilds();
```
