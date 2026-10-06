<?php
namespace n2n\bind\mapper\impl\compose;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
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

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSeparatedBindGroups(): void {
		$dataMap = new DataMap(['huii' => [ 'key1' => 'value1', 'key2' => 'value2']]);
		$result = Bind::attrs($dataMap)
				->prop('huii', Mappers::subForeach(Mappers::closure(function (array $bindables) {
					$this->assertCount(1, $bindables);
					$bindable = current($bindables);
					$bindable->setValue($bindable->getValue() . '-m');
				})))
				->toAttrs(new DataMap())
				->exec();

		$this->assertSame(['huii' => [ 'key1' => 'value1-m', 'key2' => 'value2-m']], $result->get()->toArray());
	}

	function testDocsUsage(): void {
		$dataMap = new DataMap(['huii' => ['key1' => 'value1', 'key2' => 'value2']]);
		$result = Bind::attrs($dataMap)
				->prop('huii', Mappers::subForeach(Mappers::value(fn (string $v) => $v . '-m')))
				->toAttrs(new DataMap())
				->exec();

		$this->assertTrue($result->isValid());
		$this->assertSame(['huii' => ['key1' => 'value1-m', 'key2' => 'value2-m']],
				$result->get()->toArray());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['emails' => ['bad1', 'bad2']])
				->prop('emails', Mappers::subForeach(Mappers::email()))
				->toArray()
				->exec();

		// result is invalid with an error for each array element.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('emails')->isEmpty()); // false
		var_dump($result->getErrorMap()->getChild('emails')->getChild(0)->isEmpty()); // false
		var_dump($result->getErrorMap()->getChild('emails')->getChild(1)->isEmpty()); // false

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('emails')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('emails')->getChild(0)->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('emails')->getChild(1)->isEmpty());
	}
}