<?php

namespace Splitstack\Metamon\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Splitstack\Metamon\MetamonServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestCase extends Orchestra
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    $this->setUpDatabase();
  }

  protected function getPackageProviders($app)
  {
    return [
      MetamonServiceProvider::class,
    ];
  }

  protected function getEnvironmentSetUp($app)
  {
    // Database configuration
    $app['config']->set('database.default', 'pgsql');
    $app['config']->set('database.connections.pgsql', [
      'driver' => 'pgsql',
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '54329'),
      'database' => env('DB_DATABASE', 'testing'),
      'username' => env('DB_USERNAME', 'postgres'),
      'password' => env('DB_PASSWORD', 'password'),
      'charset' => 'utf8',
      'prefix' => '',
      'schema' => 'public',
      'sslmode' => 'prefer',
    ]);
    
    // Metadata configuration
    $app['config']->set('metamon.roles.admin', ['allowed_key']);
  }

  protected function setUpDatabase()
  {
    // Create test table
    Schema::create('test_models', function (Blueprint $table) {
      $table->id();
      $table->jsonb('metadata')->nullable(); // Using jsonb for better performance in PostgreSQL
      $table->timestamps();
    });
  }
}