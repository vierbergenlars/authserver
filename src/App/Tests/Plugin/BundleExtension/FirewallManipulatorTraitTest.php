<?php
/**
 * Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) $today.date  Lars Vierbergen
 *
 * his program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Created by PhpStorm.
 * User: lars
 * Date: 23/08/17
 * Time: 22:32
 */

namespace App\Tests\Plugin\BundleExtension;

use App\Plugin\BundleExtension\FirewallManipulatorTrait;
use App\Plugin\Event\ContainerConfigEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class FirewallManipulatorTraitContainer {
    use FirewallManipulatorTrait;

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this, $name], $arguments);
    }
}
class FirewallManipulatorTraitTest extends TestCase
{
    private $manipulator;
    private $cceProphet;

    protected function setUp()
    {
        $this->manipulator = new FirewallManipulatorTraitContainer();
        $this->cceProphet = $this->prophesize(ContainerConfigEvent::class);
    }

    public function testGetFirewallConfig()
    {
        $conf = [
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ],
            'other' => [
                'abc' => 123
            ]
        ];
        $this->cceProphet->getConfig()->willReturn($conf);

        $this->assertEquals($conf['security']['firewalls'], $this->manipulator->getFirewallConfig($this->cceProphet->reveal()));
    }

    public function testGetFirewallConfigNoConfig()
    {
        $this->cceProphet->getConfig()->willReturn([]);
        $this->assertEquals([], $this->manipulator->getFirewallConfig($this->cceProphet->reveal()));
    }

    public function testSetFirewallConfig()
    {
        $conf = [
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ],
            'other' => [
                'abc' => 123
            ]
        ];
        $this->cceProphet->getConfig()->willReturn([
            'other' => $conf['other']
        ]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();
        $this->manipulator->setFirewallConfig($event, $conf['security']['firewalls']);

        $this->assertEquals($conf, $event->getConfig());
    }

    public function testAddFirewall()
    {
        $this->cceProphet->getConfig()->willReturn([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ]
        ]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->addFirewall($event, [
            'fw3' => [
                'xyz' => 123
            ],
            'fw4' => [
                'mno' => 897,
            ],
        ]);

        $this->assertEquals([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ],
                    'fw3' => [
                        'xyz' => 123
                    ],
                    'fw4' => [
                        'mno' => 897,
                    ],
                ]
            ]
        ], $event->getConfig());
    }

    public function testAddFirewallBefore()
    {
        $this->cceProphet->getConfig()->willReturn([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ]
        ]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->addFirewall($event, [
            'fw3' => [
                'xyz' => 123
            ],
            'fw4' => [
                'mno' => 897,
            ],
        ], true);

        $this->assertEquals([
            'security' => [
                'firewalls' => [
                    'fw3' => [
                        'xyz' => 123
                    ],
                    'fw4' => [
                        'mno' => 897,
                    ],
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ],
                ]
            ]
        ], $event->getConfig());
    }

    public function testAddFirewallBeforeNamed()
    {
        $this->cceProphet->getConfig()->willReturn([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ]
        ]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->addFirewall($event, [
            'fw3' => [
                'xyz' => 123
            ],
            'fw4' => [
                'mno' => 897,
            ],
        ], 'fw2');

        $this->assertEquals([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw3' => [
                        'xyz' => 123
                    ],
                    'fw4' => [
                        'mno' => 897,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ],
                ]
            ]
        ], $event->getConfig());
    }

    public function testAddFirewallBeforeNamedNonExisting()
    {
        $this->cceProphet->getConfig()->willReturn([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ]
                ]
            ]
        ]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->addFirewall($event, [
            'fw3' => [
                'xyz' => 123
            ],
            'fw4' => [
                'mno' => 897,
            ],
        ], 'xyz');

        $this->assertEquals([
            'security' => [
                'firewalls' => [
                    'fw1' => [
                        'security' => false,
                    ],
                    'fw2' => [
                        'http_basic' => null,
                    ],
                    'fw3' => [
                        'xyz' => 123
                    ],
                    'fw4' => [
                        'mno' => 897,
                    ],
                ]
            ]
        ], $event->getConfig());
    }
}
