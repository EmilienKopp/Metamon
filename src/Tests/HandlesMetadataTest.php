<?php

namespace Splitstack\Metamon\Tests;

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
    config(['metadata.roles.admin' => ['allowed_key']]);

    $this->expectException(\Exception::class);

    $this->model->setMetadata('restricted_key', 'value', 'admin');
  }
}