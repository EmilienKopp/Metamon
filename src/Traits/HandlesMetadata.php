<?php

namespace Splitstack\Metamon\Traits;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Builder;
use Splitstack\Metamon\Exceptions\MetadataAccessDeniedException;
use Splitstack\Metamon\Exceptions\MetadataException;
use Splitstack\Metamon\Validators\MetadataValidator;
use Illuminate\Database\Eloquent\Model;

trait HandlesMetadata
{
  protected $metadataColumn = 'metadata';
  protected ?MetadataValidator $validator = null;

  /**
   * Boot the trait, ensuring that metadata is an empty array on saving.
   */
  protected static function bootHasMetadata()
  {
    static::saving(function (Model $model) {
      $metadataColumn = $model->getMetadataColumn();
      if (!is_array($model->{$metadataColumn})) {
        $model->{$metadataColumn} = [];
      }
    });

    static::retrieved(function (Model $model) {
      $metadataColumn = $model->getMetadataColumn();
      if ($model->{$metadataColumn} === null) {
        $model->{$metadataColumn} = [];
      }
    });
  }

  protected function getValidator(): MetadataValidator
  {
    if ($this->validator === null) {
      $this->validator = new MetadataValidator();
    }
    return $this->validator;
  }

  /**
   * Shorthand accessor for getMetadata and setMetadata.
   * If $value is null, get the value.
   * If $value is not null, set the value.
   * 
   * @param string $key
   * @param mixed|null $value
   * @return mixed
   */
  public function meta(string $key = null, $value = null)
  {
    if(is_null($key)) {
      return json_decode(json_encode($this->{$this->getMetadataColumn()}), false);
    }

    $this->getValidator()->validateKeyFormat($key);

    if ($value === null) {
      return $this->getMetadata($key);
    } else {
      return $this->setMetadata($key, $value);
    }
  }

  /**
   * Get the metadata column name.
   *
   * @return string
   */
  public function getMetadataColumn(): string
  {
    return $this->metadataColumn;
  }

  /**
   * Get a value from the metadata.
   *
   * @param string|null $key
   * @param mixed|null $default
   * @return mixed
   */
  public function getMetadata(string $key = null, $default = null)
  {
    $metadata = $this->{$this->getMetadataColumn()};
    return $key ? Arr::get($metadata, $key, $default) : $metadata;
  }

  /**
   * Remove a key from the metadata.
   *
   * @param string $key
   * @return $this
   */
  public function forgetMetadata(string $key): self
  {
    $metadata = $this->{$this->getMetadataColumn()};
    Arr::forget($metadata, $key);
    $this->{$this->getMetadataColumn()} = $metadata;

    return $this;
  }

  /**
   * Scope a query to filter by metadata key/value.
   * Supports dot notation for nested keys.
   *
   * @param Builder $query
   * @param string $key
   * @param mixed $value
   * @return Builder
   */
  public function scopeWhereMetadata(Builder $query, string $key, $value): Builder
  {
    $exploded = explode('.', $key);
    $topLevelKey = array_shift($exploded);

    $topLevelKey = $this->sanitize($topLevelKey);
    $value = $this->sanitize($value);
    
    $whereString = "{$this->getMetadataColumn()}->{$topLevelKey}";
    foreach ($exploded as $nestedKey) {
      $whereString .= "->{$nestedKey}";
    }

    return $query->where($whereString, $value);
  }

  /**
   * Check if a metadata key exists.
   *
   * @param string $key
   * @return bool
   */
  public function hasMetadata(string $key): bool
  {
    $metadata = $this->{$this->getMetadataColumn()};
    return Arr::has($metadata, $key);
  }

  /**
   * Set a value in the metadata with role-based key restrictions.
   * Uses dot notation for nested keys.
   *
   * @param string|array $key
   * @param mixed|null $value
   * @param string|null $role
   * @return $this
   *
   * @throws \Exception
   */
  public function setMetadata(string|array|int $key, $value = null, string $role = null): self
  {
    $allowedKeys = $this->getAllowedMetadataKeys($role);
    if (!in_array('*', $allowedKeys) && !in_array($key, $allowedKeys)) {
      throw new MetadataAccessDeniedException("Key '{$key}' is not allowed for role '{$role}'");
    }

    $this->getValidator()->validateValueSize($value);
    return $this->addMetadata($key, $value);
  }

  /**
   * Add a value to metadata without restrictions.
   *
   * @param string|array $key
   * @param mixed|null $value
   * @return $this
   */
  protected function addMetadata(string|array|int $key, $value = null): self
  {
    $metadata = $this->{$this->getMetadataColumn()};
    $this->getValidator()->validateKeyFormat($key);
    try {
      if (is_array($key)) {
      $this->getValidator()->validateArrayKeyCount($key);
        if($metadata === null) {
          $metadata = [];
        }
        $metadata = array_merge($metadata, $key);
      } else {
      $this->getValidator()->validateNestingDepth($key);
        Arr::set($metadata, $key, $value);
      }
      
      $this->getValidator()->validateTotalSize($metadata);
      $this->validate($metadata);
      
      
      $this->{$this->getMetadataColumn()} = $metadata;
    } catch (\JsonException $e) {
      throw new MetadataException('Failed to process metadata: ' . $e->getMessage());
    }

    return $this;
  }

  /**
   * Get allowed metadata keys for a specific role.
   *
   * @param string|null $role
   * @return array
   */
  public function getAllowedMetadataKeys(?string $role): array
  {
    $this->getValidator()->validateRoleFormat($role);
    $role = empty($role) ? 'default' : $role;
    $rolesConfig = config('metamon.roles', []);
    return $rolesConfig[$role] ?? [];
  }

  private function sanitize($value)
  {
    if (is_string($value)) {
      return preg_replace('/[^a-zA-Z0-9_]/', '', $value);
    }
    return $value;
  }

  private function validate($metadata): void
  {
    $this->getValidator()->validate($metadata);
  }
}
