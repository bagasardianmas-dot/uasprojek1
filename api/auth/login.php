<?php
require "../vendor/autoload.php";
require "../config/database.php";
require "../config/jwt.php";
require "../helpers/response.php";
require "../models/User.php";

use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (!$username || !$password) {
    jsonResponse(["message" => "Username & password masih kosong"], 422);
}

// DB Connection
$db = (new Database())->connect();
//object
$userModel = new User($db);

$user = $userModel->findByUsername($username);

if (!$user) {
    jsonResponse(["message" => "User not found"], 401);
}

if (!password_verify($password, $user['password'])) {
    jsonResponse(["message" => "Wrong password"], 401);
}

// Payload JWT
$payload = [
    "iss"  => JWT_ISSUER,
    "aud"  => JWT_AUDIENCE,
    "iat"  => time(),
    "exp"  => time() + JWT_EXPIRE,
    "uid"  => $user['id'],
    "role" => $user['role']
];

// Generate token
$token = JWT::encode($payload, JWT_SECRET, 'HS256');

jsonResponse([
    "message" => "Login success",
    "token"   => $token,
    "role"    => $user['role']
]);
