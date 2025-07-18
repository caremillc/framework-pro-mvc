<?php declare (strict_types = 1);
namespace Careminate\Mailer;

interface MailerInterface 
{
    public function send(string $email, string $message);
}