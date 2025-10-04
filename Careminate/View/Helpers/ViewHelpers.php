<?php declare(strict_types=1);
namespace Careminate\View\Helpers;

use Careminate\Session\Session;
use Careminate\Http\Requests\Request;

class ViewHelpers
{
    protected Request $request;
    protected Session $session;

    public function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;
    }

    public function isActive(string $path): string
    {
        $current = $this->request->path();
        return $path === '/' ? ($current === '/' ? 'active' : '') : (str_starts_with($current, $path) ? 'active' : '');
    }

    public function flash(string $type = 'success'): ?string
    {
        return $this->session->getFlash($type);
    }
}
