<?php
namespace n2n\bind\mapper\impl\compose;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;

class SubForeachMapperTest extends TestCase {
	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSimple(): void {
		$dataMap = new DataMap(['huii' => [ 'key1' => 'value1', 'key2' => 'value2']]);
		$result = Bind::attrs($dataMap)
				->prop('huii', Mappers::subForeach(Mappers::valueClosure(fn (string $v) => $v . '-m')))
				->toAttrs(new DataMap())
				->exec();

		$this->assertSame(['huii' => [ 'key1' => 'value1-m', 'key2' => 'value2-m']], $result->get()->toArray());
	}
}