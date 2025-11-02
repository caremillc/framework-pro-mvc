<?php declare (strict_types = 1);
namespace Careminate\Authentication;

use Careminate\Authentication\AuthRepositoryInterface;
use Careminate\Authentication\AuthUserInterface;
use Careminate\Authentication\SessionAuthInterface;
use Careminate\Session\Session;
use Careminate\Session\SessionInterface;

class SessionAuthentication implements SessionAuthInterface
{
    private ?AuthUserInterface $user = null;

    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private SessionInterface $session) {}

    public function authenticate(string $email, string $password): bool
    {
        // query db for user using email
        $user = $this->authRepository->findByEmail($email);

        if (! $user) {
            return false;
        }

        // Does the hashed user pw match the hash of the attempted password
        if (! password_verify($password, $user->getPassword())) {
            // return false
            return false;
        }
        // if yes, log the user in
        $this->login($user);

        // return true
        return true;

    }

    public function login(AuthUserInterface $user)
    {
        // Start a session
        $this->session->start();

        // Log the user in
        $this->session->set(Session::AUTH_KEY, $user->getAuthId());

        // Set the user
        $this->user = $user;
    }

   public function logout()
    {
        $this->session->remove(Session::AUTH_KEY);
    }
    
   public function getUser(): AuthUserInterface
    {
        if ($this->user !== null) {
            return $this->user;
        }

        // Attempt to retrieve user ID from session
        $authId = $this->session->get(Session::AUTH_KEY);

        if (! $authId) {
            throw new \LogicException('No user is currently logged in.');
        }

        // Use repository to retrieve user
        $user = $this->authRepository->findById($authId);

        if (! $user) {
            throw new \LogicException('User session is invalid or user not found.');
        }

        $this->user = $user;
        return $this->user;
    }

    public function check(): bool
    {
        return $this->session->has(Session::AUTH_KEY);
    }

}
