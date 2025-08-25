<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindPlan;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\bind\mapper\MapResult;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\bind\plan\BindGroup;
use n2n\reflection\property\InaccessiblePropertyException;
use n2n\reflection\property\InvalidPropertyAccessMethodException;
use n2n\util\type\NamedTypeConstraint;
use n2n\util\type\TypeConstraint;
use n2n\util\type\TypeName;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\type\custom\Undefined;
use n2n\util\col\Map;
use n2n\bind\plan\Bindable;
use n2n\bind\mapper\impl\valobj\ValueObjectMapperExtractor;
use ReflectionClass;
use n2n\util\type\TypeUtils;
use n2n\util\calendar\Date;
use DateTime;
use n2n\util\calendar\Time;
use n2n\util\uri\Url;
use n2n\validation\validator\impl\Validators;
use n2n\bind\mapper\impl\ValidatorMapper;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\util\ex\ExUtils;
use n2n\bind\err\MisconfiguredMapperException;
use n2n\util\EnumUtils;
use n2n\util\type\mock\PureEnumMock;
use n2n\util\type\TypeConstraints;
use n2n\util\type\UnionTypeConstraint;

class SubPropsFromClassMapper extends MapperAdapter {
	private ?SubPropsMapper $subPropsMapper = null;

	function __construct(private \ReflectionClass $class, private bool $undefinedAsOptional = true) {
	}

	private function getSubPropsMapper(): SubPropsMapper {
		if ($this->subPropsMapper !== null) {
			return $this->subPropsMapper;
		}

		$this->subPropsMapper = new SubPropsMapper();
		(new SubPropsFromClassMappersResolver($this->class, $this->undefinedAsOptional))
				->populateComposer($this->subPropsMapper);
		return $this->subPropsMapper;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		return $this->getSubPropsMapper()->map($bindBoundary, $magicContext);
	}
}

class SubPropsFromClassMappersResolver {

	function __construct(private \ReflectionClass $class, private bool $undefinedAsOptional = true) {

	}

	function populateComposer(PropBindComposer $composer): void {
		$analyzer = new PropertiesAnalyzer($this->class, superIgnored: false);
		$propertyAccessProxies = ExUtils::try(fn () => $analyzer->analyzeProperties(true));

		foreach ($propertyAccessProxies as $propertyAccessProxy) {
			if (!$propertyAccessProxy->isReadable()) {
				continue;
			}

			$composer->optProp($propertyAccessProxy->getPropertyName(),
					...$this->createMappersForProperty($propertyAccessProxy));
		}
	}

	private function createMappersForProperty(PropertyAccessProxy $propertyAccessProxy): array {
		$typeConstraint = $propertyAccessProxy->getSetterConstraint();
		if (!$this->undefinedAsOptional) {
			return [Mappers::type($typeConstraint)];
		}

		$validNamedTypeConstraints = [];
		foreach ($typeConstraint->getNamedTypeConstraints() as $namedTypeConstraint) {
			if ($namedTypeConstraint->getTypeName() !== Undefined::class) {
				$validNamedTypeConstraints[] = $namedTypeConstraint;
			}
		}

		if (empty($validNamedTypeConstraints)) {
			throw new MisconfiguredMapperException(self::class . ' does not support property with only type '
					. Undefined::class . ' as long as undefinedAsOptional flag is set to true: '
					. TypeUtils::prettyReflPropName($propertyAccessProxy->getProperty()));
		}

		return [Mappers::type(new UnionTypeConstraint($validNamedTypeConstraints))];
	}
}