<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

    protected $public_action = [];    // not required login
    protected $protected_action = []; // require login
    protected $private_action = [];   // require login & access key


    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize() {
        parent::initialize();

        $this->loadComponent('RequestHandler', [
            'enableBeforeRedirect' => false,
        ]);        

        // force all as JSON
        $this->RequestHandler->renderAs($this, 'json');

        // $this->loadComponent('Flash');

        $this->loadComponent('Auth', [
            'authenticate'         => ['Form'],
            'loginAction'          => ['controller'=>'Users', 'action'=>'login',],
            'authorize'            => ['Controller'],
            'unauthorizedRedirect' => ['controller'=>'Users', 'action'=>'unauthorized'],
        ]);

        $this->Auth->allow(['display']);
        $this->Auth->allow($this->public_action);

        /*
         * Enable the following component for recommended CakePHP security settings.
         * see https://book.cakephp.org/3.0/en/controllers/components/security.html
         */
        //$this->loadComponent('Security');

    }

    public function isAuthorized($user) {
        // protected action (don't need access key)
        if (in_array($this->request->action, $this->protected_action)) {
            return true;
        }

        // private action, need specific access key
        if (array_key_exists($this->request->action, $this->private_action)) {
            // at least one of these $key ($key can be single key or array)
            $session = $this->request->getSession();
            $user_rights = $session->read('User.user_rights');            
            $key = $this->private_action[$this->request->action];
            if (is_array($key)) { // if array, check each key (only need one)
                foreach ($key as $k) {
                    $test = ":$k:"; // add : at beginning and end, prevent mixing small key in large key, eg: 10 in 1102
                    if (strpos($user_rights, $test) !== false) return true;
                }
            } else { // not array, single key... 
                $test = ":$k:";
                if (strpos($user_rights, $test) !== false) return true;
            }
        }

        return false;
    }

}
