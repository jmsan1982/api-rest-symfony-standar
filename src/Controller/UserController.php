<?php

namespace App\Controller;

use App\Services\JwtAuth;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

class UserController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;
        $this->serializer = new Serializer();
    }

    public function index(): JsonResponse
    {

    }

    public function create(Request $request)
    {
        //recojo datos
        $json = $request->get('json', null);
        //decodifico json
        $params = json_decode($json);

        //respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => Response::HTTP_OK,
            'message' => 'Error, User no created',
        ];

        //comprobar y validacion
        if (!empty($json) && !is_null($json)) {
            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $role = (!empty($params->role)) ? $params->role : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if (!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($role) && !empty($name) && !empty($surname)) {

                //si validacion true, creacion usuario object
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole($role);
                $user->setCreatedAt(new \DateTime('now'));

                //cifrar contrasseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                //comprobar si existe user(duplicados)
                $em = $this->entityManager;

                $user_repo = $this->entityManager->getRepository(User::class);
                $isser_user = $user_repo->findBy(array(
                    'email' => $email
                ));

                if (count($isser_user) == 0) {
                    //si no existe, guardo
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        'status' => 'succes',
                        'code' => Response::HTTP_OK,
                        'message' => 'User created',
                        'user' => $user
                    ];
                }else{
                    $data = [
                        'status' => 'error',
                        'code' => Response::HTTP_BAD_REQUEST,
                        'message' => 'Error, User exist',
                    ];
                }
            }

        }

        //respuesta
        return new JsonResponse($data);
    }

    public function login(Request $request, JwtAuth $jwt_auth)
    {
        //recibir los datos por post
        $json = $request->get('json', null);
        $params = json_decode($json);

        //array por defecto para devolver
        $data = [
          'status' => 'error',
          'code' => Response::HTTP_OK,
          'message' => 'Error, User not found',
        ];
        //comprobar y validar
        if (!empty($json) && !is_null($json)) {
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $gettoken = (!empty($params->gettoken)) ? $params->gettoken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
               new Email()
            ]);

            if (!empty($email) && count($validate_email) == 0 && !empty($password)) {


                //cifrar contraseña
                $pwd = hash('sha256', $password);

                //si es valido identificamos
                if ($gettoken) {
                    $data = [
                        'status' => 'error',
                        'code' => Response::HTTP_OK,
                        'message' => 'entra if ',
                    ];
                    $signup = $jwt_auth->signup($email, $pwd, $gettoken);
                }else{
                    $data = [
                        'status' => 'error',
                        'code' => Response::HTTP_OK,
                        'message' => 'despues entra else ',
                    ];
                    $signup = $jwt_auth->signup($email, $pwd);
                }
                return new JsonResponse($signup);

            }
        }

        return new JsonResponse($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth)
    {
        //recoger cabecera auth
        $token = $request->headers->get('Authorization');
        //crear metodo comprobacion token correcto
        $authCheck = $jwt_auth->checkToken($token);

        $data = [
            'status' => 'error',
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Error, User not found',
        ];

        //si es correcto hacer actualizacion usuario
        if ($authCheck){
            //actualizar usuario

            //entity manager
            $em = $this->entityManager;

            //datos user auth
            $identity = $jwt_auth->checktoken($token, true);

            //usuario actu complet
            $user_repo = $this->entityManager->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);

            //recogemos datos post
            $json = $request->get('json', null);
            $params = json_decode($json);

            //validar datos
            if (!empty($json) && !is_null($json)) {
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if (!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)) {
                    //asignar datos usuario
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setEmail($email);

                    //comprobar duplicados
                    $isser_user = $user_repo->findBy([
                        'email' => $email
                    ]);

                    if (count($isser_user) == 0 || $identity->email == $email) {
                        //guardar cambios
                        $em->persist($user);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => Response::HTTP_OK,
                            'message' => 'user update',
                            'user' => $user
                        ];

                    }else{
                        $data = [
                            'status' => 'success',
                            'code' => Response::HTTP_BAD_REQUEST,
                            'message' => 'user not identified',
                        ];
                    }


                }
            }

        }

        return new JsonResponse($data);
    }
}
