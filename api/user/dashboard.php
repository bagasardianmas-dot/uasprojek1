<?php
require "../auth/middleware.php";

jsonResponse([
    "message" => "Welcome User",
    "user_id" => $currentUser['uid']
]);
