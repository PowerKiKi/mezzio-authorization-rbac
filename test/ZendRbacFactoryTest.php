<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-authorization-rbac for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-authorization-rbac/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Authorization\Rbac;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Authorization\Exception;
use Zend\Expressive\Authorization\Rbac\ZendRbac;
use Zend\Expressive\Authorization\Rbac\ZendRbacFactory;
use Zend\Expressive\Authorization\Rbac\ZendRbacAssertionInterface;

class ZendRbacFactoryTest extends TestCase
{
    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig()
    {
        $this->container->get('config')->willReturn([]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutZendRbacConfig()
    {
        $this->container->get('config')->willReturn(['authorization' => []]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutPermissions()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => []
            ]
        ]);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithEmptyRolesPermissionsWithoutAssertion()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [],
                'permissions' => []
            ]
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithEmptyRolesPermissionsWithAssertion()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [],
                'permissions' => []
            ]
        ]);

        $assertion = $this->prophesize(ZendRbacAssertionInterface::class);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(true);
        $this->container->get(ZendRbacAssertionInterface::class)->willReturn($assertion->reveal());

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithoutAssertion()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    'administrator' => [],
                    'editor'        => ['administrator'],
                    'contributor'   => ['editor'],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ],
                    'editor' => [
                        'admin.publish',
                    ],
                    'administrator' => [
                        'admin.settings',
                    ],
                ],
            ],
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithAssertion()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    'administrator' => [],
                    'editor'        => ['administrator'],
                    'contributor'   => ['editor'],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ],
                    'editor' => [
                        'admin.publish',
                    ],
                    'administrator' => [
                        'admin.settings',
                    ],
                ],
            ],
        ]);
        $assertion = $this->prophesize(ZendRbacAssertionInterface::class);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(true);
        $this->container->get(ZendRbacAssertionInterface::class)->willReturn($assertion->reveal());

        $factory = new ZendRbacFactory();
        $zendRbac = $factory($this->container->reveal());
        $this->assertInstanceOf(ZendRbac::class, $zendRbac);
    }

    public function testFactoryWithInvalidRole()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    1 => [],
                ],
                'permissions' => [],
            ],
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithUnknownRole()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    'administrator' => [],
                ],
                'permissions' => [
                    'contributor' => [
                        'admin.dashboard',
                        'admin.posts',
                    ]
                ]
            ]
        ]);
        $this->container->has(ZendRbacAssertionInterface::class)->willReturn(false);

        $factory = new ZendRbacFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }
}
