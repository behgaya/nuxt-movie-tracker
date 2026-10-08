<?php

namespace App\Security;

use App\Entity\Folder;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may do what with a folder. Used by #[IsGranted('VIEW', 'folder')] in FolderController.
 * VIEW: the owner, or anyone (even logged out) when the folder is public. EDIT: only the owner.
 *
 * @extends Voter<string, Folder>
 */
final class FolderVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof Folder;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        $isOwner = $user instanceof User && $user === $subject->getOwner();

        return match ($attribute) {
            self::VIEW => $isOwner || $subject->isPublic(),
            self::EDIT => $isOwner,
        };
    }
}
