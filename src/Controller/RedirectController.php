<?php

namespace Drupal\id4me\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\id4me\StateToken;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for ID4me routes.
 */
class RedirectController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The controller constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Access callback: Redirect page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function access() {
    // Confirm anti-forgery state token. This round-trip verification helps to
    // ensure that the user, not a malicious script, is making the request.
    $query = $this->requestStack->getCurrentRequest()->query;
    $state_token = $query->get('state');
    if ($state_token && StateToken::confirm($state_token)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Authenticate with the Id4me service.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
   * @throws \Id4me\RP\Exception\InvalidIDTokenException
   */
  public function authenticate() {
    $query = $this->requestStack->getCurrentRequest()->query;

    if (!$query->get('error') && !$query->get('code')) {
      // In case we don't have an error, but the client could not be loaded or
      // there is no state token specified, the URI is probably being visited
      // outside of the login flow.
      throw new NotFoundHttpException();
    }

    if ($query->get('error')) {
      if (in_array($query->get('error'), [
        'interaction_required',
        'login_required',
        'account_selection_required',
        'consent_required',
      ])) {
        // If we have an one of the above errors, that means the user hasn't
        // granted the authorization for the claims.
        \Drupal::messenger()->addWarning(t('Logging in with Id4me has been canceled.'));
      }
      else {
        // Any other error should be logged. E.g. invalid scope.
        $variables = [
          '@error' => $query->get('error'),
          '@details' => $query->get('error_description') ? $query->get('error_description') : $this->t('Unknown error.'),
        ];
        $message = 'Authorization failed: @error. Details: @details';
        $this->loggerFactory->get('id4me')->error($message, $variables);
        \Drupal::messenger()->addWarning(t('Could not authenticate with Id4me.'));
      }
    }
    else {
      // Process the login or connect operations.
      /** @var \Drupal\id4me\Id4meService $id4meService */
      $id4meService = \Drupal::service('id4me');
      $id4meService
        ->setState($query->get('state'))
        ->getAuthorizationTokens($query->get('code'));
      $userInfo = $id4meService->getUserInfo();

      /** @var \Drupal\id4me\Authmap $authmapService */
      $authmapService = \Drupal::service('id4me.authmap');
      $account = $authmapService->userLoadBySub($userInfo->getSub(), 'id4me');
      if (\Drupal::currentUser()->isAuthenticated() || !$account) {
        $account = User::create([
          'name' => $userInfo->getPreferredUsername(),
          'mail' => $userInfo->getEmail(),
          'status' => 1,
        ]);
        $account->save();
        $authmapService->createAssociation($account, 'id4me', $userInfo->getSub());
        user_login_finalize($account);
      }
      elseif ($account instanceof UserInterface) {
        user_login_finalize($account);
      }
    }

    return new RedirectResponse(Url::fromUserInput('/')->toString());
  }

}
