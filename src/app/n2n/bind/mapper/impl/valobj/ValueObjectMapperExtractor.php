<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\reflection\ReflectionContext;
use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\Mapper;
use n2n\util\magic\MagicContext;
use n2n\util\col\ArrayUtils;
use n2n\reflection\attribute\MethodAttribute;
use n2n\reflection\magic\MagicMethodInvoker;
use n2n\bind\mapper\impl\valobj\err\ValueObjectError;
use n2n\util\type\TypeUtils;
use n2n\util\type\TypeConstraints;
use n2n\bind\attribute\impl\Unmarshal;
use n2n\bind\mapper\impl\valobj\err\ValueObjectMapperCorruptedException;
use n2n\bind\mapper\impl\valobj\err\ValueObjectMapperExtractionException;

class ValueObjectMapperExtractor {

	function __construct(private \ReflectionClass $class) {
	}

	/**
	 * @throws ValueObjectMapperExtractionException
	 */
	function extractMarshalMapper(): Mapper {
		return $this->extractMapper(Marshal::class);
	}

	/**
	 * @throws ValueObjectMapperExtractionException
	 */
	function extractUnmarshalMapper(): Mapper {
		return $this->extractMapper(Unmarshal::class);
	}

	/**
	 * @throws ValueObjectMapperExtractionException
	 */
	private function extractMapper(string $attributeName): Mapper {
		$methodAttributes = ReflectionContext::getAttributeSet($this->class)
				->getMethodAttributesByName($attributeName);

		if (empty($methodAttributes)) {
			throw new ValueObjectMapperExtractionException('No method annotated with ' . $attributeName
					. ' found in ' . $this->class->getName());
		}

		$methodAttribute = ArrayUtils::first($methodAttributes);
		assert($methodAttribute instanceof MethodAttribute);

		$method = $methodAttribute->getMethod();
		if (!$method->isStatic()) {
			throw new ValueObjectError('Method annotated with ' . $attributeName
							. ' must be static: ' . TypeUtils::prettyReflMethName($method),
					$method->getFileName(), $method->getStartLine());
		}

		$invoker = new MagicMethodInvoker();
		$invoker->setMethod($method);
		$invoker->setReturnTypeConstraint(TypeConstraints::namedType(Mapper::class));
		return $invoker->invoke();
	}
}

class MapperExtractor {

	function __construct(private \ReflectionMethod $method) {
	}

	function execute(MagicContext $magicContext): Mapper {
		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setMethod($this->method);
		$invoker->setReturnTypeConstraint(TypeConstraints::namedType(Mapper::class));
		return $invoker->invoke();
	}
}