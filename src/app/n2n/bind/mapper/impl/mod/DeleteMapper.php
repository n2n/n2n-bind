<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\bind\plan\BindContext;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;

class DeleteMapper extends SingleMapperAdapter {

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$bindable->setExist(false);
		return true;
	}
}