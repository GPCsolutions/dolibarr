# composer.json comments
Since the JSON format don't allow embedding comments, this file holds them.
They are structure just like the composer.json tree.

## config
### process-timeout
Set to 3600 seconds because checking PHPCompatibility can take quite some time!

### vendor-dir
We host composer installed dependencies in our legacy includes directory.

### bin-dir
Since our vendor directory (htdocs/includes) is part of the repository and we
don't want binaries distributed with Dolibarr, we moved them to a developer's
directory that is not part of the distribution.

## scripts
You can run these using ```composer [command]```.
Since these are run from within composer, they use the binaries from bin-dir,
not the host ones, making the process more reliable.

### compat_workaround
This is a workaround https://github.com/wimg/PHPCompatibility/issues/102

### check_compat
If TRAVIS_PHP_VERSION is set, we use it. This way we only check the Travis' matrix
PHP version when running this on Travis. Else we set it to all supported PHP versions
(5.3 to 7.0).
