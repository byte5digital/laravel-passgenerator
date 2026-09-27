<?php

namespace Byte5\Tests;

use Byte5\PassGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PassGeneratorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $storagePath = sys_get_temp_dir().'/passgenerator-tests-'.uniqid();

        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => $storagePath,
        ]);

        $app['config']->set('filesystems.disks.fixtures', [
            'driver' => 'local',
            'root' => __DIR__.'/Fixtures',
        ]);

        $app['config']->set('passgenerator.storage_disk', 'local');
        $app['config']->set('passgenerator.storage_path', 'passgenerator/certs');
        $app['config']->set('passgenerator.pass_type_identifier', 'pass.com.example.test');
        $app['config']->set('passgenerator.organization_name', 'Test Organization');
        $app['config']->set('passgenerator.team_identifier', 'TEAMID123');
    }
}
