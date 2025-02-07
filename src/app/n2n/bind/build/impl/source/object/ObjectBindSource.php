<?php
namespace n2n\bind\build\impl\source\object;

use n2n\bind\plan\BindInstance;
use n2n\bind\plan\BindSource;

class ObjectBindSource implements BindSource {
	public function __construct(private object $object) {
	}

	public function next(mixed $input): BindInstance {
		return new ObjectBindInstance($this->object);
	}
}