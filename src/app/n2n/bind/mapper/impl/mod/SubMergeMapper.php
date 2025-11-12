<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\impl\MapBindStep;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;

class SubMergeMapper extends SingleMapperAdapter {

	private MapBindStep $bindPlan;

	function __construct() {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$bindablesFilter = new BindablesFilter($bindBoundary->unwarpBindInstance());

		$path = $bindable->getPath();
		$pathSize = $path->size();

		$descendantBindables = $bindablesFilter->descendantsOf($path);
		foreach ($descendantBindables as $descendantBindable) {
			if ($descendantBindable->isDirty() && $this->dirtySkipped) {
				return false;
			}
		}

		$merger = new BindableValuesMerger();
		foreach ($descendantBindables as $descendantBindable) {
			if (!$descendantBindable->doesExist() || $descendantBindable->isLogical()) {
				continue;
			}

			$merger->merge($descendantBindable->getPath()->slice($pathSize), $descendantBindable->getValue());
			$descendantBindable->setExist(false);
		}

		$bindable->setValue($merger->getValues());
		return true;
	}
}