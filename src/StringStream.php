<?php

declare(strict_types = 1);

namespace ClayFreeman\StringStream;

use Psr\Http\Message\StreamInterface;

/**
 * Provides a wrapper used to treat strings as in-memory streams.
 *
 * @license http://mozilla.org/MPL/2.0/ MPL-2.0
 * This file is subject to the terms of the Mozilla Public License, v2.0. If a
 * copy of the MPL was not distributed with this file, You can obtain one at the
 * above URI.
 */
class StringStream implements \Serializable, StreamInterface {

  use CloneableStreamTrait;
  use SerializableStreamTrait;

  /**
   * The internal memory buffer.
   *
   * @var resource|null
   */
  protected $buffer = NULL;

  /**
   * Constructs a StringStream object.
   *
   * @param string $input
   *   The input string to be copied to an in-memory buffer.
   */
  public function __construct(string $input = '') {
    // Create an internal memory buffer used to store the stream.
    if (($buffer = \fopen('php://memory', 'w+')) !== FALSE) {
      $this->buffer = $buffer;

      // Write the supplied input to the buffer and rewind it.
      $this->write($input);
      $this->rewind();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    $pos = $this->tell();

    $this->rewind();
    $str = $this->getContents();
    $this->seek($pos);

    return $str;
  }

  /**
   * Calculate the final seek position from an offset and whence.
   *
   * This method is read-only and DOES NOT modify the stream offset.
   *
   * @param int $offset
   *   The desired offset from $whence.
   * @param int $whence
   *   Specifies how the cursor position will be calculated. Valid values are
   *   identical to the built-in PHP $whence values for `\fseek()`:
   *    - \SEEK_CUR: Set position to current location plus offset.
   *    - \SEEK_END: Set position to end-of-stream plus offset.
   *    - \SEEK_SET: Set position equal to offset bytes.
   *
   * @throws \RuntimeException
   *   When the current size of the stream cannot be determined.
   *
   * @internal
   *
   * @return int
   *   The theoretical final position resulting from a potential seek operation.
   */
  protected function calculateSeekPosition(int $offset, int $whence): int {
    $pos = $this->tell();

    // Calculate the final offset into the stream.
    switch ($whence) {
      case \SEEK_CUR:
        $pos += $offset;
        break;

      case \SEEK_END:
        if (($size = $this->getSize()) === NULL) {
          throw new \RuntimeException();
        }

        $pos = $size + $offset;
        break;

      case \SEEK_SET:
        $pos = $offset;
        break;
    }

    return $pos;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): void {
    // Ensure the buffer is valid before closing it.
    if (\is_resource($this->buffer)) {
      \fclose($this->buffer);
      $this->buffer = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function detach() {
    // Move the resource into a local variable and reset the internal state.
    $resource = $this->buffer;
    $this->buffer = NULL;

    // Return the now-detatched buffer resource.
    return $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function eof(): bool {
    // Check if we've reached EOF on a valid buffer; FALSE for invalid buffer.
    // This flag will only be set after a read is attempted at EOF.
    return \is_resource($this->buffer) ? \feof($this->buffer) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): string {
    if (($size = $this->getSize()) === NULL) {
      throw new \RuntimeException();
    }

    // Attempt to read and return the remainder of the buffer.
    if ($size > ($pos = $this->tell())) {
      return $this->read($size - $pos);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key = NULL) {
    // If a specific key was requested, return NULL. Otherwise return an empty
    // array. This class doesn't support metadata, so empty values are returned.
    return $key !== NULL ? NULL : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSize(): ?int {
    $info = [];

    // Check if the buffer is valid before checking its statistics.
    if (\is_resource($this->buffer)) {
      $info = \fstat($this->buffer);
    }

    // If there's a numeric size available, return it.
    if (\is_array($info) && \array_key_exists('size', $info) && \is_numeric($info['size'])) {
      return (int) $info['size'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isReadable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSeekable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isWritable(): bool {
    return TRUE;
  }

  /**
   * Peek at the next byte in the stream.
   *
   * This method will retrieve the current stream position and store it. Next,
   * an attempt is made to read a single byte from the stream. Finally, the
   * stream is reset to its original position and the character is returned.
   *
   * @throws \RuntimeException
   *   If this method is unable to do any of the following:
   *     1. Fetch the current stream position.
   *     2. Read a single character.
   *     3. Seek to the original stream position.
   *
   * @return string
   *   The next character in the string, or an empty string on EOF.
   */
  public function peek(): string {
    $pos = $this->tell();
    $chr = $this->read(1);

    if ($chr !== '') {
      $this->seek($pos);
    }

    return $chr;
  }

  /**
   * {@inheritdoc}
   */
  public function read($length): string {
    // Attempt to read from a valid buffer, throw an exception on failure.
    if (!\is_resource($this->buffer) || ($string = \fread($this->buffer, $length)) === FALSE) {
      throw new \RuntimeException();
    }

    return $string;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->seek(0);
  }

  /**
   * Perform a direct seek on the internal buffer using `\fseek()`.
   *
   * @param int $offset
   *   The offset value to use when seeking.
   * @param int $whence
   *   One of \SEEK_CUR, \SEEK_END, \SEEK_SET.
   *
   * @see \fseek()
   *   For more information on the values for $whence.
   *
   * @throws \RuntimeException
   *   If the seek operation fails.
   *
   * @internal
   */
  protected function realSeek(int $offset, int $whence): void {
    if (!\is_resource($this->buffer) || \fseek($this->buffer, $offset, $whence) !== 0) {
      throw new \RuntimeException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function seek($offset, $whence = \SEEK_SET): void {
    // Ensure that the buffer is valid before continuing.
    if (!\is_resource($this->buffer) || ($size = $this->getSize()) === NULL) {
      throw new \RuntimeException();
    }

    // Calculate the final position of the stream and fetch the stream size.
    $pos = $this->calculateSeekPosition($offset, $whence);

    // Check if padding is required to seek to the requested position.
    if ($pos > $size) {
      // Calculate the number of bytes needed to pad the end of the buffer.
      $remaining = $pos - $size;

      // Seek to the end and write the padding bytes.
      $this->realSeek(0, \SEEK_END);
      $this->write(\str_pad('', $remaining, "\0"));
    }
    else {
      // Padding isn't required; attempt to seek to the requested position.
      $this->realSeek($offset, $whence);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tell(): int {
    // Ensure that the buffer is valid before continuing.
    if (!\is_resource($this->buffer) || ($pos = \ftell($this->buffer)) === FALSE) {
      throw new \RuntimeException();
    }

    return $pos;
  }

  /**
   * {@inheritdoc}
   */
  public function write($string): int {
    // Attempt to write to a valid buffer, throw an exception on failure.
    if (!\is_resource($this->buffer) || ($bytes = \fwrite($this->buffer, $string)) === FALSE) {
      throw new \RuntimeException();
    }

    return $bytes;
  }

}
