<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'FBWatch\Controller\FBWatch' => 'FBWatch\Controller\FBWatchController',
        ),
    ),
    
    'router' => array(
        'routes' => array(
//            'home' => array(
//                'type' => 'Zend\Mvc\Router\Http\Literal',
//                'options' => array(
//                    'route'    => '/',
//                    'defaults' => array(
//                        'controller' => 'FBWatch\Controller\FBWatch',
//                        'action'     => 'index',
//                    ),
//                ),
//            ),
            'fbwatch' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/[:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'FBWatch\Controller\FBWatch',
                        'action'     => 'index',
                    ),
                ),
            )
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            'fbwatch' => __DIR__ . '/../view',
        ),
    ),
);