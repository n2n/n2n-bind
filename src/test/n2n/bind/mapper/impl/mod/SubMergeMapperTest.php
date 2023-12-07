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

class SubMergeMapperTest extends TestCase {

	/**
	 * @throws \Throwable
	 */
	function testMerge(): void {
		$dataMap = new DataMap([
			'sub' => [
				'huii' => 'bar',
				'sub2' => [
					'huii1' => 'foobar',
					'huii2' => 'foobar2'
				]
			]
		]);
		$targetObj = new MergedValuesObjMock();

		Bind::attrs($dataMap)->toObj($targetObj)
				->props(['sub/huii', 'sub/sub2/huii1', 'sub/sub2/huii2'],
						Mappers::valueClosure(fn (string $v) => $v . '-mapped'))
				->prop('sub', Mappers::subMerge())
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(
				['huii' => 'bar-mapped', 'sub2' => ['huii1' => 'foobar-mapped', 'huii2' => 'foobar2-mapped']],
				$targetObj->sub);
	}
}

class MergedValuesObjMock {
	public array $sub;
}
