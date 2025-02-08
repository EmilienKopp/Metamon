<?php

namespace Splitstack\Metamon;

use Illuminate\Support\ServiceProvider;

class MetamonServiceProvider extends ServiceProvider
{
  public function boot()
  {
    $this->publishes([
      __DIR__ . '/../config/metamon.php' => config_path('metamon.php'),
    ], 'metamon-config');
  }

  public function register()
  {
    $this->mergeConfigFrom(
      __DIR__ . '/../config/metamon.php',
      'metamon'
    );
  }
}