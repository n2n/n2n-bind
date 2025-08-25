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
use n2n\validation\lang\ValidationMessages;
use n2n\validation\plan\Validatable;

class SubPropsForClassMapper extends MapperAdapter {
	private ?SubPropsMapper $subPropsMapper = null;

	function __construct(private \ReflectionClass $class, private bool $undefinedAutoFilled = true) {
	}

	private function getSubPropsMapper(): SubPropsMapper {
		if ($this->subPropsMapper !== null) {
			return $this->subPropsMapper;
		}

		$this->subPropsMapper = new SubPropsMapper();
		(new SubPropsForClassMappersResolver($this->class, $this->undefinedAutoFilled))
				->populateComposer($this->subPropsMapper);
		return $this->subPropsMapper;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		return $this->getSubPropsMapper()->map($bindBoundary, $magicContext);
	}
}

class SubPropsForClassMappersResolver {

	function __construct(private \ReflectionClass $class, private bool $undefinedAutoFilled = true) {

	}

	function populateComposer(PropBindComposer $composer): void {
		$analyzer = new PropertiesAnalyzer($this->class, superIgnored: false);
		$propertyAccessProxies = ExUtils::try(fn () => $analyzer->analyzeProperties(true));

		foreach ($propertyAccessProxies as $propertyAccessProxy) {
			if (!$propertyAccessProxy->isWritable()) {
				continue;
			}

			$composer->optProp($propertyAccessProxy->getPropertyName(),
					...$this->createMappersForProperty($propertyAccessProxy));
		}
	}

	private function createMappersForProperty(PropertyAccessProxy $propertyAccessProxy): array {
		$nullable = false;
		$undefinable = false;
		$valueMappers = null;

		$typeConstraint = $propertyAccessProxy->getSetterConstraint();
		foreach ($typeConstraint->getNamedTypeConstraints() as $namedTypeConstraint) {
			$typeName = $namedTypeConstraint->getTypeName();
			if ($namedTypeConstraint->allowsNull()) {
				$nullable = true;
			}

			if ($typeName === TypeName::NULL) {
				continue;
			}

			if ($this->undefinedAutoFilled && $typeName === Undefined::class) {
				$undefinable = true;
				continue;
			}

			if ($valueMappers !== null) {
				throw new MisconfiguredMapperException(SubPropsForClassMapper::class
						. ' does not support properties with multiple types: '
						. TypeUtils::prettyClassPropName($this->class, $propertyAccessProxy->getPropertyName()));
			}

			if ($namedTypeConstraint->isMixed() || TypeName::isScalar($namedTypeConstraint->getTypeName())) {
				$valueMappers = [Mappers::typeNotNull($namedTypeConstraint)];
				continue;
			}

			if (EnumUtils::isEnumType($typeName)) {
				$valueMappers = [Mappers::enum($typeName)];
				continue;
			}

			$valueMappers = $this->detectKnownTypesMappers($typeName);
			if ($valueMappers !== null) {
				continue;
			}

			$valueMappers = $this->detectValObjMappers($typeName);
			if ($valueMappers !== null) {
				continue;
			}

			$valueMappers = [Mappers::typeNotNull($namedTypeConstraint)];
		}

		return $this->compilePropertyMappers($valueMappers ?? [Mappers::type($typeConstraint)],
				$undefinable, $nullable);
	}

	private function compilePropertyMappers(array $valueMappers, bool $undefinable, bool $nullable): array {
		$mappers = [];
		if (!$undefinable) {
			$mappers[] = Mappers::mustExistIf(true);
		} else {
			$mappers[] = Mappers::bindableClosure(
					function (Bindable $bindable) {
						if (!$bindable->doesExist()) {
							$bindable->setValue(Undefined::i())->setExist(true);
						}
					},
					nonExistingSkipped: false);
			$mappers[] = Mappers::doIfValueClosure(fn ($v) => $v === Undefined::i(), skipNextMappers: true);
		}

		array_push($mappers, ...$valueMappers);

		if (!$nullable) {
			$mappers[] = new ValidatorMapper(Validators::valueClosure(fn ($v, Validatable $validatable)
					=> ($v !== null ? null : ValidationMessages::mandatory($validatable->getLabel()))));
		}

		return $mappers;
	}

	private function detectKnownTypesMappers(string $typeName): ?array {
		return match ($typeName) {
			\DateTimeInterface::class, \DateTimeImmutable::class => [Mappers::dateTimeImmutable()],
			\DateTime::class => [Mappers::dateTime()],
			Date::class => [Mappers::date()],
			Time::class => [Mappers::time()],
			Url::class => [Mappers::url()],
			default => null,
		};
	}

	private function detectValObjMappers(string $typeName): ?array {
		if (!class_exists($typeName)) {
			return null;
		}

		$typeClass = new \ReflectionClass($typeName);
		if (ValueObjectMapperExtractor::isUnmarshalable($typeClass)) {
			return [Mappers::unmarshal($typeName)];
		}

		return null;
	}


}