<?php
namespace n2n\bind\type;

use n2n\bind\plan\Bindable;
use n2n\bind\marshal\MarshalComposer;

class Bindable1Mock implements Bindable {
	private $someValue = 'huii';
	private $b2m;
	
	function getSomeValue() {
		return $this->someValue;
	}
	
	function setSomeValue(?string $someValue) {
		$this->someValue = $someValue;
	}
	
	function setB2m(Bindable2Mock $b2m) {
		$this->b2m = $b2m;
	}
	
	function getB2m() {
		return $this->b2m;
	}
	
	private function _marshal(MarshalComposer $mc) {
		$mc->prop('someValue')->map(function ($value) {
			return $value . ' mapper suffix';
		});
		
		$mc->prop('b2m');
	}
}