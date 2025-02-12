<?php

namespace n2n\bind\mapper\impl;

use n2n\util\magic\MagicContext;
use n2n\bind\mapper\Mapper;
use n2n\bind\plan\BindBoundary;
use n2n\util\type\ArgUtils;
use n2n\bind\mapper\MapResult;


class PipeMapper implements Mapper {

	/**
	 * @param Mapper[] $mappers
	 */
	public function __construct(private array $mappers) {
		ArgUtils::valArray($this->mappers, Mapper::class);
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		foreach ($this->mappers as $mapper) {
			$mapResult = $mapper->map($bindBoundary, $magicContext);
			if (!$mapResult->isOk() || $mapResult->isSkipNext()) {
				return $mapResult;
			}
		}
		return new MapResult();
	}
}