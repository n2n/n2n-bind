<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindPlan;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\bind\plan\impl\LogicalBindContext;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;

class SubMergeMapper extends SingleMapperAdapter {

	private BindPlan $bindPlan;

	function __construct() {
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		$bindablesFilter = new BindablesFilter($bindBoundary->unwarpBindSource());

		$path = $bindable->getPath();
		$pathSize = $path->size();

		$merger = new BindableValuesMerger();
		foreach ($bindablesFilter->descendantsOf($path) as $descendantBindable) {
			$merger->merge($descendantBindable->getPath()->slice($pathSize), $descendantBindable->getValue());
			$descendantBindable->setExist(false);
		}

		$bindable->setValue($merger->getValues());
		return true;
	}
}