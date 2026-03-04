<?php

declare(strict_types=1);

namespace Application\GraphQl\Types;

use Domain\Repositories\LanguagesRepository;
use Domain\Repositories\MenuItemsRepository;
use Domain\Repositories\MenuSectionsRepository;
use Domain\Repositories\OrdersRepository;
use Domain\Repositories\ReservationsRepository;
use Domain\Repositories\StationsRepository;
use Domain\Repositories\SuppliesRepository;
use Domain\Repositories\TablesRepository;
use Domain\Repositories\UsersRepository;
use Domain\Repositories\MenusRepository;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AdminQueryType extends ObjectType
{
    public function __construct()
    {
    	$config = [
            'name' => 'Query',
            'fields' => [
                'activeMenus' => [
                    'type' => Type::listOf(Types::menu()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(MenusRepository::class)
                        	->findBy(['isActive' => true]);
                    }
                ],
                'menuSections' => [
                    'type' => Type::listOf(Types::menuSection()),
                    'args' => [
                		'menu' => Type::nonNull(Type::string())
                	],
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        $menu  = $context->get(MenusRepository::class)
                            ->findOneBy(['name' => $args['menu']]);
                        
                        if ($menu != null) {
                            return $context
                                ->get(MenuSectionsRepository::class)
                                ->findBy(
                                    ['isActive' => true, 'menu' => $menu], 
                                    ['position' => 'asc']
                                );
                        } else {
                            return [];
                        }
                    }
                ],
                'menuItems' => [
                    'type' => Type::listOf(Types::menuItem()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(MenuItemsRepository::class)
                        	->findBy(['isActive' => true]);
                    }
                ],
                'tables' => [
                    'type' => Type::listOf(Types::table()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(TablesRepository::class)
                        	->findBy(['isActive' => true], ['name' => 'asc']);
                    }
                ],
                'availableTables' => [
                    'type' => Type::listOf(Types::table()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        $activeTables = $context
                        	->get(TablesRepository::class)
                        	->findBy(['isActive' => true], ['name' => 'asc']);

                       	$orders = $context
                       		->get(OrdersRepository::class)
                       		->findBy(['status' => 'OPEN']);

                       	foreach ($orders as $order) {
                       		//$activeTables->removeElement($order->getTable());
                       		$activeTables = array_filter($activeTables, function ($v) use ($order) {
                       			return $v != $order->getTable();
                       		});
                       	}

                       	return $activeTables;
                    }
                ],
                'stations' => [
                    'type' => Type::listOf(Types::station()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(StationsRepository::class)
                        	->findBy(['isActive' => true], ['name' => 'asc']);
                    }
                ],
                'order' => [
                	'type' => Types::order(),
                	'args' => [
                		'id' => Type::nonNull(Type::id())
                	]
                ],
                'languages' => [
                	'type' => Type::listOf(Types::language()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(LanguagesRepository::class)
                        	->findAll();
                    }
                ],
                'supplies' => [
                	'type' => Type::listOf(Types::supply()),
                	'resolve' => function ($rootValue, $args, $context, $info) {
                        return $context
                        	->get(SuppliesRepository::class)
                        	->findBy([], ['name' => 'asc']);
                    }
                ],
                'employees' => [
                    'type' => Type::listOf(Types::user()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        $users = $context->get(UsersRepository::class)->findBy(['isActive' => true], ['fullName' => 'asc']);
                        $employees = array_filter($users, fn($user) => $user->isEmployee());

                        return $employees;
                    }
                ],
                'activeUser' => [
                    'type' => Types::user(),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        //$users = $context->get(UsersRepository::class)->findBy(['isActive' => true], ['fullName' => 'asc']);
                        //$employees = array_filter($users, fn($user) => $user->isEmployee());

                        return $_SESSION['user'];
                    }
                ],
                'activeOrders' => [
                	'type' => Type::listOf(Types::order()),
                	'resolve' => function ($rootValue, $args, $context, $info) {
                        $orders = $context->get(OrdersRepository::class)->findBy(['status' => 'OPEN'], ['createdAt' => 'desc']);
                        //$employees = array_filter($users, fn($user) => $user->isEmployee());

                        return $orders;
                    }
                ],
                'todaysReservations' => [
                    'type' => Type::listOf(Types::reservation()),
                    'resolve' => function ($rootValue, $args, $context, $info) {
                        $todaysReservations = $context->get(ReservationsRepository::class)->findByDate(new \Datetime);

                        return $todaysReservations;
                    }
                ]
            ],
            'resolveField' => function ($rootValue, $args, $context, $info) {
                $type = sprintf("\\Application\\GraphQl\\Types\\%sType", ucfirst($info->fieldName));

                return (new $type)->resolveType($rootValue, $args, $context, $info);
            }
        ];

        parent::__construct($config);
    }
}