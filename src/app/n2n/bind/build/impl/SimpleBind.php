<?php

namespace n2n\bind\build\impl;

use n2n\util\attr\AttributeReader;
use n2n\bind\plan\BindSource;
use n2n\util\magic\TaskResult;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicTask;

class SimpleBind {

	/**
	 * Shorthand for
	 *
	 * ```php
	 * Bind::attrs()->root(Mappers::unmarshal($typeName))->toValue()
	 * ```
	 *
	 * @see Mappers::unmarshal()
	 */
	static function unmarshalAttrs(string $typeName,
			AttributeReader|array|BindSource|\JsonSerializable|null $source = null): MagicTask {
		return Bind::attrs($source)->root(Mappers::unmarshal($typeName))->toValue();
	}
}