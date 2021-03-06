<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\LoginViaPost\Controller;

use OC\Authentication\Login\Chain;
use OC\Authentication\TwoFactorAuth\Manager;
use OCA\LoginViaPost\Request;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Defaults;
use OCP\IConfig;
use OCP\IInitialStateService;
use OCP\ILogger;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OC\Authentication\WebAuthn\Manager as WebAuthnManager;

class LoginController extends Controller {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IUserSession */
	private $userSession;
	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var ISession */
	private $session;
	/** @var ILogger */
	private $logger;
	/** @var Manager */
	private $twoFactorAuthManager;

	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								IUserSession $userSession,
								IUserManager $userManager,
								IConfig $config,
								ISession $session,
								ILogger $logger,
								Manager $twoFactorAuthManager) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->config = $config;
		$this->session = $session;
		$this->logger = $logger;
		$this->twoFactorAuthManager = $twoFactorAuthManager;
	}

	private function getMockedRequest() {
		return new Request(
			[
				'get' => $_GET,
				'post' => $_POST,
				'files' => $_FILES,
				'server' => $_SERVER,
				'env' => $_ENV,
				'cookies' => $_COOKIE,
				'method' => (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']))
					? $_SERVER['REQUEST_METHOD']
					: null,
			],
			null,
			$this->config
		);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 * @BruteForceProtection(action=login)
	 * @UseSession
	 *
	 * @param string $username
	 * @param string $password
	 * @return RedirectResponse
	 */
	public function login($username, $password) {
		$class = new \ReflectionClass(\OC\Core\Controller\LoginController::class);
		$parameters = $class->getConstructor()->getParameters();

		/** @var \OC\Core\Controller\LoginController $loginController */
		$loginController = new \OC\Core\Controller\LoginController(
			'core',
			$this->getMockedRequest(),
			$this->userManager,
			$this->config,
			$this->session,
			$this->userSession,
			$this->urlGenerator,
			\OC::$server->getLogger(),
			\OC::$server->query(Defaults::class),
			\OC::$server->getBruteForceThrottler(),
			\OC::$server->query(Chain::class),
			\OC::$server->query(IInitialStateService::class),
			\OC::$server->query(WebAuthnManager::class)
		);

		return $loginController->tryLogin($username, $password, '');
	}
}
