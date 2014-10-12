<?php
namespace Flint\Security;

interface UserStorageAdapterInterface
{
    /**
     * Get user data by given criteria
     *
     * @param array $criteriaList
     *
     * @return array
     */
    function findUserBy(array $criteriaList);

    /**
     * Get user data by token
     *
     * @param string $token
     *
     * @return mixed
     */
    public function findUserByToken($token);

    /**
     * Get user data by id
     *
     * @param integer $id
     *
     * @return array
     */
    function findUserById($id);

    /**
     * Save User token by data
     *
     * @param $tokenData
     *
     * @return void
     */
    function saveToken($tokenData);

    /**
     * Remove user token
     *
     * @param string $token
     *
     * @return void
     */
    function deleteToken($token);

    /**
     * Create bew User
     *
     * @param string $username
     * @param string $password
     * @param array  $data
     *
     * @return array
     */
    function createUser($username, $password, $data = array());

    /**
     * Update User
     *
     * @param int          $id
     * @param array        $data
     * @param string|null  $password
     *
     * @return bool
     */
    function updateUser($id, array $data, $password = null);

    /**
     * activate User for everyday Newsletter
     */
    function activateUser($id);

    /**
     * @param int $userId
     *
     * @return array
     */
    function getSocialNetworkAssociations($userId);

    /**
     * @param int $providerId
     *
     * @param int $uid
     *
     * @return array
     */
    function getUserByOAuthUid($providerId, $uid);

    /**
     * @param int $providerId
     * @param int $userId
     *
     * @return mixed
     */
    function unassociateSocialNetwork($providerId, $userId);

    /**
     * @param int $userId
     * get User is subscribed or not
     */
    function isUserSubscribed($userId);

    /**
     * @param $userId
     * @param $data
     * @return mixed
     */
    function setSubscribeSetting($userId, $data);

    /**
     * @param string $phone
     * @param int $userId
     * activate number for User
     */
    function activateNumber($phone, $userId);

    /**
     * @param int $userId
     * get coupon count
     */
    function getCouponCountForUser($userId);

    /**
     * @param int $userId
     * get review count
     */
    function getReviewCountForUser($userId);
}
