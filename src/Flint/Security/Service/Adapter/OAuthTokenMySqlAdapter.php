<?php
namespace Flint\Security\Service\Adapter;

use Miner\MinerFactory;
use Flint\Security\OAuthtokenStorageAdapterInterface;

class OAuthTokenMySqlAdapter implements OAuthtokenStorageAdapterInterface
{
    protected $miner;

    /**
     * @param MinerFactory $miner
     */
    public function __construct(MinerFactory $miner)
    {
        $this->miner = $miner;
    }

    public function findByUserId($id)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('auths')
            ->where('auths.user_id', $id);

        $token = $qb->fetchOne();

        return $token;
    }

    public function find($userId, $providerId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('auths')
            //->where('auths.user_id', $userId)
            ->where('auths.uid', $userId)
            ->andWhere('auths.authtype_id', $providerId);


        $token = $qb->fetchOne();

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function saveToken($tokenData)
    {
        $qb = $this->miner->create();


        $secret = isset($tokenData['secret']) ? $tokenData['secret'] : '';

        $qb
            ->insert('auths')
            ->set('user_id', $tokenData['user_id'])
            ->set('uid', $tokenData['uid'])
            ->set('authtype_id', $tokenData['auth_type'])
            ->set('access_token', $tokenData['access_token'])
            ->set('secret', $secret);

        $qb->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteToken($token)
    {
        $qb = $this->miner->create();

        $qb
            ->delete()
            ->from('auths')
            ->where('auths.access_token', $token);

        $qb->execute();
    }
}
