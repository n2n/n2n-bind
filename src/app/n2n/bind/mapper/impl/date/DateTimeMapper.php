<?php
namespace n2n\bind\mapper\impl\date;

class DateTimeMapper extends DateTimeInterfaceMapperAdapter {
	function __construct(private bool $mandatory, private ?\DateTimeInterface $min = null,
			private ?\DateTimeInterface $max = null) {
		parent::__construct(true,$mandatory, $min, $max);
	}
}