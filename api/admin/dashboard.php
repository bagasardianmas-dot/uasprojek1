<?php
require "../auth/middleware.php";

if ($currentUser['role'] !== 'admin') {
    jsonResponse(["message" => "Access denied"], 403);
}

jsonResponse([
    "message" => "Welcome Admin",
    "user_id" => $currentUser['uid']
]);
