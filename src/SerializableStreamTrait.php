<?php

declare(strict_types = 1);

namespace ClayFreeman\StringStream;

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
   * Magic method to serialize the object.
   *
   * @return string
   *   The entire contents of the buffer.
   */
  public function serialize(): string {
    // Capture the internal resource's buffer content and seek position so that
    // its state can be restored.
    return \serialize([
      'buffer' => (string) $this,
      'pos' => $this->tell(),
    ]);
  }

  /**
   * Magic method to unserialize the object.
   *
   * @param string $serialized
   *   The buffer contents.
   */
  public function unserialize($serialized): void {
    // Unserialize the supplied data for use in restoring the resource state.
    $state = \unserialize($serialized);

    // Create a new resource with the supplied buffer content and seek position.
    $this->__construct($state['buffer']);
    $this->seek($state['pos']);
  }

}
