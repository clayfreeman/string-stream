<?php

declare(strict_types = 1);

namespace ClayFreeman\Stream;

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
        $pos = $this->getSize() + $offset;
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
   *
   * @param int $length
   *   The maximum length of bytes to read (default: 0; all remaining bytes).
   * @param string $delim
   *   Stop reading at the supplied delimiter. Only used if $length is positive.
   */
  public function getContents(int $length = 0, string $delim = ''): string {
    // Check if the user wants all remaining bytes in the stream.
    if ($length <= 0) {
      // Determine the number of remaining bytes in the stream.
      $length = $this->getSize() - $this->tell();

      // Ensure that there are bytes to read before continuing.
      if ($length > 0) {
        // Attempt to read and return the remainder of the buffer.
        return $this->read($length);
      }
    }
    // Check if a delimiter was supplied.
    elseif (\strlen($delim) > 0) {
      return $this->readDelimited($length, $delim, FALSE);
    }
    else {
      return $this->read($length);
    }

    // By default, return an empty string. This point should never be reached.
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
    // Check if the buffer is valid before retrieving its length.
    if (\is_resource($this->buffer) && ($info = \fstat($this->buffer)) !== FALSE) {
      return $info['size'];
    }

    return NULL;
  }

  /**
   * Read & throw away bytes using ::getContents().
   *
   * @param int $length
   *   The maximum length of bytes to read (default: 0; all remaining bytes).
   * @param string $delim
   *   Stop reading at the supplied delimiter. Only used if $length is positive.
   *
   * @see ::getContents()
   *   For more information about how this method works.
   */
  public function ignore(int $length = 0, string $delim = ''): void {
    // Check if the request should use non-delimited functionality.
    if ($length <= 0 || \strlen($delim) === 0) {
      // Defer the request to ::getContents().
      $this->getContents($length, $delim);
    }
    else {
      // Attempt to discard a delimited string (including delimiter).
      $this->readDelimited($length, $delim, TRUE);
    }
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
   * Read up to N bytes or until the supplied delimiter is found.
   *
   * @param int $length
   *   The maximum length of bytes to read. It is the responsibility of the
   *   caller to ensure that this value is greater than zero.
   * @param string $delim
   *   Stop reading at the supplied delimiter. It is the responsibility of the
   *   caller to ensure that this value has a length greater than zero.
   * @param bool $discard
   *   Whether the stream should seek past the supplied delimiter (TRUE) or stop
   *   ahead of it (FALSE).
   *
   * @internal
   *
   * @return string
   *   The bytes read from the stream.
   */
  protected function readDelimited(int $length, string $delim, bool $discard): string {
    $result = '';

    if (\is_resource($this->buffer)) {
      $pos = $this->tell();

      // Read up to $length characters, or until $delim is found.
      if (($str = \stream_get_line($this->buffer, $length, $delim)) !== FALSE) {
        $result = $str;
      }

      // Check whether the delimiter shouldn't be discarded.
      if (!$discard) {
        $this->seek($pos + \strlen($result));
      }
    }

    return $result;
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
  public function seek($offset, $whence = \SEEK_SET): void {
    $pos = $this->calculateSeekPosition($offset, $whence);
    $size = $this->getSize();

    // Check if the final offset is past EOF.
    if ($pos > $size) {
      // Calculate the number of bytes used to pad the stream.
      $pad_bytes = $pos - $size;

      // Seek to the end of the buffer to write the padding bytes.
      if (!\is_resource($this->buffer) || \fseek($this->buffer, 0, \SEEK_END) !== 0) {
        throw new \RuntimeException();
      }

      // Write some NUL bytes to reach $pos.
      $this->write(\str_pad('', $pad_bytes, "\0"));
    }
    elseif (!\is_resource($this->buffer) || \fseek($this->buffer, $offset, $whence) !== 0) {
      throw new \RuntimeException();
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
