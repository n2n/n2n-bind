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

class SubPropsFromClassMapper extends MapperAdapter {


	function __construct(\ReflectionClass $class) {

	}

	/**
	 * @throws InaccessiblePropertyException
	 * @throws InvalidPropertyAccessMethodException
	 */
	private function getBindGroup(): BindGroup {


		$bindPlan = new BindPlan();
		$analyzer = new PropertiesAnalyzer($this->class);
		$composer = new PropBindComposer(new BindPlan());

		foreach ($analyzer->analyzeProperties(true) as $propertyAccessProxy) {
			if (!$propertyAccessProxy->isWritable()) {
				continue;
			}

			$bindPlan->addBindGroup(new BindGroup());
			$propertyAccessProxy->getSetterConstraint()->getNamedTypeConstraints()
		}

		$bindPlan->addBindGroup(new BindGroup());
	}

	private function determineMappersFor(TypeConstraint $typeConstraint) {
		$mappers = [];
		$mustExist = true;
		foreach ($typeConstraint->getNamedTypeConstraints() as $namedTypeConstraint) {
			$typeName = $namedTypeConstraint->getTypeName();
			if ($namedTypeConstraint->isMixed() || TypeName::isScalar($namedTypeConstraint->getTypeName())) {
				return [Mappers::type($typeConstraint)];
			}

			if ($typeName === Undefined::class) {
				Mappers::bindableClosure(function (Bindable $b) {
					if ($b->doesExist()) {
						return;
					}

					$b->setValue(Undefined::i());
					$b->setExist(true);
				});

				$mappers[] = Mappers::doIfValueClosure(fn ($v) => $v === Undefined::i(), skipNextMappers: true);
				$mustExist = false;

			}


			if (class_exists($typeName)) {

			}



		}
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {


		foreach ($bindBoundary->getBindables() as $bindable) {
			$bindContext = new BindableBindContext($bindable, $bindBoundary->unwarpBindInstance());

			if (!$this->bindPlan->exec($bindContext, $magicContext)) {
				return new MapResult(false);
			}
		}

		return new MapResult(true);
	}
}