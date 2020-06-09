<?php

declare(strict_types = 1);

namespace ClayFreeman\Stream;

/**
 * Provides a trait to enable serialization for PSR-7 streams.
 *
 * @license http://mozilla.org/MPL/2.0/ MPL-2.0
 * This file is subject to the terms of the Mozilla Public License, v2.0. If a
 * copy of the MPL was not distributed with this file, You can obtain one at the
 * above URI.
 */
trait SerializableStreamTrait {

  /**
   * Magic method to help serialize the object.
   *
   * @return string
   *   The entire contents of the buffer.
   */
  public function serialize(): string {
    // Save the current position of the stream before serializing it.
    $pos = $this->tell();
    // Attempt to serialize the stream's buffer & position using json_encode().
    $str = json_encode([
      'buffer' => (string) $this,
      'pos' => $pos,
    ]);

    // Check if a failure occurred when running json_encode().
    if ($str === FALSE) {
      $str = '';
    }

    // Restore the previous position to the stream.
    $this->seek($pos);
    return $str;
  }

  /**
   * Magic method to help unserialize the object.
   *
   * @param string $serialized
   *   The buffer contents.
   */
  public function unserialize($serialized): void {
    if ($serialized !== '') {
      $state = json_decode($serialized);
      $this->__construct($state->buffer);
      $this->seek($state->pos);
    }
  }

}
