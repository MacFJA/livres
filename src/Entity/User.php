<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace App\Entity;

use App\Repository\UserRepository;
use function array_unique;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface
{
    public const ROLES = [
        'Admin (include role editor)' => self::ROLE_ADMIN,
        'Editor (include role user, ability to add, edit, remove)' => self::ROLE_EDITOR,
        'User (include ability to view)' => self::ROLE_USER,
        'Ability to remove' => self::ROLE_CAN_DELETE,
        'Ability to edit' => self::ROLE_CAN_EDIT,
        'Ability to add' => self::ROLE_CAN_ADD,
        'Ability to view' => self::ROLE_CAN_VIEW,
    ];

    public const ROLE_USER = 'ROLE_USER';

    public const ROLE_EDITOR = 'ROLE_EDITOR';

    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const ROLE_CAN_EDIT = 'ROLE_CAN_EDIT';

    public const ROLE_CAN_ADD = 'ROLE_CAN_ADD';

    public const ROLE_CAN_DELETE = 'ROLE_CAN_DELETE';

    public const ROLE_CAN_VIEW = 'ROLE_CAN_VIEW';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $userId;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(type="json")
     * @Assert\Choice(choices=App\Entity\User::ROLES)
     *
     * @var array<string>
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $password;

    public function __toString()
    {
        return $this->getUsername();
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
