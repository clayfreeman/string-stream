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
    $this->assertSame($input, $stream->read($stream->getSize() + 1));
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

    $stream->seek($offset4 = $stream->getSize() + 100, SEEK_SET);
    $this->assertSame($offset4, $stream->getSize());
    $this->assertSame($offset4, $stream->tell());
    $this->assertSame(str_pad($input, $offset4, "\0"), (string) $stream);

    $this->expectException(\RuntimeException::class);
    $stream->seek($offset5 = -2, SEEK_SET);

    $stream->close();
    $this->expectException(\RuntimeException::class);
    $stream->seek($offset6 = 1, SEEK_SET);
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
    $stream->write($tmp = 'junk');
    $this->assertSame($tmp . substr($input . $input, strlen($tmp)), (string) $stream);

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
   * @covers \ClayFreeman\StringStream::peek()
   */
  public function testPeek(): void {
    $offset = 0;
    $stream = new StringStream($input = 'sample');

    $this->assertSame(substr($input, $offset, 1), $stream->peek());
    $this->assertSame($offset, $stream->tell());
    $this->assertSame(substr($input, $offset, 1), $stream->peek());
    $this->assertSame($offset, $stream->tell());
    $this->assertSame(substr($input, $offset, 1), $chr = $stream->read(1)); $offset += strlen($chr);
    $this->assertSame($offset, $stream->tell());
    $this->assertSame(substr($input, $offset, 1), $stream->peek());
    $this->assertSame($offset, $stream->tell());

    $stream->getContents();
    $this->assertFalse($stream->eof());
    $this->assertSame('', $stream->peek());
    $this->assertTrue($stream->eof());
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

    $stream->rewind();
    $this->assertSame($expected = 'sam', $stream->getContents($stream->getSize(), 'p'));
    $this->assertSame(strlen($expected), $stream->tell());
    $stream->rewind();
    $this->assertSame($expected = 's', $stream->getContents(1, 'p'));
    $this->assertSame(strlen($expected), $stream->tell());
    $stream->rewind();
    $this->assertSame($expected = 'sample', $stream->getContents($stream->getSize(), 'z'));
    $this->assertSame(strlen($expected), $stream->tell());
    $this->assertSame($expected = '', $stream->getContents($stream->getSize(), 'z'));
    $this->assertSame($stream->getSize(), $stream->tell());
    $stream->rewind();
    $this->assertSame($expected = 'sample', $stream->getContents($stream->getSize()));
    $this->assertSame(strlen($expected), $stream->tell());
    $stream->rewind();
    $this->assertSame($expected = 'sample', $stream->getContents());
    $this->assertSame(strlen($expected), $stream->tell());
    $stream->rewind();
    $this->assertSame($expected = 'sample', $stream->getContents(-1));
    $this->assertSame(strlen($expected), $stream->tell());
  }

  /**
   * @covers \ClayFreeman\StringStream::ignore()
   */
  public function testIgnore(): void {
    $stream = new StringStream($input = 'sample');

    $stream->ignore();
    $this->assertSame(strlen($input), $stream->tell());

    $stream->rewind();
    $stream->ignore($max = 3);
    $this->assertSame($max, $stream->tell());

    $stream->rewind();
    $stream->ignore(0, 'p');
    $this->assertSame(strlen($input), $stream->tell());

    $stream->rewind();
    $stream->ignore($max = strlen($input), $delim = 'p');
    $this->assertSame(strpos($input, $delim) + strlen($delim), $stream->tell());
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
    $this->assertSame(TRUE, $stream->isReadable());
    $this->assertSame(TRUE, $stream->isSeekable());
    $this->assertSame(TRUE, $stream->isWritable());
  }

  /**
   * @covers \ClayFreeman\StringStream::serialize()
   * @covers \ClayFreeman\StringStream::unserialize()
   */
  public function testSerialization(): void {
    $stream = new StringStream($input = 'sample');
    $this->assertSame($input, (string) unserialize(serialize($stream)));

    $stream->seek($pos = intval(strlen($input) / 2));
    $this->assertSame($pos, $stream->tell());
    $this->assertSame($pos, unserialize(serialize($stream))->tell());
  }

  /**
   * @covers \ClayFreeman\StringStream::__clone()
   */
  public function testClone(): void {
    $stream = new StringStream($input = 'sample');
    $stream2 = clone $stream;

    $this->assertSame((string) $stream, (string) $stream2);

    $stream2->rewind();
    $stream2->write($write = 'junk');
    $this->assertSame($write . substr($input, strlen($write)), (string) $stream2);
    $this->assertSame($input, (string) $stream);

    $this->assertNotEquals((string) $stream, (string) $stream2);

    $stream2->seek($pos = intval(strlen((string) $stream2) / 2));
    $this->assertSame($pos, (clone $stream2)->tell());
  }

}
