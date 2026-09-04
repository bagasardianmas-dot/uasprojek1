<?php

class User
{
    private $conn;
    private $table = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    //get user by username
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1"
        );
        $stmt->execute(["username" => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    // CREATE
    public function create($username, $password, $role)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO {$this->table} (username, password, role)
                VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$username, $hash, $role]);
    }

    // READ ALL
    public function getAll()
    {
        $sql = "SELECT id, username, role, created_at FROM {$this->table}";
        return $this->conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ BY ID
    public function getById($id)
    {
        $sql = "SELECT id, username, role FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function update($id, $username, $role)
    {
        $sql = "UPDATE {$this->table}
                SET username = ?, role = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$username, $role, $id]);
    }

    // UPDATE PASSWORD
    public function updatePassword($id, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$hash, $id]);
    }

    // DELETE
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }
}
