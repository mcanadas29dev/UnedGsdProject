<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface as GoogleTwoFactorInterface;
//use Scheb\TwoFactorBundle\Model\BackupCode\BackupCodeInterface;
//use Scheb\TwoFactorBundle\Model\BackupCodeInterface;

##[ORM\Table(name: "app_user")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, GoogleTwoFactorInterface //, BackupCodeInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // ------------------------
    // Campos 2FA
    // ------------------------
    #[ORM\Column(type: 'boolean')]
    private bool $isGoogleAuthenticatorEnabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleAuthenticatorSecret = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $backupCodes = [];

    // ------------------------
    // Métodos existentes
    // ------------------------
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated
    }

    // ------------------------
    // Métodos Google Authenticator
    // ------------------------

    public function getGoogleAuthenticatorUsername(): string
    {
         return (string) $this->getUserIdentifier(); 
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->isGoogleAuthenticatorEnabled;
    }

    public function setGoogleAuthenticatorEnabled(bool $enabled): static
    {
        $this->isGoogleAuthenticatorEnabled = $enabled;
        return $this;
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $secret): static
    {
        $this->googleAuthenticatorSecret = $secret;
        return $this;
    }

    // ------------------------
    // Métodos Backup Codes
    // ------------------------
    public function getBackupCodes(): array
    {
        return $this->backupCodes;
    }

    public function setBackupCodes(array $codes): static
    {
        $this->backupCodes = $codes;
        return $this;
    }

    public function isBackupCode(string $code): bool
    {
        return in_array($code, $this->backupCodes, true);
    }

    public function markBackupCodeAsUsed(string $code): static
    {
        $this->backupCodes = array_filter($this->backupCodes, fn($c) => $c !== $code);
        return $this;
    }

    public function invalidateBackupCode(string $code): void
    {
        $this->backupCodes = array_filter($this->backupCodes, fn($c) => $c !== $code);
    }
}
