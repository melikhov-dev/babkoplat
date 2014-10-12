<?php
namespace Flint\Security;

/**
 * EncoderInterface
 */
interface EncoderInterface
{
    /**
     * Encode password
     *
     * @param string      $password
     * @param string|null $oldPassword
     *
     * @return string encoded password
     */
    function encode($password, $oldPassword = null);
}
