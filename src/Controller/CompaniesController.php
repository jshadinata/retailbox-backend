<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\Query;

class CompaniesController extends AppController {

	protected $public_action = [];
	
	protected $protected_action = ['index', 'get', 'add', 'edit'];

	private function setAllAndSerialize($arr) {
		$this->set($arr);
		$this->set('_serialize', array_keys($arr));
	}

	public function index() {
		$user_id = $this->request->getSession()->read('User.id');
		$companies = $this->Companies->find()			
			->select(['id', 'name', 'address', 'phone', 'website'])
			->matching('Users', function(Query $q) use ($user_id) {
				return $q->where(['Users.id' => $user_id]);
			})
			->toArray();
		$result = true;
		$message = 'OK';		
		$this->setAllAndSerialize(compact('result', 'message', 'companies'));		
	}

	public function get($id = null) {
		$user_id = $this->request->getSession()->read('User.id');
		$company = $this->Companies->find()
			->select(['id', 'name', 'address', 'phone', 'email', 'website', 'api_key'])
			->contain('Users', function (Query $q) use ($user_id) {
				return $q
					->select(['id'])
					->where(['Users.id' => $user_id]);
			})
			->where(['Companies.id'=>$id])
			->matching('Users', function(Query $q) use ($user_id) {
				return $q->where(['Users.id' => $user_id]);
			})
			->first()
			->toArray();
		$result = true;
		$message = 'OK';		
		$this->setAllAndSerialize(compact('result', 'message', 'company'));		
	}

	public function add() {

		// check if HTTP POST
		if (!$this->request->is('post')) {
			$result = false;
			$message = 'Nothing happened.';
			$this->setAllAndSerialize(compact('result', 'message'));
			return;
		}

		// check duplicate api_key						
		$proposed_api_key = $this->request->getData('api_key');
		if ($proposed_api_key != null && $proposed_api_key != '') {
			$query = $this->Companies->find();
			$count = $query->select(['count' => $query->func()->count('*')])
				->where(['api_key' => $proposed_api_key])
				->first()->count;				
			if ($count > 0) {
				$result = false;
				$errors = ['api_key' => 'Duplicate API Key, please supply another key!'];
				$message = 'The company could not be saved.';
				$this->setAllAndSerialize(compact('result', 'message', 'errors'));				
				return;
			}
		}

		// pass validation
		$company = $this->Companies->newEntity();
		$company = $this->Companies->patchEntity($company, $this->request->data);			
		if ($this->Companies->save($company)) {
			
			// create this user as first member
			$this->loadModel('CompaniesUsers');
			$user_id = $this->request->getSession()->read('User.id');
			$cu = $this->CompaniesUsers->newEntity();
			$cu->user_id = $user_id;
			$cu->company_id = $company->id;
			$cu->user_rights = ':0:';
			$this->CompaniesUsers->save($cu);

			$result = true;
			$message = 'OK';
			$this->setAllAndSerialize(compact('result', 'message', 'company'));
			
		} else {
			$message = 'The company could not be saved.';
			$e = $company->errors();
			$errors = [];
			foreach ($e as $field => $msg) {
				$errors[$field] = reset($msg);
			}
			$this->setAllAndSerialize(compact('result', 'message', 'errors'));			
		}		
	}

	public function edit($id) {

		// check if HTTP POST
		if (!$this->request->is('post')) {
			$result = false;
			$message = 'Nothing happened.';
			$this->setAllAndSerialize(compact('result', 'message'));	
			return;
		}

		// check duplicate api_key						
		$proposed_api_key = $this->request->getData('api_key');
		if ($proposed_api_key != null && $proposed_api_key != '') {
			$query = $this->Companies->find();
			$count = $query->select(['count' => $query->func()->count('*')])
				->where([
					'id !=' => $id,
					'api_key' => $proposed_api_key
				])
				->first()->count;				
			if ($count > 0) {
				$result = false;
				$errors = ['api_key' => 'Duplicate API Key, please supply another key!'];
				$message = 'The company could not be saved.';
				$this->setAllAndSerialize(compact('result', 'message', 'errors'));					
				return;
			}
		}

		// check if user is owner (has :0: key)
		$user_id = $this->request->getSession()->read('User.id');
		$this->loadModel('CompaniesUsers');
		$uc = $this->CompaniesUsers->find()
			->select('user_rights')
			->where([
				'user_id' => $user_id,
				'company_id' => $id,
			])
			->first();
		if ($uc == null || strpos($uc['user_rights'], ':0:') === false) {
			$result = false;
			$message = 'Only owner can edit company info.';
			$this->setAllAndSerialize(compact('result', 'message'));				
			return;
		} 

		// pass validation
		$company = $this->Companies->get($id, ['contain'=>[]]);
		$company = $this->Companies->patchEntity($company, $this->request->data);
		if ($this->Companies->save($company)) {
			$result = true;
			$message = 'OK';				
			$this->setAllAndSerialize(compact('result', 'message', 'company'));				
		} else {
			$result = false;
			$message = 'The company could not be saved.';
			$e = $company->errors();
			$errors = [];
			foreach ($e as $field => $msg) {
				$errors[$field] = reset($msg);
			}
			$this->setAllAndSerialize(compact('result', 'message', 'errors'));
		}
	}

}

?>