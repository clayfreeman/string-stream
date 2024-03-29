<?php

declare(strict_types = 1);

namespace ClayFreeman\StringStream;

/**
 * Provides a trait to enable cloning for PSR-7 streams.
 *
 * @license https://opensource.org/licenses/MIT MIT
 */
trait CloneableStreamTrait {

  /**
   * Clones the internal state of this object.
   */
  public function __clone() {
    // At this point, both the cloned instance and the original instance still
    // refer to the same underlying resource.
    //
    // Fetch the original resource's buffer content and current position.
    $buffer = (string) $this;
    $pos = $this->tell();

    // Replace the original resource in the cloned instance with a new resource
    // containing the original resource's buffer content.
    $this->__construct((string) $this);
    // Restore the seek position from the original resource.
    $this->seek($pos);
  }

}
