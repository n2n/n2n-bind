<?php
namespace n2n\bind\map\impl;

class Mappers {
	/**
	 * @return \n2n\bind\map\impl\DateTimeTsMapper
	 */
	static function dateTimeTs() {
		return new DateTimeTsMapper();
	}
}