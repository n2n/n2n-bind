<?php
namespace n2n\bind\mapper\impl\op;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\magic\impl\MagicMethodInvoker;
use n2n\util\magic\MagicContext;

class ValueIfNotExistsMapper extends SingleMapperAdapter {
	function __construct(private \Closure $closure) {
		$this->nonExistingSkipped = false;
	}

	protected function mapSingle(Bindable $bindable, BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		if ($bindable->doesExist()) {
			return true;
		}

		$invoker = new MagicMethodInvoker($magicContext);
		$invoker->setClassParamObject(Bindable::class, $bindable);
		$invoker->setClassParamObject(BindBoundary::class, $bindBoundary);
		$bindable->setExist(true);
		$bindable->setValue( $invoker->invoke(null, $this->closure));

		return true;
	}
}