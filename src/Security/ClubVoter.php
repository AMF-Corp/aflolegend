<?php

namespace App\Security;

use App\Entity\Club;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'view';
    const EDIT = 'edit';

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT])) {
            return false;
        }

        // only vote on `Post` objects
        if (!$subject instanceof Club) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to `supports()`
        /** @var Club $club */
        $club = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($club, $user),
            self::EDIT => $this->canEdit($club, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Club $club, User $user): bool
    {
        // if they can edit, they can view
        if (!$user instanceof User) {
            return true;
        }

        // the Post object could have, for example, a method `isPrivate()`
        return false;
    }

    private function canEdit(Club $club, User $user): bool
    {
        // si le club a été creé par l'utilisateur ou que l'utilisateut  est admin
        return $user === $club->getUser() || $this->security->isGranted['ROLE_ADMIN'];
    }
}
