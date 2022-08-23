<?php
namespace n2n\bind\mapper\impl;

class Mappers {
	/**
	 * @return \n2n\bind\mapper\impl\DateTimeTsMapper
	 */
	static function dateTimeTs() {
		return new DateTimeTsMapper();
	}
}