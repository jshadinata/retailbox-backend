<?php

namespace App\Controller;

use App\Controller\AppController;

class UsersController extends AppController {

	protected $public_action = ['login', 'logout', 'unauthorized'];

	protected $protected_action = ['whoami', 'listCompany'];

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
		$this->request->getSession()->destroy();
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
		$user_rights = ''; // assume no rights

		// add to session
		$session = $this->request->getSession();
		$session->write('User.id', $user_id);
		$session->write('User.current_company_id', $user['current_company_id']);
		$session->write('User.user_rights', $user_rights);

		// load rights
		if ($user['current_company_id'] != null) {
			$this->loadModel('CompaniesUsers');
			$user_rights = $this->CompaniesUsers->find()
					->where([
						'user_id' => $user_id,
						'company_id' => $user['current_company_id']
					])
					->first()->user_rights;			

			// add to $user
			$user['current_company']['user_rights'] = $user_rights; 
			
			// rewrite session
			$session->write('User.user_rights', $user_rights);
		}
		$result = true;		
		$message = 'OK';
		$this->set(compact('message', 'result', 'user'));
		$this->set('_serialize', ['message', 'result', 'user']);
	}

	public function listCompany() {
		$user_id = $this->request->getSession()->read('User.id');
		// $this->loadModel('CompaniesUsers');
		// $companies = $this->CompaniesUsers->find()
		// 	->where(['user_id' => $user_id]);
		$this->loadModel('Companies');
		$companies = $this->Companies->find()			
			->select(['id', 'name', 'address', 'phone', 'website'])
			->matching('Users', function($q) use ($user_id) {
				return $q->where(['Users.id' => $user_id]);
			})
			->toArray();
		$result = true;
		$message = 'OK';		
		$this->set(compact('result', 'message', 'companies'));
		$this->set('_serialize', ['result', 'message', 'companies']);
	}

}

?>