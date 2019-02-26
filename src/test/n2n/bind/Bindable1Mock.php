<?php
namespace n2n\bind;

use n2n\util\type\TypeConstraints;
use n2n\reflection\annotation\AnnoInit;
use n2n\reflection\property\annotation\AnnoType;

class Bindable1Mock implements Bindable {
	private static function _annos(AnnoInit $ai) {
// 		$ai->c(new AnnoPropTypes([
// 			'firstname' => TypeConstraints::string(),
// 			'lastname' => TypeConstraints::scalar()
// 		]));
		$ai->p('nickname', new AnnoType('?string'));
	}
	
	
	private $firstname;
	private $lastname;
	private $nickname;
	private $bindable2Mocks;
	
	function getFirstname() {
		
	}
	
	function setFirstname(?string $firstname) {
		
	}
	
	function setSomething(float $huii) {
		
	}
	
	function setNickname(int $long) {
		
	}
	
	function _bind(BindModel $bm) {
		$bm->inOut(['firstname', 'lastname', 'nickname'])
				->val(Validators::required(), Validators::length(3, 255));
		$bm->prop('bindable2Mocks', TypeConstraints::arrayObject(false, Bindable2Mock::class));
		$bm->prop('date', Mappers::dateTime());
	}
	
		
	function _unmarshal(UnmarshalModel $um) {
		$um->prop('firstname', 'lastname')->map(Mappers::dateTime())->val(Validators::email());
	}
	
	function _marshal(MarshalModel $mm) {
		$mm->autoProps();
		$mm->prop('firstname', 'lastname')->map(Mappers::ucfirst());
		
		$mm->prop('firstName', 'lastName')->val(Validators::ucfirst());
		$mm->prop('firstName')->map(Mappers::ucfirst());
		$mm->prop('category');
		
		$mm->provide(['firstname' => 'hallo', 'fileUrl' => '..', 'fileName' => '..'])->map(ScalarMapper);
		
		return [
			'randomprop' => $this->getName(),
			'rindomprip' => 'hilli'
		];
	}
	
}