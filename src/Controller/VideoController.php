<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class VideoController extends AbstractController
{
    private $entityManager;
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer){
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/VideoController.php',
        ]);
    }

    public function create(Request $request, JwtAuth $jwt_auth, $id = null): JsonResponse
    {
        $data = [
            'status' => 'error',
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'video no created',
        ];

        //recoger token
        $token = $request->headers->get('Authorization');

        //comprobar si es correcto
        $authChecker = $jwt_auth->checkToken($token);
        if($authChecker){
            //datos post
            $json = $request->get('json', null);
            $params = json_decode($json);

            //objeto user
            $identity = $jwt_auth->checkToken($token, true);

            //validar datos
            if (!empty($json) && !is_null($json)) {
                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (!empty($params->title)) ? $params->title : null;
                $description = (!empty($params->description)) ? $params->description : null;
                $url = (!empty($params->url)) ? $params->url : null;

                if (!empty($title) && !empty($user_id)) {
                    //video nuevo guardado

                    $em = $this->entityManager;
                    $user = $this->entityManager->getRepository(User::class)->findOneBy([
                        'id' => $user_id,
                    ]);

                    //creo y guardo
                    if ($id == null) {
                        $video = new Video();
                        $video->setUser($user);
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);
                        $video->setStatus('normal');
                        $createdAt = new \DateTime('now');
                        $updatedAt = new \DateTime('now');
                        $video->setCreatedAt($createdAt);
                        $video->setUpdatedAt($updatedAt);

                        //guardar
                        $em->persist($video);
                        $em->flush();

                        $data = [
                            'status' => 'succes',
                            'code' => Response::HTTP_OK,
                            'message' => 'video guardado correctamente',
                            'video' => $video,
                        ];
                    }else{
                        $video = $this->entityManager->getRepository(Video::class)->findOneBy([
                           'id' => $id,
                           'user' => $identity->sub,
                        ]);
                        if ($video && is_object($video)) {
                            $video->setTitle($title);
                            $video->setDescription($description);
                            $video->setUrl($url);
                            $updatedAt = new \DateTime('now');
                            $video->setUpdatedAt($updatedAt);

                            $em->persist($video);
                            $em->flush();

                            $data = [
                                'status' => 'succes',
                                'code' => Response::HTTP_OK,
                                'message' => 'video actualizado correctamente',
                                'video' => $video,
                            ];
                        }
                    }
                }
            }
        }

        //devolver respuesta

        return new JsonResponse($data);
    }

    public function videos(Request $request, JwtAuth $jwt_auth, PaginatorInterface $paginator): JsonResponse
    {
        $data = [
            'status' => 'error',
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'no videos',

        ];

        //cabecera auth
        $token = $request->headers->get('Authorization');

        //comprobar token

        $authChecker = $jwt_auth->checkToken($token);

        //si es valido

        if ($authChecker){
            //conseguir identidad user
            $idetity = $jwt_auth->checkToken($token, true);

            $em = $this->entityManager;

            //consulta a paginar
            /*$dql = "SELECT v FROM App\Entity\Video v WHERE v.user = $idetity->sub ORDER BY v.id DESC";
            $query = $em->createQuery($dql);*/
            $videosQuery = $em->getRepository(Video::class)->createQueryBuilder('v')
                ->where('v.user = :user')
                ->setParameter('user', $idetity->sub)
                ->orderBy('v.id', 'DESC')
                ->getQuery();

            //recoger parametro page de la url
            $page = $request->query->getInt('page', 1);
            $items_per_page = 6;

            //invoccar paginacion
            $pagination = $paginator->paginate($videosQuery, $page, $items_per_page);

            $total = $pagination->getTotalItemCount();

            //array auth comprobacion
            $data = [
                'status' => 'success',
                'code' => Response::HTTP_OK,
                'total_item_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'videos' => $pagination,
                'user' => $idetity->sub,
            ];
        }

        return new JsonResponse($data);
    }

    public function detail(Request $request, JwtAuth $jwt_auth, $id = null): JsonResponse
    {
        $data = [
            'status' => 'error',
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'video no found',
            'id' => $id
        ];
        //sacar token
        $token = $request->headers->get('Authorization');
        $authChecker = $jwt_auth->checkToken($token);

        if ($authChecker){
            //sacar identidad usuario
            $identity = $jwt_auth->checkToken($token, true);
            $user = $this->entityManager->getRepository(User::class)->find($identity->sub);

            //sacar objeto video
            $videoObject = $this->entityManager->getRepository(Video::class)->findOneBy([
                'id' => $id,
                'user' => $user,
            ]);

            $video = $this->serializer->serialize($videoObject, 'json');

            //comprobar si video existe y del usuario identificado
            if ($videoObject && is_object($videoObject)) {
                $data = [
                    'status' => 'success',
                    'code' => Response::HTTP_OK,
                    'video' => json_decode($video, true)
                ];
            }

        }

        //devolver respuesta
        return new JsonResponse($data);
    }

    public function remove(Request $request, JwtAuth $jwt_auth, $id = null): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        $authChecker = $jwt_auth->checkToken($token);

        $data = [
            'status' => 'error',
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'video no remove',

        ];

        if ($authChecker){
            $identity = $jwt_auth->checkToken($token, true);
            $em = $this->entityManager;
            $video = $this->entityManager->getRepository(Video::class)->findOneBy([
               'id' => $id,
            ]);

            if ($video && is_object($video) && $identity->sub == $video->getUser()->getId()) {
                $em->remove($video);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => Response::HTTP_OK,
                    'message' => 'video remove',

                ];
            }
        }

        return new JsonResponse($data);
    }

}
