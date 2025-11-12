<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\bind\mapper\impl\MapperAdapter;
use n2n\reflection\property\PropertiesAnalyzer;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\type\custom\Undefined;
use n2n\util\type\TypeUtils;
use n2n\reflection\property\PropertyAccessProxy;
use n2n\util\ex\ExUtils;
use n2n\bind\err\MisconfiguredMapperException;
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

	function populateComposer(SubPropsMapper $composer): void {
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
			return [Mappers::mustExistIf(true), Mappers::type($typeConstraint)];
		}

		$undefinable = false;
		$validNamedTypeConstraints = [];
		foreach ($typeConstraint->getNamedTypeConstraints() as $namedTypeConstraint) {
			if ($namedTypeConstraint->getTypeName() !== Undefined::class) {
				$validNamedTypeConstraints[] = $namedTypeConstraint;
				$undefinable = true;
				continue;
			}
		}

		if (empty($validNamedTypeConstraints)) {
			throw new MisconfiguredMapperException(self::class . ' does not support property with only type '
					. Undefined::class . ' as long as undefinedAsOptional flag is set to true: '
					. TypeUtils::prettyReflPropName($propertyAccessProxy->getProperty()));
		}

		return [Mappers::mustExistIf(!$undefinable), Mappers::type(new UnionTypeConstraint($validNamedTypeConstraints))];
	}
}