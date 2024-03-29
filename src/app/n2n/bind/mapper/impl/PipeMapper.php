<?php

namespace n2n\bind\mapper\impl;

use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\type\ArgUtils;


class PipeMapper implements Mapper {

	/**
	 * @param Mapper[] $mappers
	 */
	public function __construct(private array $mappers) {
		ArgUtils::valArray($this->mappers, Mapper::class);
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): bool {
		foreach ($this->mappers as $mapper) {
			if (!$mapper->map($bindBoundary, $magicContext)) {
				return false;
			}
		}
		return true;
	}
}