<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\Bindable;
use n2n\validation\lang\ValidationMessages;

/**
 * Tests {@link AbortIfMapper} (Mappers::abortIfInvalid / abortIfDirty).
 */
class AbortIfMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsAbortIfInvalid(): void {
		$result = Bind::attrs(['prop' => 'holeradio'])->prop('prop',
				Mappers::bindableClosure(function (Bindable $b) {
					$b->addError(ValidationMessages::invalid());
				}),
				Mappers::abortIfInvalid(),
				Mappers::value(function () { /* never reached */ }))
				->toArray()->exec();
		var_dump($result->isValid()); // false

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsAbortIfDirty(): void {
		$result = Bind::attrs(['prop' => 'holeradio'])->prop('prop',
				Mappers::bindableClosure(fn (Bindable $b) => $b->setDirty(true)),
				Mappers::abortIfDirty(),
				Mappers::value(function () { /* never reach */ }))
				->toArray()->exec();
		var_dump($result->isValid()); // false

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsAbortIfInvalidPassesWhenValid(): void {
		$result = Bind::attrs(['prop' => 'holeradio'])->prop('prop',
				Mappers::value(fn (string $v) => $v . '-ok'),
				Mappers::abortIfInvalid())
				->toArray()->exec();
		var_dump($result->get());

		$this->assertTrue($result->isValid());
		$this->assertSame(['prop' => 'holeradio-ok'], $result->get());
	}
}