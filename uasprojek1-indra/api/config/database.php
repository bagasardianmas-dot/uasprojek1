<?php

class Database
{
    public function connect()
    {
        return new PDO(
            "mysql:host=localhost;dbname=db_api;charset=utf8",
            "root",
            "kusuma16",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }
}
