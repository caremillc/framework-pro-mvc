<?php  declare(strict_types=1);
namespace Careminate\Http\Responses;

class RedirectResponse extends Response
{
    protected ?string $location = null;

    public function __construct(?string $location = null)
    {
        if ($location) {
            $this->location = $location;
        }
    }

    public function to(string $url): self
    {
        $this->location = $url;
        return $this;
    }

    public function send(): void
    {
        if (!$this->location) {
            throw new \RuntimeException('No location header set for redirect.');
        }

        header('Location: ' . $this->getHeader('location'), true, $this->getStatus());
        exit;
       
    }
}
