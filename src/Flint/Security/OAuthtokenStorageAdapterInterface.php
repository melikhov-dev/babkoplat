<?php
namespace Flint\Security;

interface OAuthtokenStorageAdapterInterface
{
    /**
     * @param integer $id
     *
     * @return array
     */
    function findByUserId($id);

    /**
     * Create new token
     *
     * @param array $tokenData
     *
     * @return void
     */
    function saveToken($tokenData);

    /**
     * remove token
     *
     * @param string $token
     *
     * @return void
     */
    function deleteToken($token);
}
