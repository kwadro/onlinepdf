<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config
set('keep_releases', 2);
set('repository', 'https://github.com/kwadro/onlinepdf.git');
set('git_tty', true);
set('deploy_path', '/home/kwadro/kwadro.com.ua/service');
set('writable_mode', 'chmod');
set('shared_dirs', [
    'var/log','public/build','public/images','public/uploads',
]);

// Hosts
host('production')
    ->setHostname('kwadro.ftp.tools') // або IP
    ->set('remote_user', 'kwadro')
    ->set('branch', 'production')
    ->setIdentityFile('~/.ssh/id_rsa');

// Hooks
after('deploy:failed', 'deploy:unlock');
