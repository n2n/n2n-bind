<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;
use n2n\reflection\ReflectionUtils;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\util\type\TypeConstraints;
use n2n\reflection\ReflectionRuntimeException;
use n2n\bind\mapper\impl\valobj\err\ValueObjectMapperExtractionException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\MapResult;

class MarshalMapper extends SingleMapperAdapter {

	/**
	 * @var Mapper[]
	 */
	private array $mappers = [];

	function __construct() {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		$value = $this->readSafeValue($bindable, TypeConstraints::namedType('object', true));

		if ($value === null) {
			return new MapResult(true);
		}

		return $this->deterMapper($value)->map(
				new BindBoundary($bindBoundary->getBindContext(), [$bindable]),
				$magicContext);
	}

	/**
	 * @throws BindMismatchException
	 */
	private function deterMapper(object $value): Mapper {
		$class = ReflectionRuntimeException::try(fn () => new \ReflectionClass($value));
		$className = $class->getName();

		if (isset($this->mappers[$className])) {
			return $this->mappers[$className];
		}

		try {
			return $this->mappers[$className] = (new ValueObjectMapperExtractor($class))->extractMarshalMapper();
		} catch (ValueObjectMapperExtractionException $e) {
			throw new BindMismatchException('Object of type ' . $class->getName()
							. ' could not be marshalled. Reason: ' . $e->getMessage(),
					previous: $e);
		}
	}
}