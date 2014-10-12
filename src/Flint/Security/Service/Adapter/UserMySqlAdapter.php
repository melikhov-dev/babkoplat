<?php
namespace Flint\Security\Service\Adapter;

use Miner\MinerFactory;
use Flint\Security\UserStorageAdapterInterface;
use Utils\Arr;

class UserMySqlAdapter implements UserStorageAdapterInterface
{
    protected $miner;

    /**
     * @param MinerFactory $miner
     */
    public function __construct(MinerFactory $miner)
    {
        $this->miner = $miner;
    }

    /**
     * {@inheritDoc}
     */
    public function findUserBy(array $criteriaList)
    {

        if (empty($criteriaList)) {
            return false;
        }

        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('users');

        foreach($criteriaList as $name => $value) {
            $qb->andWhere('users.' . $name, $value);
        }

        $user = $qb->fetchOne();

        if ($user) {
            $user['tokens'] = $this->getTokensByUserId($user['id']);
            $user['roles']  = $this->getRolesByUserId($user['id']);
        }

        return $user;
    }

    public function findUserByToken($token)
    {
        $qb = $this->miner->create();
        $qb
            ->select('users.*')
            ->from('users')
            ->join('user_tokens', 'user_tokens.user_id = users.id')
            ->where('user_tokens.token', $token);

        $user = $qb->fetchOne();

        if ($user) {
            $user['tokens'] = $this->getTokensByUserId($user['id']);
            $user['roles']  = $this->getRolesByUserId($user['id']);
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function findUserById($id)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('users')
            ->where('users.id', $id);

        $user = $qb->fetchOne();

        if ($user) {
            $user['tokens'] = $this->getTokensByUserId($id);
            $user['roles']  = $this->getRolesByUserId($id);
        }

        return $user;
    }

    /**
     * @param integer $id
     *
     * @return array
     */
    protected function getTokensByUserId($id)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('user_tokens')
            ->where('user_tokens.user_id', $id);

        return $qb->fetchAll();
    }

    /**
     * @param integer $id
     *
     * @return array
     */
    protected function getRolesByUserId($id)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('roles')
            ->join('roles_users', 'roles_users.role_id = roles.id')
            ->where('roles_users.user_id', $id);

