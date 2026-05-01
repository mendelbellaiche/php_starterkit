<?php

namespace Models;

use Core\Database;

class User
{
    private const PASSWORD_MIN_LENGTH = 12;

    private ?int $id = null;
    private string $name;
    private string $email;
    private string $password;
    private string $created_at;

    // --- GETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): string {
        return $this->created_at;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    // --- SETTERS (avec logique métier) ---

    public function setName(string $name): self
    {
        $this->name = htmlspecialchars(trim($name));
        return $this;
    }

    public function setEmail(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Format d'email invalide.");
        }
        $this->email = strtolower(trim($email));
        return $this;
    }

    public function setPassword(string $plainPassword, bool $shouldHash = true): self
    {
        // On hache le mot de passe seulement s'il ne l'est pas déjà (utile pour le fetchObject)
        if ($shouldHash) {
            self::validatePasswordComplexity($plainPassword);
            $this->password = password_hash($plainPassword, PASSWORD_BCRYPT);
        } else {
            $this->password = $plainPassword;
        }
        return $this;
    }

    // --- MÉTHODES DE PERSISTANCE (DATABASE) ---

    /**
     * Sauvegarde l'utilisateur (Insert ou Update)
     */
    public function save(): bool
    {
        $db = Database::getConnection();

        if ($this->id === null) {
            // INSERTION
            $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
        } else {
            // MISE À JOUR (Update)
            $stmt = $db->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id");
            $stmt->bindValue(':id', $this->id);
        }

        $stmt->bindValue(':name', $this->name);
        $stmt->bindValue(':email', $this->email);
        $stmt->bindValue(':password', $this->password);

        $success = $stmt->execute();

        // Si c'est une insertion, on récupère l'ID généré
        if ($success && $this->id === null) {
            $this->id = (int)$db->lastInsertId();
        }

        return $success;
    }

    /**
     * Trouve un utilisateur par son Email
     */
    public static function findByEmail(string $email): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        // Utilisation de FETCH_CLASS pour mapper directement sur cette classe
        $user = $stmt->fetchObject(self::class);

        return $user ?: null;
    }

    /**
     * Trouve un utilisateur par son ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);

        $user = $stmt->fetchObject(self::class);

        return $user ?: null;
    }

    // src/Models/User.php

    public function update(array $data): bool
    {
        $db = Database::getConnection();

        // Si un nouveau mot de passe est fourni, on le hache
        if (!empty($data['password'])) {
            $this->setPassword((string) $data['password']);
            $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
            return $stmt->execute([$data['name'], $data['email'], $this->password, $this->id]);
        }

        // Sinon, on met à jour uniquement le nom et l'email
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        return $stmt->execute([$data['name'], $data['email'], $this->id]);
    }

    private static function validatePasswordComplexity(string $password): void
    {
        $hasMinLength = strlen($password) >= self::PASSWORD_MIN_LENGTH;
        $hasUppercase = (bool) preg_match('/[A-Z]/', $password);
        $hasLowercase = (bool) preg_match('/[a-z]/', $password);
        $hasDigit = (bool) preg_match('/\d/', $password);
        $hasSpecial = (bool) preg_match('/[^a-zA-Z\d]/', $password);

        if (!$hasMinLength || !$hasUppercase || !$hasLowercase || !$hasDigit || !$hasSpecial) {
            throw new \Exception('Le mot de passe doit contenir au moins 12 caracteres, avec majuscule, minuscule, chiffre et caractere special.');
        }
    }
}