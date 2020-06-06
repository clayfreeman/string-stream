<?php

declare(strict_types = 1);

use ClayFreeman\StringStream;

use PHPUnit\Framework\TestCase;

/**
 * @covers \ClayFreeman\StringStream
 *
 * @license http://mozilla.org/MPL/2.0/ MPL-2.0
 * This file is subject to the terms of the Mozilla Public License, v2.0. If a
 * copy of the MPL was not distributed with this file, You can obtain one at the
 * above URI.
 */
final class StringStreamTest extends TestCase {

  /**
   * @covers \ClayFreeman\StringStream::__construct()
   */
  public function testConstruct(): void {
    $stream = new StringStream($input = 'sample');

    $this->assertInstanceOf(StringStream::class, $stream);
    $this->assertSame(0, $stream->tell());

    $this->assertIsResource($fh = $stream->detach());
    $this->assertSame(0, ftell($fh));

    $this->assertSame(strlen($input), fstat($fh)['size']);
    $this->assertSame($input, fread($fh, strlen($input)));

    $this->expectException(\RuntimeException::class);
    $stream->tell();
  }

  /**
   * @covers \ClayFreeman\StringStream::__toString()
   */
  public function testToString(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame($input, (string) $stream);

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $test = (string) $stream;
  }

  /**
   * @covers \ClayFreeman\StringStream::close()
   */
  public function testClose(): void {
    $stream = new StringStream($input = 'sample');
    $stream->close();

    $this->expectException(\RuntimeException::class);
    $test = (string) $stream;
  }

  /**
   * @covers \ClayFreeman\StringStream::detach()
   */
  public function testDetach(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertIsResource($fh = $stream->detach());
    $this->assertNull($stream->detach());

    $this->expectException(\RuntimeException::class);
    $test = (string) $stream;
  }

  /**
   * @covers \ClayFreeman\StringStream::getSize()
   */
  public function testGetSize(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame(strlen($input), $stream->getSize());

    $stream->close();
    $this->assertNull($stream->getSize());
  }

  /**
   * @covers \ClayFreeman\StringStream::tell()
   */
  public function testTell(): void {
    $stream = new StringStream($input = 'sample');

    $this->assertSame(0, $stream->tell());
    $this->assertSame(substr($input, 0, $length = 3), $stream->read($length));
    $this->assertSame($length, $stream->tell());

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $test = $stream->tell();
  }

  /**
   * @covers \ClayFreeman\StringStream::eof()
   */
  public function testEof(): void {
    $stream = new StringStream($input = 'sample');

    $this->assertSame(FALSE, $stream->eof());
    $this->assertSame($input, (string) $stream);
    $this->assertSame(FALSE, $stream->eof());
    $this->assertSame('', $stream->read(1));
    $this->assertSame(TRUE, $stream->eof());

    $stream->close();
    $this->assertSame(FALSE, $stream->eof());
  }

  /**
   * @covers \ClayFreeman\StringStream::seek()
   */
  public function testSeek(): void {
    $stream = new StringStream($input = 'sample');

    $stream->seek($offset1 = 3, SEEK_SET);
    $this->assertSame($offset1, $stream->tell());

    $stream->seek($offset2 = -1, SEEK_CUR);
    $this->assertSame($offset1 + $offset2, $stream->tell());

    $stream->seek($offset3 = -2, SEEK_END);
    $this->assertSame($stream->getSize() + $offset3, $stream->tell());

    $this->expectException(\RuntimeException::class);
    $stream->seek($offset4 = -2, SEEK_SET);

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $stream->seek($offset5 = 1, SEEK_SET);
  }

  /**
   * @covers \ClayFreeman\StringStream::rewind()
   */
  public function testRewind(): void {
    $stream = new StringStream($input = 'sample');

    $stream->seek($offset = 3, SEEK_SET);
    $this->assertSame($offset, $stream->tell());

    $stream->rewind();
    $this->assertSame(0, $stream->tell());
  }

  /**
   * @covers \ClayFreeman\StringStream::write()
   */
  public function testWrite(): void {
    $stream = new StringStream($input = 'sample');

    $stream->write($input);
    $this->assertSame($input, (string) $stream);

    $stream->write($input);
    $this->assertSame($input . $input, (string) $stream);

    $stream->seek(0);
    $stream->write('junk');
    $this->assertSame('junklesample', (string) $stream);

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $stream->write('junk');
  }

  /**
   * @covers \ClayFreeman\StringStream::read()
   */
  public function testRead(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame($input, $stream->read(strlen($input)));
    $this->assertSame('', $stream->read(strlen($input)));

    $stream->seek(1);
    $this->assertSame(substr($input, 1), $stream->read(strlen($input)));
    $this->assertSame('', $stream->read(strlen($input)));

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $stream->read(1);
  }

  /**
   * @covers \ClayFreeman\StringStream::getContents()
   */
  public function testGetContents(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame($input, $stream->getContents());
    $this->assertSame('', $stream->getContents());

    $stream->seek(-3, SEEK_END);
    $this->assertSame(substr($input, -3), $stream->getContents());
  }

  /**
   * @covers \ClayFreeman\StringStream::getContents()
   */
  public function testGetMetadata(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertIsArray($stream->getMetadata());
    $this->assertEmpty($stream->getMetadata());
    $this->assertNull($stream->getMetadata('test'));
  }

  /**
   * @covers \ClayFreeman\StringStream::isReadable()
   * @covers \ClayFreeman\StringStream::isSeekable()
   * @covers \ClayFreeman\StringStream::isWritable()
   */
  public function testFeatureFlags(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame(FALSE, $stream->isReadable());
    $this->assertSame(TRUE, $stream->isSeekable());
    $this->assertSame(TRUE, $stream->isWritable());
  }

}
