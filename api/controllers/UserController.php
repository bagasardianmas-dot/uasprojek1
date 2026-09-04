<?php
require_once "../models/User.php";
require_once "../helpers/response.php";

class UserController
{
    private $user;

    public function __construct($db)
    {
        $this->user = new User($db);
    }

    public function index()
    {
        jsonResponse($this->user->getAll());
    }

    public function show($id)
    {
        $data = $this->user->getById($id);
        $data
            ? jsonResponse($data)
            : jsonResponse(["message" => "User not found"], 404);
    }

    public function store($data)
    {
        if (!$data['username'] || !$data['password'] || !$data['role']) {
            jsonResponse(["message" => "Invalid data"], 422);
        }

        $this->user->create(
            $data['username'],
            $data['password'],
            $data['role']
        );

        jsonResponse(["message" => "User created"]);
    }

    public function update($id, $data)
    {
        $this->user->update($id, $data['username'], $data['role']);
        jsonResponse(["message" => "User updated"]);
    }

    public function updatePassword($id, $data)
    {
        $this->user->updatePassword($id, $data['password']);
        jsonResponse(["message" => "Password updated"]);
    }

    public function destroy($id)
    {
        $this->user->delete($id);
        jsonResponse(["message" => "User deleted"]);
    }
}
