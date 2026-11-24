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
	 * @param bool $grouped if true, the returned {@see MapResult} only takes over {@see MapResult::isOk()} of the
	 *     inner mappers, but never {@see MapResult::isSkipNext()}. A skip therefore only affects the mappers inside
	 *     this group, not the mappers following it.
	 */
	public function __construct(private array $mappers, private bool $grouped = false) {
		ArgUtils::valArray($this->mappers, Mapper::class);
	}

	function add(Mapper $mapper): static {
		$this->mappers[] = $mapper;
		return $this;
	}

	function map(BindBoundary $bindBoundary, MagicContext $magicContext): MapResult {
		foreach ($this->mappers as $mapper) {
			$mapResult = $mapper->map($bindBoundary, $magicContext);
			if (!$mapResult->isOk() || $mapResult->isSkipNext()) {
				return $this->grouped ? new MapResult($mapResult->isOk()) : $mapResult;
			}
		}
		return new MapResult();
	}
}