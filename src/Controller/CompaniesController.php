<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\Query;

class CompaniesController extends AppController {

	protected $public_action = [];
	
	protected $protected_action = ['index', 'get', 'add', 'edit'];

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
		$this->set(compact('result', 'message', 'companies'));
		$this->set('_serialize', ['result', 'message', 'companies']);
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
		$this->set(compact('result', 'message', 'company'));
		$this->set('_serialize', ['result', 'message', 'company']);
	}

	public function add() {
		$result = false;
		$message = 'Nothing happened.';
		if ($this->request->is('post')) {
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
				$this->set(compact('result', 'message', 'company'));
				$this->set('_serialize', ['result', 'message', 'company']);
			} else {
				$message = 'The company could not be saved.';
				$e = $company->errors();
				$errors = [];
				foreach ($e as $field => $msg) {
					$errors[$field] = reset($msg);
				}
				$this->set(compact('result', 'message', 'errors'));
				$this->set('_serialize', ['result', 'message', 'errors']);
			}
		} else {
			$this->set(compact('result', 'message'));
			$this->set('_serialize', ['result', 'message']);
		}
	}

	public function edit($id) {
		$result = false;
		$message = 'Nothing happened.';
		if ($this->request->is('post')) {

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

				// don't has owner key
				$result = false;
				$message = 'Only owner can edit company info.';
				$this->set(compact('result', 'message'));
				$this->set('_serialize', ['result', 'message']);
			} else {

				// has owner key
				$company = $this->Companies->get($id, ['contain'=>[]]);
				$company = $this->Companies->patchEntity($company, $this->request->data);
				if ($this->Companies->save($company)) {
					$result = true;
					$message = 'OK';				
					$this->set(compact('result', 'message', 'company'));
					$this->set('_serialize', ['result', 'message', 'company']);
				} else {
					$message = 'The company could not be saved.';
					$e = $company->errors();
					$errors = [];
					foreach ($e as $field => $msg) {
						$errors[$field] = reset($msg);
					}
					$this->set(compact('result', 'message', 'errors'));
					$this->set('_serialize', ['result', 'message', 'errors']);
				}
			}
		} else {

			// not a HTTP POST request
			$this->set(compact('result', 'message'));
			$this->set('_serialize', ['result', 'message']);	
		}
	}

}

?>