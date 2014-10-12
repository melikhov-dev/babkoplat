<?php
namespace Flint\Security;

class OAuthProviderTypeEnum
{
    const FACEBOOK  = 1;
    const VKONTAKTE = 2;
    const MAILRU    = 3;
    const TWITTER   = 4;

    static public function getId($providerName)
    {
        $map = [
            'twitter'   => self::TWITTER,
            'facebook'  => self::FACEBOOK,
            'vkontakte' => self::VKONTAKTE,
            'mailru'    => self::MAILRU
        ];

        if (!isset($map[$providerName])) {
            throw new \Exception(sprintf("Provider type constant not found for %s", $providerName));
        }

        return $map[$providerName];
    }
}
