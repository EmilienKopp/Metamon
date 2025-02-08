<?php

namespace Splitstack\Metamon\Tests;

use Illuminate\Database\Eloquent\Model;
use Splitstack\Metamon\Traits\HandlesMetadata;

class TestModel extends Model
{
  use HandlesMetadata;

  protected $guarded = [];
  protected $casts = [
    'metadata' => 'array'
  ];
}