<?php

declare(strict_types = 1);

namespace ClayFreeman;

use Psr\Http\Message\StreamInterface;

/**
 * Provides a wrapper used to treat strings as in-memory streams.
 *
 * @license http://mozilla.org/MPL/2.0/ MPL-2.0
 * This file is subject to the terms of the Mozilla Public License, v2.0. If a
 * copy of the MPL was not distributed with this file, You can obtain one at the
 * above URI.
 */
class StringStream implements StreamInterface {

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
    $this->buffer = \fopen('php://memory', 'w+');
    // Write the supplied input to the stream and rewind back to the beginning.
    $this->write($input);
    $this->rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    // Rewind to the beginning of the stream and fetch the remaining contents.
    $this->rewind();
    return $this->getContents();
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
  public function getSize(): ?int {
    // Check if the buffer is valid before retrieving its length.
    if (\is_resource($this->buffer)) {
      return \fstat($this->buffer)['size'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function tell(): int {
    // Ensure that the buffer is valid before continuing.
    if (!\is_resource($this->buffer)) {
      throw new \RuntimeException();
    }

    return \ftell($this->buffer);
  }

  /**
   * {@inheritdoc}
   */
  public function eof(): bool {
    // Check if we've reached EOF on a valid buffer; FALSE for invalid buffer.
    // This flag will only be set after a read is attempted while at EOF offset.
    return \is_resource($this->buffer) ? \feof($this->buffer) : FALSE;
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
  public function seek($offset, $whence = SEEK_SET): void {
    // Ensure that we have a valid buffer before continuing.
    if (!\is_resource($this->buffer)) {
      throw new \RuntimeException();
    }

    $pos = $this->tell();
    $size = $this->getSize();

    // Calculate the final offset into the stream.
    switch ($whence) {
      case SEEK_CUR:
        $pos += $offset;
        break;

      case SEEK_END:
        $pos = $this->getSize() + $offset;
        break;

      case SEEK_SET:
        $pos = $offset;
        break;
    }

    // Check if the final offset is past EOF.
    if ($pos > $size) {
      // Calculate the number of bytes used to pad the stream.
      $pad_bytes = $pos - $size;

      // Seek to the end of the buffer and pad some NUL bytes to reach $pos.
      if (\fseek($this->buffer, 0, SEEK_END) !== 0 || \fwrite($this->buffer, str_pad("", $pad_bytes, "\0"), $pad_bytes) !== $pad_bytes) {
        throw new \RuntimeException();
      }
    }
    elseif (\fseek($this->buffer, $offset, $whence) !== 0) {
      throw new \RuntimeException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->seek(0);
  }

  /**
   * {@inheritdoc}
   */
  public function isWritable(): bool {
    return TRUE;
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

  /**
   * {@inheritdoc}
   */
  public function isReadable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function read($length): string {
    // Attempt to read from a valid buffer, throw an exception on failure.
    if (!is_resource($this->buffer) || ($string = \fread($this->buffer, $length)) === FALSE) {
      throw new \RuntimeException();
    }

    return $string;
  }

  /**
   * Peek at the next character while temporarily modifying the stream offset.
   *
   * This method will retrieve the current stream offset and store it. Next, an
   * attempt is made to read a single character from the stream. Finally, the
   * stream is reset to its original offset and the character is returned.
   *
   * @throws \RuntimeException
   *   If this method is unable to do any of the following:
   *     1. Fetch the current stream offset.
   *     2. Read a single character.
   *     3. Seek to the original stream offset.
   *
   * @return string
   *   The next character in the string, or an empty string on EOF.
   */
  public function peek(): string {
    $pos = $this->tell();
    $chr = $this->read(1);
    $this->seek($pos);

    return $chr;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): string {
    // Determine the number of remaining bytes in the stream.
    $length = $this->getSize() - $this->tell();

    // Ensure that there are bytes to read before continuing.
    if ($length > 0) {
      // Attempt to read and return the remainder of the buffer.
      return $this->read($length);
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

}
