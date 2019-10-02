<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;

class UsersTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('Timestamp');
	}

	public function findKeywords(Query $query, array $options) {
        $where = [];
        if (array_key_exists('q', $options)) {
            $q = $options['q'];
            $keywords = explode(' ', $q);
            $fields = ['Users.username'];
            foreach ($fields as $field) {
                $field_where = [];
                foreach ($keywords as $keyword) {
                    $field_where[] = ["$field LIKE" => "%$keyword%"];
                }
                $where['OR'][] = $field_where;
            }
        }
        return $query->where($where);
    }

}

?>