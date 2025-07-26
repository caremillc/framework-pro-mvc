<?php declare(strict_types=1);

namespace Careminate\Http\Responses;

class RedirectResponse extends Response
{
    protected bool $exitAfterRedirect = true;

    public function __construct(string $url, int $status = self::HTTP_FOUND)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('#^/[\w\-/]+$#', $url)) {
            throw new \InvalidArgumentException('Invalid redirect URL');
        }

        parent::__construct('', $status, ['Location' => $url]);
    }

    public function setExitAfterRedirect(bool $exit): static
    {
        $this->exitAfterRedirect = $exit;
        return $this;
    }

    public function withUrl(string $url): static
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('#^/[\w\-/]+$#', $url)) {
            throw new \InvalidArgumentException('Invalid redirect URL');
        }

        $clone = clone $this;
        return $clone->setHeader('Location', $url);
    }

    public function send(): void
    {
        if ($this->areHeadersSent()) {
            return;
        }

        $url = $this->getHeader('Location');
        if (!$url) {
            throw new \RuntimeException('No location header set for redirect.');
        }

        http_response_code($this->getStatus());
        header("Location: $url", true);

        if ($this->exitAfterRedirect) {
            exit;
        }
    }
}


