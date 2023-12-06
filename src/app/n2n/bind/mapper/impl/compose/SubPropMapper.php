<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindPlan;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\build\impl\compose\prop\PropBindComposer;
use n2n\bind\plan\impl\LogicalBindContext;

class SubPropMapper extends PropBindComposer implements Mapper {

	private BindPlan $bindPlan;

	function __construct() {
		parent::__construct($this->bindPlan = new BindPlan());
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		foreach ($bindBoundary->getPaths() as $path) {
			if (null !== ($bindable = $bindBoundary->getBindable($path))) {
				$bindContext = new BindableBindContext($bindable);
			} else {
				$bindContext = new LogicalBindContext($path, $bindBoundary->unwrapBindContext());
			}

			if (!$this->bindPlan->exec($bindBoundary->unwarpBindSource(), $bindContext, $magicContext)) {
				return false;
			}
		}

		return true;
	}
}