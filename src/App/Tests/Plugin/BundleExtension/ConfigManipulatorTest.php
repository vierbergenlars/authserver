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
 * Date: 24/08/17
 * Time: 8:05
 */

namespace App\Tests\Plugin\BundleExtension;

use App\Plugin\BundleExtension\ConfigManipulator;
use App\Plugin\Event\ContainerConfigEvent;
use Prophecy\Argument;

class ConfigManipulatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManipulator
     */
    private $manipulator;
    private $cceProphet;

    protected function setUp()
    {
        $this->cceProphet = $this->prophesize(ContainerConfigEvent::class);
        $this->manipulator = new ConfigManipulator($this->cceProphet->reveal(), '[security][firewalls]');
    }

    public function testGetConfig()
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

        $this->assertEquals($conf['security']['firewalls'], $this->manipulator->getConfig());
    }

    public function testGetConfigNoConfig()
    {
        $this->cceProphet->getConfig()->willReturn([]);
        $this->assertEquals(null, $this->manipulator->getConfig());
    }

    public function testSetConfig()
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

        $this->manipulator->setConfig($conf['security']['firewalls']);

        $this->assertEquals($conf, $this->cceProphet->reveal()->getConfig());
    }

    public function testAppendConfig()
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

        $this->manipulator->appendConfig([
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
        ], $this->cceProphet->reveal()->getConfig());
    }

    public function testPrependConfig()
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

        $this->manipulator->prependConfig([
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

    public function testAppendConfigNamed()
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

        $this->manipulator->appendConfig([
            'fw3' => [
                'xyz' => 123
            ],
            'fw4' => [
                'mno' => 897,
            ],
        ], 'fw1');

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

    public function testPrependConfigNamed()
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

        $this->manipulator->prependConfig([
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

    public function testAppendConfigNamedNonExisting()
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

        $this->manipulator->appendConfig([
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

    public function testPrependConfigNamedNonExisting()
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

        $this->manipulator->prependConfig([
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

    public function testAppendConfigEmptyConfig()
    {
        $this->cceProphet->getConfig()->willReturn([]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->appendConfig([
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

    public function testPrependConfigEmptyConfig()
    {
        $this->cceProphet->getConfig()->willReturn([]);
        $this->cceProphet->setConfig(Argument::type('array'))->will(function($args) {
            $this->getConfig()->willReturn($args[0]);
        })->shouldBeCalled();

        $event = $this->cceProphet->reveal();

        $this->manipulator->prependConfig([
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
