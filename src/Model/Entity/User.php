<?php 

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Auth\DefaultPasswordHasher;

class User extends Entity {
	
	protected $_accessible = [
		'*'  => true,
		'id' => false,
	];

	protected $_hidden = [
		'password',
	];

	protected function _setPassword($value) {
		$hasher = new DefaultPasswordHasher();
		return $hasher->hash($value);
	}

}

?>