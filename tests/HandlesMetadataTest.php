<?php

namespace Splitstack\Metamon\Tests;

use stdClass;

class HandlesMetadataTest extends TestCase
{
  protected TestModel $model;

  protected function setUp(): void
  {
    parent::setUp();

    $this->model = TestModel::create([
      'metadata' => [
        'initial' => 'some_value',
        'nested' => [
          'key' => 'value'
        ],
        'supernested' => [
          'first_level' => [
            'second_level' => 'value'
          ]
        ]
      ]
    ]);
  }

  /** @test */
  public function it_set_config_properly()
  {
    $expected = [
      'admin' => ['*'],
      'user' => ['allowed_key'],
      'default' => ['*']
    ];
    $this->assertEquals($expected, config('metamon.roles'));
  }

  /** @test */
  public function it_throws_on_invalid_role_format()
  {
    $this->expectException(\Splitstack\Metamon\Exceptions\InvalidMetadataKeyException::class);
    $this->model->setMetadata('allowed_key', 'value', 'user');
    $this->model->setMetadata('allowed_key', 'value', 'invalid role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid-role!');
    $this->model->setMetadata('allowed_key', 'value', 'invalid%role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid%role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid#role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid&role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid(role)');
    $this->model->setMetadata('allowed_key', 'value', 'invalid|role');
    $this->model->setMetadata('allowed_key', 'value', 'invalid~role');
  }

  /** @test */
  public function it_throws_on_access_denied()
  {
    config(['metadata.roles.guest' => ['allowed_key']]);

    $this->expectException(\Splitstack\Metamon\Exceptions\MetadataAccessDeniedException::class);

    $this->model->setMetadata('restricted_key', 'value', 'guest');
  }

  /** @test */
  public function it_can_set_and_get_metadata()
  {
    $this->model->setMetadata('test_key', 'test_value');
    $this->assertEquals('test_value', $this->model->getMetadata('test_key'));
  }

  /** @test */
  public function it_can_retrieve_metadata()
  {
    $this->assertEquals('some_value', $this->model->getMetadata('initial'));
  }

  /** @test */
  public function it_can_persist_and_retrieve_metadata()
  {
    $this->model->setMetadata('test_key', 'test_value');
    $this->model->save();
    $this->model->refresh();
    $this->assertEquals('test_value', $this->model->getMetadata('test_key'));
  }

  /** @test */
  public function it_can_check_if_metadata_exists()
  {
    $this->model->setMetadata('test_key', 'test_value');

    $this->assertTrue($this->model->hasMetadata('test_key'));
    $this->assertFalse($this->model->hasMetadata('nonexistent_key'));
  }

  /** @test */
  public function it_can_be_passed_associative_arrays()
  {
    $array = [
      'first' => 'value',
      'second' => 'value'
    ];

    $this->model->setMetadata($array);

    $this->assertEquals('value', $this->model->getMetadata('first'));
    $this->assertEquals('value', $this->model->getMetadata('second'));
  }

  /** @test */
  public function associative_arrays_do_merge_instead_of_overwriting()
  {
    $this->model->setMetadata('test_key', 'test_value');
    $this->model->setMetadata('other_key', 'other_value');
    $this->model->setMetadata(['test_key' => 'new_value']);

    $this->assertEquals('new_value', $this->model->getMetadata('test_key'));
    $this->assertEquals('other_value', $this->model->getMetadata('other_key'));
  }

  /** @test */
  public function it_can_access_with_dot_notation()
  {
    $this->assertEquals('value', $this->model->getMetadata('nested.key'));
    $this->assertEquals('value', $this->model->getMetadata('supernested.first_level.second_level'));
  }

  /** @test */
  public function it_can_use_meta_shorthand_without_argument_to_return_object()
  {
    $object = $this->model->meta();
    $this->assertInstanceOf(stdClass::class, $object);
    $this->assertEquals('some_value', $object->initial);
  }

  /** @test */
  public function it_can_use_meta_shorthand_without_argument_to_access_nested()
  {
    $val = $this->model->meta()->initial;
    $otherVal = $this->model->meta()->nested->key;
    $this->assertEquals('some_value', $val);
    $this->assertEquals('value', $otherVal);
  }

  /** @test */
  public function it_can_get_and_set_with_shorthand()
  {
    $this->model->meta('test_key', 'test_value');
    $this->assertEquals('test_value', $this->model->meta('test_key'));

    $this->model->save();
    $this->model->refresh();
    $found = TestModel::whereMetadata('test_key', 'test_value')->first();
    $this->assertNotNull($found);
  }

  /** @test */
  public function it_can_get_with_shorthand_and_dot_notation()
  {
    $this->model->meta('nested.key', 'new_value');
    $this->assertEquals('new_value', $this->model->meta()->nested->key);
  }

