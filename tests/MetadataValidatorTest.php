<?php

namespace Splitstack\Metamon\Tests;

use Splitstack\Metamon\Validators\MetadataValidator;
use Splitstack\Metamon\Exceptions\InvalidMetadataKeyException;
use Splitstack\Metamon\Exceptions\MetadataException;

class MetadataValidatorTest extends TestCase
{
    private MetadataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new MetadataValidator();
    }

    /** @test */
    public function it_validates_key_format()
    {
        // Valid key
        $this->validator->validateKeyFormat('valid_key');

        // Invalid types
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat(['array_key']);

        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat(123);

        // Too long key
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat(str_repeat('a', 256));
    }

    /** @test */
    public function it_validates_value_size()
    {
        // Valid values
        $this->validator->validateValueSize('short value');
        $this->validator->validateValueSize(['array' => 'value']);
        $this->validator->validateValueSize(123);

        // Too long string value
        $this->expectException(MetadataException::class);
        $this->validator->validateValueSize(str_repeat('a', 65536));
    }

    /** @test */
    public function it_validates_array_key_count()
    {
        // Valid array
        $this->validator->validateArrayKeyCount(array_fill(0, 100, 'value'));

        // Too many keys
        $this->expectException(MetadataException::class);
        $this->validator->validateArrayKeyCount(array_fill(0, 101, 'value'));
    }

    /** @test */
    public function it_validates_nesting_depth()
    {
        // Valid nesting
        $this->validator->validateNestingDepth('level1.level2.level3.level4.level5.level6');

        // Too deep nesting
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateNestingDepth('l1.l2.l3.l4.l5.l6.17');
    }

    /** @test */
    public function it_validates_total_size()
    {
        // Valid size
        $this->validator->validateTotalSize(['key' => 'value']);
        
        // Too large metadata
        $largeData = [];
        for ($i = 0; $i < 100000; $i++) {
            $largeData["key$i"] = str_repeat('a', 100);
        }
        
        $this->expectException(MetadataException::class);
        $this->validator->validateTotalSize($largeData);
    }

    /** @test */
    public function it_validates_role_format()
    {
        // Valid roles
        $this->validator->validateRoleFormat('admin');
        $this->validator->validateRoleFormat('user_role');
        $this->validator->validateRoleFormat('role-123');
        $this->validator->validateRoleFormat(null);

        // Invalid role format
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateRoleFormat('invalid@role');
    }

    /** @test */
    public function it_validates_key_length()
    {
        // Valid keys
        $this->validator->validateKeyFormat('short_key');
        $this->validator->validateKeyFormat(['key1', 'key2']);
        $this->validator->validateKeyFormat('level1.level2');

        // Single key too long
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat(str_repeat('a', 64));

        // Array key too long
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat([str_repeat('a', 64) => 'value']);

        // Nested key too long
        $this->expectException(InvalidMetadataKeyException::class);
        $this->validator->validateKeyFormat('valid.' . str_repeat('a', 64));
    }

    /** @test */
    public function it_validates_json_encoding()
    {
        // Valid metadata
        $this->validator->validate(['key' => 'value']);
        $this->validator->validate('string value');
        $this->validator->validate(123);

        // Invalid metadata (resource can't be JSON encoded)
        $resource = fopen('php://memory', 'r');
        $this->expectException(MetadataException::class);
        $this->validator->validate(['key' => $resource]);
        fclose($resource);
    }
}
