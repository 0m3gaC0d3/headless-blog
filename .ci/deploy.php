<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';

host('production')
    ->hostname('lupus-code.it')
    ->user('root')
    ->set('deploy_path', '/var/www/html/api.lupus-code.it');

// Config
set('php_path', '/usr/bin/php');
set('bin_folder', './vendor/bin');
add('shared_files', [
    '.env'
]);
add('shared_dirs', [
    'keys',
]);
add('writable_dirs', [
    'keys',
]);
set('rsync_src', './');
set('rsync', [
    'exclude' => [
        '.env*',
        '.git*',
        'docker-compose.yml',
        '.ci',
        '.idea',
        '.dpl',
        '.php_cs',
        '.travis.yml',
        'phpcs.xml',
        'phpmd.xml',
        'phpstan.neon',
        'phpunit.xml',
        '.phpunit.result.cache',
        'doc',
        'tests',
    ],
    'exclude-file' => false,
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz', // Recursive, with compress
    'options' => ['delete', 'times', 'perms', 'links', 'delete-excluded'],
    'timeout' => 600,
]);

// Tasks
task('build', function () {
    run('rm -rf vendor'); // Remove old vendor data.
    run('composer install --no-dev');
})->local();

task('opcache', function () {
    run('curl -sLO https://github.com/gordalina/cachetool/releases/latest/download/cachetool.phar && chmod +x cachetool.phar');
    run('{{php_path}} cachetool.phar opcache:reset --fcgi=/var/lib/php7.2-fpm/web1021.sock --tmp-dir /var/www/clients/client1/web1021/tmp');
});

task('clear-cache', function () {
    run('cd {{release_path}} && {{php_path}} {{bin_folder}}console cache:clear');
});

task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'build',
    'deploy:release',
    'rsync',
    'deploy:shared',
    'deploy:symlink',
//    'opcache',
//    'clear-cache',
    'deploy:unlock',
    'cleanup',
    'success'
])->desc('Deploy API');

after('deploy:failed', 'deploy:unlock');