<?php

declare(strict_types = 1);

namespace ClayFreeman\Stream;

/**
 * Provides a trait to enable cloning for PSR-7 streams.
 *
 * @license http://mozilla.org/MPL/2.0/ MPL-2.0
 * This file is subject to the terms of the Mozilla Public License, v2.0. If a
 * copy of the MPL was not distributed with this file, You can obtain one at the
 * above URI.
 */
trait CloneableStreamTrait {

  /**
   * Clones the internal state of this object.
   */
  public function __clone() {
    $pos = $this->tell();
    $this->__construct((string) $this);
    $this->seek($pos);
  }

}