        return $qb->fetchAll();
    }

    /**
     * {@inheritDoc}
     */
    public function saveToken($tokenData)
    {
        $qb = $this->miner->create();

        $qb
            ->insert('user_tokens')
            ->set('user_id', $tokenData['user_id'])
            ->set('user_agent', $tokenData['user_agent'])
            ->set('token', $tokenData['token'])
            ->set('expires', $tokenData['expires']);

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
            ->from('user_tokens')
            ->where('token', $token);

        $qb->execute();
    }

    public function createUser($username, $password, $data = array())
    {
        $today      = new \DateTime();
        $connection = $this->miner->create()->getPdoConnection();
        $newUser    = null;

        try {
            $connection->beginTransaction();
            $qb = $this->miner->create();
            $qb
                ->insert('users')
                ->set('email', $username)
                ->set('username', $username)
                ->set('password', $password)
                ->set('created', $today->getTimestamp());

            foreach($data as $key => $value){
                $qb->set($key, $value);
            }

            $qb->execute();

            $newUser = $this->miner->create()
                ->select('*')
                ->from('users')
                ->where('email', $username)
                ->fetchOne();

            $qb = $this->miner->create();

            $qb
                ->insert('roles_users')
                ->set('user_id', $newUser['id'])
                ->set('role_id', 1) // Login ROLE
                ->execute();

            $qb = $this->miner->create();

            $qb
                ->insert('user_settings')
                ->set('user_id', $newUser['id'])
                ->set('created', $today->getTimestamp())

                ->set('notify_comment_company_feedback', 1)
                ->set('notify_friend_request', 1)

                ->set('who_add_event', 0)
                ->set('who_see_friends', 0)
                ->set('who_add_me', 1)

                ->set('who_send_message', 1)
                ->set('who_see_coupons', 0)
                ->set('who_see_events', 0)
                ->set('who_see_feedbacks', 0)

                ->set('post_to_fb', 1)
                ->set('post_to_tw', 1)
                //->set('subscribe', 1) //? сейчас?

                ->set('who_see_email', 0)
                ->set('who_see_city', 1)
                ->set('who_see_birthday', 1)
                ->set('who_see_phone', 0)
                ->execute();

            $connection->commit();
        } catch(\PDOException $e) {
            $connection->rollback();

            throw $e;
        }

        return $newUser;
    }

    public function updateUser($id, array $data, $password = null)
    {

        $qb = $this->miner->create();

        $fieldsTransform = [
            'cityId' => 'city_id',
            'phone' => 'telephone',
            'gender' => 'sex',
            'birthDate' => 'birthday',
        ];

        foreach($fieldsTransform as $key => $value){
            if(isset($data[$key])){
                $data[$value] = $data[$key];
                unset($data[$key]);
            }
        }

        $qb
            ->update('users');
        foreach(Arr::filterKeys($data,['email','username','name','lastname','city_id','telephone',
            'sex','birthday','reset_token','email_confirm']) as $key => $value){
            $qb->set($key,$value);
        }
            /*->set('email', $data['email'])
            ->set('name', $data['name'])
            ->set('lastname', $data['lastname'])
            ->set('city_id', $data['cityId'])
            ->set('telephone', $data['phone'])
            ->set('sex', $data['gender'])
            ->set('birthday', $data['birthDate']);



        if (isset($data['reset_token'])) {
            $qb->set('reset_token', $data['reset_token']);
        }*/
        if ($password) {
            $qb->set('password', $password);
        }

        $qb->where('id', $id);

        return (bool) $qb->execute();
    }

    public function changePassword($id, $password)
    {
        $qb = $this->miner->create();
        $qb->update('users')
            ->set('password', $password)
            ->where('id', $id);

        return (bool) $qb->execute();
    }


    public function activateUser($id)
    {
        $qb = $this->miner->create();
        $qb->update('users')
            ->set('email_confirm', 1)
            ->where('id', $id)
            ->execute();

        $qb = $this->miner->create();
        $qb->update('user_settings')
            ->set('subscribe', 1)
            ->where('user_id', $id)
            ->execute();

        return $this->addToSubscribeQueue($id);
    }

    private function addToSubscribeQueue($id)
    {
        $qb = $this->miner->create();
        $qb->insert('subscribe_queue')
            ->set('user_id', $id);
        return (bool)$qb->execute();
    }

    private function addToResubscribeQueue($id)
    {
        $qb = $this->miner->create();
        $qb->insert('resubscribe_queue')
            ->set('user_id', $id);
        return (bool)$qb->execute();
    }

    private function addToUnsubscribeQueue($email)
    {
        $qb = $this->miner->create();
        $qb->insert('unsubscribe_queue')
            ->set('email', $email);
        return (bool)$qb->execute();
    }

    public function activateNumber($phone, $id) {
        $qb = $this->miner->create();
        $qb->update('users')
            ->set('telephone', $phone)
            ->set('is_phone_activated', 1)
            ->where('id', $id);
        return (bool)$qb->execute();
    }

    public function setSubscribeSetting($userId, $data)
    {
        $currentUser = $this->findUserById($userId);
        // проверяем, если изменилось хотя бы одно из полей
        if (
            ($data['name'] !== $currentUser['name']) ||
            ($data['lastname'] !== $currentUser['lastname']) ||
            ((integer)$data['gender'] !== (integer)$currentUser['sex']) ||
            ($data['cityId'] !== $currentUser['city_id']) ||
            ($data['newOfferEveryday']) !== $this->isUserSubscribed($userId)
        ){
            $this->addToResubscribeQueue($userId);

            $qb = $this->miner->create();
            $qb->update('user_settings')
                ->set('subscribe', $data['newOfferEveryday'])
                ->where('user_id', $userId)
                ->execute();
        }
    }

    public function getResetTokenByEmail($email)
    {
        $qb = $this->miner->create();
        $qb
            ->select('users.*')
            ->from('users')
            ->where('users.email', $email);

        if (! $user = $qb->fetchOne() ){
            return false;
        }
        return $user['reset_token'];
    }

    public function getSocialNetworkAssociations($userId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('authtype_id', 'type')
            ->select('uid', 'uid')
            ->from('auths')
            ->where('user_id', $userId);

        return $qb->fetchAll();
    }

    public function getUserByOAuthUid($providerId, $uid)
    {
        $qb = $this->miner->create();
        $qb
            ->select('*')
            ->from('auths')
            ->leftJoin('users','users.id = auths.user_id')
            ->where('authtype_id', $providerId)
            ->where('uid',$uid);

        return $qb->fetchOne();
    }

    public function unassociateSocialNetwork($providerId, $userId)
    {
        $qb = $this->miner->create();
        $qb
            ->delete()
            ->from('auths')
            ->where('authtype_id', $providerId)
            ->andWhere('user_id', $userId);

        $qb->execute();
    }

    public function changeEmail($email, $userId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('email')
            ->from('users')
            ->where('id', $userId)
            ->execute();
        $result = $qb->fetchOne();
        $oldEmail = $result['email'];

        $this->addToUnsubscribeQueue($oldEmail);

        $qb = $this->miner->create();

        $qb
            ->update('users')
            ->set('email', $email)
            ->set('username', $email)
            ->set('email_confirm', 1);

        $qb->where('id', $userId);

        return (bool) $qb->execute();
    }

    public function isUserSubscribed($userId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('subscribe')
            ->from('user_settings')
            ->where('user_id', $userId)
            ->execute();
        $result = $qb->fetchOne();
        return (bool)$result['subscribe'];
    }

    public function getCouponCountForUser($userId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('COUNT(id)', 'count')
            ->from('coupons')
            ->where('user_id', $userId)
            ->execute();
        $result = $qb->fetchOne()['count'];
        return (int)$result;
    }

    public function getReviewCountForUser($userId)
    {
        $qb = $this->miner->create();
        $qb
            ->select('COUNT(id)', 'count')
            ->from('comments')
            ->where('user_id', $userId)
            ->where('is_approved', 1)
            ->where('deleted', 0)
            ->where('comment_type', 'deal')
            ->execute();
        $dealCommentCount = $qb->fetchOne()['count'];

        $qb = $this->miner->create();
        $qb
            ->select('COUNT(id)', 'count')
            ->from('places_feedbacks')
            ->where('user_id', $userId)
            ->where('level', 1)
            ->execute();
        $companyCommentCount = $qb->fetchOne()['count'];

        $result = (int)$dealCommentCount + (int)$companyCommentCount;
        return (int)$result;
    }
}
