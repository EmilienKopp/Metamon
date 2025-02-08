<?php

namespace Splitstack\Metamon\Validators;

use Splitstack\Metamon\Exceptions\InvalidMetadataKeyException;
use Splitstack\Metamon\Exceptions\MetadataException;

class MetadataValidator
{
    /**
     * Validate the format of a metadata key.
     *
     * @param string|array|int $key
     * @throws InvalidMetadataKeyException
     */
    public function validateKeyFormat(string|array|int $key): void
    {
        if (is_array($key)) {
            $this->validateArrayKeyCount($key);
            foreach ($key as $k => $value) {
                if (strlen($k) > 63) {
                    throw new InvalidMetadataKeyException('Metadata key cannot exceed 64 characters');
                }
            }
        } else if (str_contains($key, '.')) {
            $this->validateNestingDepth($key);
            $key = explode('.', $key);
            foreach ($key as $k) {
                if (strlen($k) > 63) {
                    throw new InvalidMetadataKeyException('Metadata key cannot exceed 64 characters');
                }
            }
        } else if (strlen($key) > 63) {
            throw new InvalidMetadataKeyException('Metadata key cannot exceed 64 characters');
        }
    }

    /**
     * Validate the size of a metadata value.
     *
     * @param mixed $value
     * @throws MetadataException
     */
    public function validateValueSize($value): void
    {
        if (is_string($value) && strlen($value) > 65535) {
            throw new MetadataException('Metadata value size exceeds maximum limit');
        }
    }

    /**
     * Validate the number of keys in an array.
     *
     * @param array $key
     * @throws MetadataException
     */
    public function validateArrayKeyCount(array $key): void
    {
        if (count($key) > 100) {
            throw new MetadataException('Too many metadata keys in array');
        }
    }

    /**
     * Validate the nesting depth of a key.
     *
     * @param string|int $key
     * @throws InvalidMetadataKeyException
     */
    public function validateNestingDepth($key): void
    {
        if (substr_count((string) $key, '.') > 5) {
            throw new InvalidMetadataKeyException('Metadata nesting depth exceeds maximum limit of 5');
        }
    }

    /**
     * Validate the total size of metadata.
     *
     * @param mixed $metadata
     * @throws MetadataException
     */
    public function validateTotalSize($metadata): void
    {
        if (strlen(json_encode($metadata)) > 1048576) { // 1MB limit
            throw new MetadataException('Total metadata size exceeds maximum limit');
        }
    }

    /**
     * Validate the format of a role name.
     *
     * @param string|null $role
     * @throws InvalidMetadataKeyException
     */
    public function validateRoleFormat(?string $role): void
    {
        if (!is_null($role) && !preg_match('/^[a-zA-Z0-9_-]+$/', $role)) {
            throw new InvalidMetadataKeyException('Invalid role name format');
        }
    }

    /**
     * Validate that metadata can be JSON encoded.
     *
     * @param mixed $metadata
     * @throws MetadataException
     */
    public function validate($metadata): void
    {
        try {
            json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MetadataException('Metadata was provided in an invalid format' . $e->getMessage());
        }
    }
}