  /** @test */
  public function it_safely_handles_sql_injection_in_metadata_keys()
  {
    $this->expectException(\Illuminate\Database\QueryException::class);
    $maliciousKeys = [
      "';DROP TABLE test_models;--",
      "metadata->>'$.key' = 'value' OR 1=1;--",
      "safe_key' OR '1'='1",
      "supernested->first_level'; SELECT * FROM test_models;--",
      "); DROP TABLE test_models;--",
      "' UNION SELECT id FROM test_models;--"
    ];

    foreach ($maliciousKeys as $maliciousKey) {
      $this->expectException(\Illuminate\Database\QueryException::class);
      $result = TestModel::whereMetadata($maliciousKey, 'any_value')->get();
    }

    $maliciousNestedKeys = [
      "nested.'; DROP TABLE test_models;--",
      "nested.key' OR '1'='1",
      "supernested.first_level'; DELETE FROM test_models;--"
    ];

    foreach ($maliciousNestedKeys as $maliciousKey) {
      $result = TestModel::whereMetadata($maliciousKey, 'any_value')->get();
      $this->assertEmpty($result, "Nested SQL injection attempt with key: {$maliciousKey} should return no results");
    }
  }

  /** @test */
  public function it_safely_handles_sql_injection_in_metadata_values()
  {

    $maliciousValues = [
      "' OR '1'='1",
      "'; DROP TABLE test_models;--",
      "' UNION SELECT * FROM test_models;--",
      "' OR metadata->>'$.admin' = 'true';--",
      null,
      "key; DROP TABLE test_models;--",
      ["'; DROP TABLE test_models;--"],
    ];

    foreach ($maliciousValues as $maliciousValue) {
      $result = TestModel::whereMetadata('nested.key', $maliciousValue)->get();
      $this->assertEmpty($result, "SQL injection attempt with value should return no results: " . json_encode($maliciousValue));

      $this->assertDatabaseHas('test_models', ['id' => $this->model->id]);
    }

    $asObj = (object) ["malicious" => "'; SELECT * FROM test_models;--"];
    $result = TestModel::whereMetadata('nested.key', json_encode($asObj))->get();
    $this->assertEmpty($result, "SQL injection attempt with object value should return no results: " . json_encode($asObj));
  }

  /** @test */
  public function it_generates_expected_sql_for_nested_paths()
  {
    \DB::enableQueryLog();

    TestModel::whereMetadata('supernested.first_level', 'test_value')->get();

    $queries = \DB::getQueryLog();
    $lastQuery = end($queries);

    $this->assertStringContainsString(
      '"metadata"->\'supernested\'->>\'first_level\'',
      $lastQuery['query'],
      'Query should contain properly constructed JSON path'
    );

    \DB::disableQueryLog();
  }

  /** @test */
  public function it_can_persist_and_retrieve_with_meta_shorthand_dot_notation()
  {
    $this->model->meta('nested.key', 'deezNuts');
    $this->model->save();
    $this->model->refresh();
    $this->assertEquals('deezNuts', $this->model->meta()->nested->key);

    $found = TestModel::whereMetadata('nested.key', 'deezNuts')->first();
    $this->assertNotNull($found);
    $this->assertEquals($this->model->id, $found->id);
  }

  /** @test */
  public function it_can_forget_metadata()
  {
    $this->model->setMetadata('test_key', 'test_value');
    $this->model->forgetMetadata('test_key');

    $this->assertFalse($this->model->hasMetadata('test_key'));
  }

  /** @test */
  public function it_can_query_by_metadata()
  {
    $this->model->setMetadata('test_key', 'test_value');

    $this->model->save();
    $found = TestModel::whereMetadata('test_key', 'test_value')->first();

    $this->assertNotNull($found);
    $this->assertEquals($this->model->id, $found->id);
  }

  /** @test */
  public function it_can_query_by_nested_metadata()
  {
    $found = TestModel::whereMetadata('nested.key', 'value')->first();
    $this->assertNotNull($found);
    $this->assertEquals($this->model->id, $found->id);

    $found = TestModel::whereMetadata('supernested.first_level.second_level', 'value')->first();
    $this->assertNotNull($found);
    $this->assertEquals($this->model->id, $found->id);
  }

  /** @test */
  public function it_respects_role_based_access()
  {
    $this->expectException(\Splitstack\Metamon\Exceptions\MetadataAccessDeniedException::class);

    $this->model->setMetadata('restricted_key', 'value', 'user');
  }

  /** @test */
  public function it_throws_on_value_length_over_65535()
  {
    $this->expectException(\Splitstack\Metamon\Exceptions\MetadataException::class);
    $this->model->setMetadata('allowed_key', str_repeat('a', 65535));
    $this->model->setMetadata('allowed_key', str_repeat('a', 65536));
  }

  /** @test */
  public function it_throws_on_key_length_over_63()
  {
    $this->expectException(\Splitstack\Metamon\Exceptions\InvalidMetadataKeyException::class);
    $this->model->setMetadata(str_repeat('a', 63), 'value');
    $this->model->setMetadata(str_repeat('a', 64), 'value');
  }

  
}
