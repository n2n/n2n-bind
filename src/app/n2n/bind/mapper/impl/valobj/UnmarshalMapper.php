<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\bind\mapper\Mapper;
use n2n\reflection\ReflectionUtils;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\util\type\TypeUtils;
use n2n\bind\mapper\impl\valobj\err\ValueObjectMapperCorruptedException;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\reflection\ReflectionRuntimeException;
use n2n\bind\mapper\impl\valobj\err\ValueObjectMapperExtractionException;

class UnmarshalMapper extends MapperAdapter {

	private \ReflectionClass $class;
	private Mapper $mapper;

	function __construct(string $valueObjectClassName) {
		$this->class = ReflectionRuntimeException::try(fn () => new \ReflectionClass($valueObjectClassName));
		try {
			$this->mapper = (new ValueObjectMapperExtractor($this->class))->extractUnmarshalMapper();
		} catch (ValueObjectMapperExtractionException $e) {
			throw new \InvalidArgumentException('UnmarshalMapper of ' . $valueObjectClassName
					. ' could not be extracted. Reason: ' . $e->getMessage(), previous: $e);
		}
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		if (!$this->mapper->map($bindBoundary, $magicContext)) {
			return false;
		}

		foreach ($bindBoundary->getBindables() as $bindable) {
			$value = $bindable->getValue();
			if (!$bindable->doesExist() || $bindable->isDirty()
					|| $value === null ||  TypeUtils::isValueA($value, $this->class)) {
				continue;
			}

			throw new ValueObjectMapperCorruptedException('Unmarshal Mapper of ' . $this->class->getName()
					. ' must map the input value to an object of this type or null. Bindable '
					. (string) $bindable->getPath() . ' was filled with a value of type: '
					. TypeUtils::getTypeInfo($value));
		}

		return true;
	}

}