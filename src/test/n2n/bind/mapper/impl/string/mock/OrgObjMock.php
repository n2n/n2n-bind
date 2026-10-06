<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\spec\valobj\err\IllegalValueException;

class OrgObjMock implements \JsonSerializable {
	public null|PathPartObjMock $path = null;
	public StringValObjMock $title;

	/**
	 * @throws IllegalValueException
	 */
	function __construct(string $title) {
		$this->title = new StringValObjMock($title);
	}

	public function jsonSerialize(): array {
		return ['title' => $this->title, 'path' => $this->path];
	}
}