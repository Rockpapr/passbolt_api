<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace App\Controller\Setup;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\BadRequestException;
use Cake\Validation\Validation;

class SetupStartController extends AppController
{
    /**
     * Before filter
     *
     * @param Event $event An Event instance
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(Event $event)
    {
        $this->Auth->allow('start');

        return parent::beforeFilter($event);
    }

    /**
     * Setup start
     *
     * @throws BadRequestException if the user or token id are missing or not uuids
     * @throws BadRequestException if the authentication token is expired or not valid for this user
     * @param string $userId uuid of the user
     * @param string $tokenId uuid of the token
     * @return void
     */
    public function start($userId, $tokenId)
    {
        // Check request sanity
        if (!isset($userId)) {
            throw new BadRequestException(__('The user id is missing.'));
        }
        if (!Validation::uuid($userId)) {
            throw new BadRequestException(__('The user id is not valid. It should be a uuid.'));
        }
        if (!isset($tokenId)) {
            throw new BadRequestException(__('The authentication token is missing.'));
        }
        if (!Validation::uuid($tokenId)) {
            throw new BadRequestException(__('The token is not valid. It should be a uuid.'));
        }

        // Check that the token exists
        $this->loadModel('AuthenticationTokens');
        if (!$this->AuthenticationTokens->isValid($tokenId, $userId)) {
            throw new BadRequestException(__('The authentication token is not valid or expired.'));
        }

        // Retrieve the user.
        $this->loadModel('Users');
        $user = $this->Users->findSetupStart($userId);
        if (empty($user)) {
            // @TODO more precise error message
            throw new BadRequestException(__('The user does not exist or is already active or has been deleted.'));
        }
        $this->set('user', $user);

        // Parse the user agent
        $this->loadModel('UserAgents');
        $browserName = $this->UserAgents->browserName();
        $this->set('browserName', strtolower($browserName));

        $this->viewBuilder()
            ->setTemplatePath('/Setup')
            ->setLayout('default')
            ->setTemplate('start');
    }
}