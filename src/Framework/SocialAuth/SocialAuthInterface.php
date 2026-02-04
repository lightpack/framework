<?php

namespace Lightpack\SocialAuth;

interface SocialAuthInterface
{
    public function getAuthUrl(array $params = []): string;
    public function getUser(string $code): array;
    public function stateless(): self;
}
