<?php

namespace App\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use App\Entity\User;
use Firebase\JWT\Key;

class JwtAuth{

    public $manager;
    public $key;

    public function __construct($manager){
        $this->manager = $manager ;
        $this->key = 'master_api_987641454545';
    }

    public function signup($email, $password, $gettoken = null)
    {
        //comprobar usuario existe
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup = false;

        if (is_object($user)) {
            $signup = true;
        }

        //generamos token
        if ($signup){

            $token = [
                'sub' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];

            //comprobar flag gettoken, condicional
            $jwt = JWT::encode($token, $this->key, 'HS256');

            if (!empty($gettoken)){
                $data = $jwt;
            }else{

                $decoded = JWT::decode($jwt, new Key($this->key, 'HS256') );
                $data = $decoded;
            }
        }else{

            $data = [
                'status' => 'error',
                'message' => 'not exist'
            ];
        }
        //devolverdatos
        return $data;
    }

    public function checktoken($jwt, $identity = false)
    {
        $auth = false;

        try {
            $decode = JWT::decode($jwt, new Key($this->key, "HS256"));
        }catch (\UnexpectedValueException $e){
            $auth = false;
        }catch (\DomainException $e){
            $auth = false;
        }


        if (!empty($decode) && isset($decode) && is_object($decode) && isset($decode->sub)) {
            $auth = true;
        }

        if ($identity != false){
            return $decode;
        }else{
            return $auth;
        }
    }
}