<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\plan\BindBoundary;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\impl\MapBindStep;
use n2n\bind\plan\impl\BindableBindContext;
use n2n\bind\mapper\MapResult;
use n2n\bind\build\impl\compose\prop\PropBindComposerTrait;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class SubPropsMapper extends SingleMapperAdapter {
	use PropBindComposerTrait {
		PropBindComposerTrait::__construct as private __propBindComposerTraitConstruct;
	}

	function __construct() {
		$this->__propBindComposerTraitConstruct(new MapBindStep());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): MapResult|bool {
		$bindContext = new BindableBindContext($bindable, $bindBoundary->unwarpBindInstance(),
				$bindBoundary->getBindContext()->unwrapBindTargetInstance());

		return $this->bindPlan->exec($bindContext, $magicContext)->ok;
	}
}