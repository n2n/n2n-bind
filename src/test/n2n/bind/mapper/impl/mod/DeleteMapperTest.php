<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class DeleteMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSubPropWrite(): void {

		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar']]);
		$targetMock = new OuterTargetMock();

		Bind::attrs($dataMap)->toObj($targetMock)
				->prop('holeradio')
				->prop('sub', Mappers::delete(), Mappers::subProps()->prop('huii'))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals('foo', $targetMock->holeradio);
		$this->assertEquals('bar', $targetMock->sub->huii);
	}
}

class OuterTargetMock {

	public ?string $holeradio = null;
	public InnerTargetMock $sub;

	public function __construct() {
		$this->sub = new InnerTargetMock();
	}
}

class InnerTargetMock {

	public ?string $huii = null;
}