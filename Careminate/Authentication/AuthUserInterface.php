<?php declare(strict_types=1);
namespace Careminate\Authentication;

interface AuthUserInterface
{
    public function getAuthId(): int|string;

    public function getEmail(): string;

    public function getUsername(): string;

    public function getPassword(): string;
}