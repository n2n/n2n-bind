<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\MapResult;
use n2n\bind\plan\BindBoundary;
use n2n\util\type\TypeConstraints;
use Traversable;
use n2n\bind\mapper\Mapper;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\mapper\impl\PipeMapper;

class SubForeachMapper extends SingleMapperAdapter {

	/**
	 * @param Mapper[] $mappers
	 */
	function __construct(private array $mappers) {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult|bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::type(['array', Traversable::class]));

		$bindContext = new BindableBindContext($bindable, $bindBoundary->unwarpBindInstance());
		foreach ($value as $fieldKey => $fieldValue) {
			$bindBoundary = new BindBoundary($bindContext, []);
			$bindBoundary->acquireBindableByRelativeName($fieldKey);
			$result = (new PipeMapper($this->mappers))->map($bindBoundary, $magicContext);
			if (!$result->isOk()) {
				return $result;
			}
		}
		return new MapResult();
	}
}