<?php

namespace n2n\bind\build\impl\target;

use n2n\bind\build\impl\Bind;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class ClosureBindTargetTest extends TestCase {

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testBasics(): void {
		Bind::values('holeradio')
				->toClosure(function ($value) {
					$this->assertSame('holeradio', $value);
				})
				->exec($this->createMock(MagicContext::class));


	}
}