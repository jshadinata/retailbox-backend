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
				$message = 'Invalid login';				
				$result = false;
			}
			$this->set(compact('message', 'result'));				
			$this->set('_serialize', ['message', 'result']);
		} else {
			$message = 'Please login.';
			$this->set(compact('message'));				
			$this->set('_serialize', ['message']);
		}
	}	

	public function logout() {
		$this->Auth->logout();
		$result = true;
		$message = 'OK. You are logged out.';
		$this->set(compact('message', 'result'));
		$this->set('_serialize', ['message', 'result']);
	}

	public function whoami() {
		$user_id = $this->Auth->user('id');		
		$user = $this->Users->get($user_id);
		$result = compact('user');
		$message = 'OK';
		$this->set(compact('message', 'result'));
		$this->set('_serialize', ['message', 'result']);
	}

}

?>