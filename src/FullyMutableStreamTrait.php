<?php

declare(strict_types=1);

namespace ClayFreeman\StringStream;

/**
 * Provides a trait for streams to declare themselves fully-mutable.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @internal
 */
trait FullyMutableStreamTrait {

  /**
   * Returns whether or not the stream is readable.
   *
   * @return bool
   *   Whether or not the stream is readable.
   */
  public function isReadable(): bool {
    return TRUE;
  }

  /**
   * Returns whether or not the stream is seekable.
   *
   * @return bool
   *   Whether or not the stream is seekable.
   */
  public function isSeekable(): bool {
    return TRUE;
  }

  /**
   * Returns whether or not the stream is writable.
   *
   * @return bool
   *   Whether or not the stream is writable.
   */
  public function isWritable(): bool {
    return TRUE;
  }

}
