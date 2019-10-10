<?php

namespace App\Controller;

use App\Controller\AppController;

class UsersController extends AppController {

	protected $public_action = ['login', 'logout', 'unauthorized'];

	protected $protected_action = ['whoami'];

	public function index() {
		$this->paginate = [
			'finder' => [
				'keywords' => ['q' => $this->request->query('q')]
			],
		];
		$users = $this->paginate($this->Users);
		$this->set(compact('users'));
		$this->set('_serialize', ['users']);
	}

	public function unauthorized() {
		$message = 'Unauthorized access.';
		$this->set(compact('message'));
		$this->set('_serialize', ['message']);
	}

	public function login() {
		if ($this->request->is('post')) {
			$user = $this->Auth->identify();
			if ($user) {
				$this->Auth->setUser($user);

				// update last_login
				$query = $this->Users->query();				
				$query->update()
					->set(['last_login' => $query->func()->now()])
					->where(['id' => $user['id']])
					->execute();
					
				$message = 'OK';
				$result = true;
			} else {
				$message = 'Invalid login. Please retype your username and password.';				
				$result = false;
			}
		} else {
			$result = false;
			$message = 'Please login.';			
		}
		$this->set(compact('message', 'result'));				
		$this->set('_serialize', ['message', 'result']);
	}	

	public function logout() {
		$this->Auth->logout();
		$result = true;
		$message = 'OK. You are logged out.';
		$this->set(compact('message', 'result'));
		$this->set('_serialize', ['message', 'result']);
	}

	public function whoami() {

		// get user id from Auth
		$user_id = $this->Auth->user('id');		
		
		// load user (toArray)
		$user = $this->Users->get($user_id, [
			'contain' => [
				'CurrentCompany' => ['fields' => ['id', 'name']],				
			],
		])->toArray();

		// load rights
		$rights = '';
		if ($user['current_company_id'] != null) {
			$this->loadModel('CompaniesUsers');
			$user_rights = $this->CompaniesUsers->find()
					->where([
						'user_id' => $user_id,
						'company_id' => $user['current_company_id']
					])
					->first()->user_rights;			
			$user['current_company']['user_rights'] = $user_rights; // add to $user

		}
		$result = true;		
		$message = 'OK';
		$this->set(compact('message', 'result', 'user'));
		$this->set('_serialize', ['message', 'result', 'user']);
	}

}

?>