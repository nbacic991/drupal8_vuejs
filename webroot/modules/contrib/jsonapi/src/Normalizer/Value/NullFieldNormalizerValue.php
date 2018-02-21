<?php

namespace Drupal\jsonapi\Normalizer\Value;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Normalizes null fields in accordance with the JSON API specification.
 *
 * @internal
 */
class NullFieldNormalizerValue implements FieldNormalizerValueInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The property type.
   *
   * @var mixed
   */
  protected $propertyType;

  /**
   * {@inheritdoc}
   */
  public function getIncludes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyType() {
    return $this->propertyType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyType($property_type) {
    $this->propertyType = $property_type;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeIncludes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludes(array $includes) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getAllIncludes() {
    return NULL;
  }

}
