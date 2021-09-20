<?php

declare(strict_types = 1);

namespace ClayFreeman\StringStream;

/**
 * Provides a trait to enable serialization for PSR-7 streams.
 *
 * The stream's internal buffer will be converted to a string prior to
 * serialization and stored alongside its current read position.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
trait SerializableStreamTrait {

  /**
   * Magic method to serialize the object.
   *
   * @return array
   *   An array representation of this object at the time of serialization.
   *
   * @internal
   */
  public function __serialize(): array {
    return [
      'buffer' => (string) $this,
      'pos' => $this->tell(),
    ];
  }

  /**
   * Magic method to unserialize the object.
   *
   * @param array $data
   *   An array representation of this object at the time of serialization.
   *
   * @internal
   */
  public function __unserialize(array $data): void {
    // Create a new resource with the supplied buffer content and seek position.
    $this->__construct($data['buffer']);
    $this->seek($data['pos']);
  }

  /**
   * Magic method to serialize the object.
   *
   * @return string
   *   A string representation of this object at the time of serialization.
   *
   * @deprecated in string-stream:1.1.0 and is removed from string-stream:2.0.0.
   *   Serialize the object directly using \serialize().
   *
   * @see https://wiki.php.net/rfc/custom_object_serialization
   * @see https://wiki.php.net/rfc/phase_out_serializable
   */
  public function serialize(): string {
    return \serialize($this->__serialize());
  }

  /**
   * Magic method to unserialize the object.
   *
   * @param string $serialized
   *   A string representation of this object at the time of serialization.
   *
   * @deprecated in string-stream:1.1.0 and is removed from string-stream:2.0.0.
   *   Unserialize the payload directly using \unserialize().
   *
   * @see https://wiki.php.net/rfc/custom_object_serialization
   * @see https://wiki.php.net/rfc/phase_out_serializable
   */
  public function unserialize($serialized): void {
    $this->__unserialize(\unserialize($serialized));
  }

}
