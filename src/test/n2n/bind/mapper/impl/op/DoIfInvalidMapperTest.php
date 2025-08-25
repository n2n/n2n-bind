<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\validation\lang\ValidationMessages;
use n2n\bind\plan\Bindable;

class DoIfInvalidMapperTest extends TestCase {

    /**
     * @throws BindTargetException
     * @throws UnresolvableBindableException
     * @throws BindMismatchException
     */
    function testAbortIfInvalid() {
        $result = Bind::attrs(['prop' => 'holeradio'])
                ->prop('prop',
                        Mappers::bindableClosure(function (Bindable $bindable) {
							$bindable->addError(ValidationMessages::invalid());
                        }),
                        Mappers::doIfInvalid(abort: true),
                        Mappers::valueClosure(function () {
                            $this->fail('Mapper should not be called.');
                        }))
                ->toArray()->exec();

        $this->assertFalse($result->isValid());
    }

    /**
     * @throws BindTargetException
     * @throws UnresolvableBindableException
     * @throws BindMismatchException
     */
    function testConditionFalseWhenValid() {
        $result = Bind::attrs(['prop' => 'holeradio'])
                ->prop('prop',
                        Mappers::cleanString(true, 1, 255),
                        Mappers::doIfInvalid(abort: true),
                        Mappers::valueClosure(function (string $v) {
                            return $v . '-ok';
                        }))
                ->toArray()->exec();

        $this->assertTrue($result->isValid());
        $this->assertSame(['prop' => 'holeradio-ok'], $result->get());
    }

    /**
     * @throws BindTargetException
     * @throws UnresolvableBindableException
     * @throws BindMismatchException
     */
    function testSkipNextMappersOnInvalid() {
        $result = Bind::attrs(['prop' => 'holeradio'])
                ->prop('prop',
                        Mappers::valueClosure(fn () => null),
                        Mappers::cleanString(true, 1, 255),
                        Mappers::doIfInvalid(skipNextMappers: true),
                        Mappers::valueClosure(function () {
                            $this->fail('Mapper should be skipped');
                        }))
                ->toArray()->exec();

        $this->assertFalse($result->isValid());
    }


	function testChLogicalOnInvalid() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop',
						Mappers::cleanString(true, 1, 5),
						Mappers::doIfInvalid(chLogical: false),
						Mappers::bindableClosure(function (Bindable $bindable) {
							$this->assertFalse($bindable->isLogical());
						}, true, false))
				->logicalProp('prop2',
						Mappers::cleanString(true, 1, 5),
						Mappers::doIfInvalid(chLogical: false)->setDirtySkipped(false),
						Mappers::bindableClosure(function (Bindable $bindable) {
							$this->assertFalse($bindable->isLogical());
						}, true, false))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
		$this->assertCount(1, $result->getErrorMap()->getChild('prop')->getMessages());
		$this->assertCount(1, $result->getErrorMap()->getChild('prop2')->getMessages());
	}
}


