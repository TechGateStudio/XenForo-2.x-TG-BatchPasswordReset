<?php

namespace TG\BatchPasswordReset\XF\Job;


class UserAction extends XFCP_UserAction
{
    /**
     * @param \XF\Entity\User $user
     * @throws \XF\PrintableException
     */
    protected function applyExternalUserChange(\XF\Entity\User $user)
    {
        if ($this->getActionValue('reset_passwords'))
        {
            if ($this->getActionValue('invalidate_passwords'))
            {
                if ($user->email != '')
                {
                    \XF::app()->mailer()
                        ->newMail()
                        ->setToUser($user)
                        ->setTemplate('tg_bpr_user_password_invalidate', ['user' => $user])
                        ->send();

                    /** @var \XF\Entity\UserAuth $userAuth */
                    $userAuth = $user->getRelationOrDefault('Auth');
                    $userAuth->setNoPassword();
                    $userAuth->save();
                }
                else
                {
                    \XF::logError("User '{$user->username} doesn't have email; ignoring");
                }
            }
            else
            {
                /** @var \XF\Service\User\PasswordReset $resetService */
                $resetService = \XF::service('XF:User\PasswordReset', $user);
                if (!$resetService->canTriggerConfirmation())
                {
                    \XF::logError("Cannot reset '{$user->username}`s password; ignoring'");
                }
                else
                {
                    $resetService->setAdminReset(true);
                    $resetService->triggerConfirmation();
                }

            }
        }

        parent::applyExternalUserChange($user);
    }
}