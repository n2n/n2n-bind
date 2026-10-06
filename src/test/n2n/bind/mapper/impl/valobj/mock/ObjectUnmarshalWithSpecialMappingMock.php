<?php

namespace n2n\bind\mapper\impl\valobj\mock;

use n2n\bind\mapper\impl\valobj\util\ObjectUnmarshalTrait;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\attribute\impl\Unmarshal;

class ObjectUnmarshalWithSpecialMappingMock {
	use ObjectUnmarshalTrait {
		unmarshal as traitUnmarshal;
	}

	public string $name;
	public int $age;

	#[Unmarshal]
	static function unmarshal(): Mapper {
		return self::traitUnmarshal(Mappers::subProps()->prop('age', Mappers::value(fn ($v) => 999999)));
	}
}
