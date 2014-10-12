<?php
namespace Flint\Security\Service\Encoder;

use Flint\Security\EncoderInterface;

/**
 * Password encoder
 *
 * This encoder migrated from FEP
 * @author a.chernykh
 */
class PasswordEncoder implements EncoderInterface
{
    protected $saltPattern = [1, 3, 5, 9, 14, 15, 20, 21, 28, 30];
    protected $hashMethod = 'sha1';

    public function encode($password, $oldPassword = null)
    {
        // Get the salt from the stored password
        // Set salt to null if User is new and haven't old password
        $salt = $oldPassword ? $this->findSalt($oldPassword) : null;

        // Create a hashed password using the salt from the stored password
        return $this->hashPassword($password, $salt);
    }

    /**
     * Finds the salt from a password, based on the configured salt pattern
     *
     * @param string $password hashed password
     *
     * @return string
     */
    protected function findSalt($password)
    {
        $salt = '';
        foreach ($this->saltPattern as $i => $offset) {
            // Find salt characters, take a good long look...
            $salt .= substr($password, $offset + $i, 1);
        }

        return $salt;
    }

    /**
     * Get hash
     *
     * @param string $str
     *
     * @return string
     */
    protected function hash($str)
    {
        return hash($this->hashMethod, $str);
    }

    /**
     * Creates a hashed password from a plaintext password, inserting salt based on the configured salt pattern
     *
     * @param string $password plaintext password
     * @param string $salt
     *
     * @return string hashed password string
     */
    protected function hashPassword($password, $salt = null)
    {
        if ($salt === null) {
            // Create a salt seed, same length as the number of offsets in the pattern
            $salt = substr($this->hash(uniqid(null, true)), 0, count($this->saltPattern));
        }

        // Password hash that the salt will be inserted into
        $hash = $this->hash($salt . $password);

        // Change salt to an array
        $salt = str_split($salt, 1);

        // Returned password
        $password = '';

        // Used to calculate the length of splits
        $lastOffset = 0;

        foreach ($this->saltPattern as $offset) {
            // Split a new part of the hash off
            $part = substr($hash, 0, $offset - $lastOffset);

            // Cut the current part out of the hash
            $hash = substr($hash, $offset - $lastOffset);

            // Add the part to the password, appending the salt character
            $password .= $part . array_shift($salt);

            // Set the last offset to the current offset
            $lastOffset = $offset;
        }

        // Return the password, with the remaining hash appended
        return $password . $hash;
    }
}
