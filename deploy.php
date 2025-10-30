<?php
namespace Deployer;

require 'recipe/symfony.php';
require 'recipe/common.php';

// Nombre de la aplicación
set('application', 'GreenHarvest');
// Desactivar sistema de releases
set('keep_releases', 0);
set('use_relative_symlink', false);
set('use_atomic_symlink', false);

// Desactivar completamente las tareas de releases
//after('deploy:prepare', 'deploy:update_code');
//after('deploy:update_code', 'deploy:vendors');
//after('deploy:vendors', 'deploy:cache:clear');

// Repositorio Git

set('repository', 'https://github.com/mcanadas29dev/UnedGsdProject.git');
//set('repository', 'git@github.com:mcanadas29dev/UnedGsdProject.git');
// Rama a desplegar
set('branch', 'main');
// Ruta en el servidor
set('deploy_path', '/var/www/wwwmarcelo');
// Variables de entorno Symfony
set('symfony_env', 'prod');
set('symfony_console_path', 'bin/console');

// Archivos/directorios que se mantienen entre releases
add('shared_files', ['.env.local']);
add('shared_dirs', ['var/log', 'var/sessions', 'public/uploads']);

// Directorios que deben tener permisos
add('writable_dirs', ['var', 'public/uploads']);


// Configuración del host (formato nuevo)
host('192.168.0.40')
    ->setRemoteUser('marcelo')
    ->setIdentityFile('~/.ssh/id_ed25519')
    ->setDeployPath('/var/www/wwwmarcelo');

// Tareas personalizadas

// Migraciones de base de datos
/*
task('database:migrate', function () {
    run('cd {{release_path}} && php {{bin/php}} {{symfony_console}} doctrine:migrations:migrate --no-interaction');
});
*/
// Clear cache
task('cache:clear', function () {
    run('cd {{release_path}} && php {{bin/php}} {{symfony_console}} cache:clear --env={{symfony_env}}');
});

// Rollback si falla
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'cleanup');
// Flujo de despliegue
desc('Deploy GreenHarvest Symfony');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    //'deploy:vendors',
    //'database:migrate',
    'cache:clear',
      //'cleanup',
]);


