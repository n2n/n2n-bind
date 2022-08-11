<?php
namespace n2n\bind\type;

use n2n\bind\plan\Bindable;
use n2n\bind\marshal\MarshalComposer;

class Bindable2Mock implements Bindable {
	
	private $someValue;
	public $autoBindable;
	
	function __construct() {
		$this->autoBindable = new AutoBindableMock('Auto');
	}
	
	function _marshal(MarshalComposer $mc) {
		$mc->prop('autoBindable');
	}
}