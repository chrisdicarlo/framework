<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Failed\DatabaseFailedJobProvider;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class ForgetFailedCommandTest extends TestCase
{
    public function testForgetsSingleNumericId()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
        });

        $this->app['queue.failer'] = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $deleteJob = $db->getConnection()->table('failed_jobs')->insertGetId([]);
        $retainJob = $db->getConnection()->table('failed_jobs')->insertGetId([]);


        $this->artisan('queue:forget 1');

        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->where('id', '=', $deleteJob)->count());
    }

     public function testForgetsMultipleNumericIds()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
        });

        $this->app['queue.failer'] = new DatabaseFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $deleteJob1 = $db->getConnection()->table('failed_jobs')->insertGetId([]);
        $deleteJob2 = $db->getConnection()->table('failed_jobs')->insertGetId([]);
        $deleteJob3 = $db->getConnection()->table('failed_jobs')->insertGetId([]);
        $retainJob = $db->getConnection()->table('failed_jobs')->insertGetId([]);

        $this->artisan('queue:forget 1 2 3');
        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->whereIn('id', [$deleteJob1,$deleteJob2,$deleteJob3])->count());
    }

    public function testForgetsSingleUuid()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
        });

        $this->app['queue.failer'] = new DatabaseUuidFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $deleteJob = $db->getConnection()->table('failed_jobs')->insert(['uuid' => Str::uuid()]);
        $retainJob = $db->getConnection()->table('failed_jobs')->insert(['uuid' => Str::uuid()]);

        $deleteJob = $db->getConnection()->table('failed_jobs')->where('id', '=', $deleteJob)->first();

        $this->artisan('queue:forget '.$deleteJob->uuid);

        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->where('uuid', '=', $deleteJob->uuid)->count());
    }

     public function testForgetsMultipleUuids()
    {
        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->getConnection()->getSchemaBuilder()->create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
        });

        $this->app['queue.failer'] = new DatabaseUuidFailedJobProvider($db->getDatabaseManager(), 'default', 'failed_jobs');

        $deleteJob1 = $db->getConnection()->table('failed_jobs')->insertGetId(['uuid' => Str::uuid()]);
        $deleteJob2 = $db->getConnection()->table('failed_jobs')->insertGetId(['uuid' => Str::uuid()]);
        $deleteJob3 = $db->getConnection()->table('failed_jobs')->insertGetId(['uuid' => Str::uuid()]);
        $retainJob = $db->getConnection()->table('failed_jobs')->insertGetId(['uuid' => Str::uuid()]);

        $uuids = $db->getConnection()
            ->table('failed_jobs')
            ->whereIn('id', [$deleteJob1,$deleteJob2,$deleteJob3])
            ->get()
            ->map(fn($item, $key) => $item->uuid)
            ->implode(' ');

        $this->artisan('queue:forget '.$uuids);
        $this->assertSame(1, $db->getConnection()->table('failed_jobs')->count());
        $this->assertSame(0, $db->getConnection()->table('failed_jobs')->whereIn('id', [$deleteJob1,$deleteJob2,$deleteJob3])->count());
    }
}
