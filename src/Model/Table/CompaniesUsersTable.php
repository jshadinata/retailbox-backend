<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;

class CompaniesUsersTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);

        $this->belongsTo('Companies');
        $this->belongsTo('Users');

        $this->addBehavior('Timestamp');
	}

}

?>