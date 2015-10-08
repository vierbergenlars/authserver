<?php

namespace Admin\Tests\Controller;

use Admin\Entity\ApiKey;
use App\Entity\EmailAddress;
use App\Entity\Group;
use App\Entity\Property;
use App\Entity\User;
use App\Entity\UserProperty;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\UuidGenerator;
use FOS\RestBundle\Util\Codes;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\Client;

class UserControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        static::bootKernel();
        unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        $cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        if($cliApp->run(new StringInput('doctrine:migrations:migrate --no-interaction'), new NullOutput()))
            throw new \RuntimeException('Could not run the migrations.');

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */

        $groups = array();
        /* @var $groups Group[] */
        for($i=0;$i<0x40;$i++) {
            $group = new Group();
            $groups[] = $group;
            $group->setName('group_'.$i);
            $group->setDisplayName('DisplayName '.$i);
            $group->setNoGroups($i%0x2 == 1);
            $group->setExportable($i%0x4 == 1);
            $group->setNoUsers($i%0x8 == 1);
            $group->setUserJoinable($i%0x10 == 1);
            $group->setUserLeaveable($i%0x20 == 1);
            $em->persist($group);
        }

        $groups[0]->addGroup($groups[1]);
        $groups[4]->addGroup($groups[3]);
        $groups[5]->addGroup($groups[3]);
        $groups[5]->addGroup($groups[6]);
        $groups[5]->addGroup($groups[7]);
        $groups[5]->addGroup($groups[8]);


        $users = array();
        /* @var $users User[] */
        $guidGenerator = new UuidGenerator();

        for($i=0;$i<0xf0;$i++) {
            $user = new User();
            $users[] = $user;
            $user->setGuid('00000000-0000-0000-0000-'.str_pad((string)$i, 12, '0', STR_PAD_LEFT));
            $user->setUsername('user_'.$i);
            $user->setDisplayName('User '.$i);
            $user->setEnabled($i%0x2 == 0);
            $user->setRole($i%0x4?'ROLE_USER':$i%0x8 == 0?'ROLE_ADMIN':'ROLE_SUPER_ADMIN');
            $user->setPasswordEnabled($i%0xf == 0?0:$i%0x10?1:2);
            $user->addEmailAddress(new EmailAddress());
            $user->getPrimaryEmailAddress()->setEmail($i.'@example.invalid');
            $user->getPrimaryEmailAddress()->setUser($user);
            if($i%0x20 == 0)
                $user->getPrimaryEmailAddress()->setVerified(true);
            if($i%0x40) {
                $e = new EmailAddress();
                $user->addEmailAddress($e);
                $e->setUser($user);
                $e->setEmail($i.'@sec.invalid');
                if($i%0x80 == 0)
                    $e->setVerified(true);
            }
            $em->persist($user);
        }

        $users[0]->addGroup($groups[3]);
        $users[1]->addGroup($groups[3]);
        $users[2]->addGroup($groups[4]);
        $users[3]->addGroup($groups[4]);
        $users[4]->addGroup($groups[4]);
        $users[5]->addGroup($groups[5]);
        $users[6]->addGroup($groups[6]);
        $users[7]->addGroup($groups[6]);
        $users[8]->addGroup($groups[6])->addGroup($groups[4])->addGroup($groups[5]);
        $users[9]->addGroup($groups[6])->addGroup($groups[4]);

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setScopes(array('r_profile_email', 'w_profile_groups', 'w_profile_email', 'w_profile_enabled_admin', 'w_profile_cred', 'w_profile_username', 'w_profile_admin'));
        $em->persist($apiKey);
        $em->flush();

        copy(self::$kernel->getRootDir().'/test_db.sqlite', self::$kernel->getRootDir().'/test_db.sqlite.sav');
    }


    public function setUp()
    {
        static::bootKernel();
        copy(self::$kernel->getRootDir().'/test_db.sqlite.sav', self::$kernel->getRootDir().'/test_db.sqlite');
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $apiKey = $this->em->find('AdminBundle:ApiKey', 1);
        $this->client = self::createClient(array(), array(
            'PHP_AUTH_USER' => '-apikey-'.$apiKey->getId(),
            'PHP_AUTH_PW' => $apiKey->getSecret(),
            'HTTP_ACCEPT' => 'application/json',
        ));
    }

    
    public function testCget()
    {
        $this->client->request('GET', '/admin/users');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(0xf0, $data->total);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('/admin/users?page=2', $data->_links->next->href);
        $this->assertEquals('user_239', $data->items[0]->username);
        $this->assertEquals('User 239', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000239', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users?page=2');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(2, $data->page);
        $this->assertEquals(0xf0, $data->total);
        $this->assertEquals('/admin/users?page=1', $data->_links->prev->href);
        $this->assertEquals('/admin/users?page=3', $data->_links->next->href);
        $this->assertEquals('user_229', $data->items[0]->username);
        $this->assertEquals('User 229', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000229', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users?per_page=20');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(20, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(0xf0, $data->total);
        $this->assertEquals('/admin/users?per_page=20&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('user_239', $data->items[0]->username);
        $this->assertEquals('User 239', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000239', $data->items[0]->_links->self->href);
    }

    public function testCgetSearch()
    {
        $this->client->request('GET', '/admin/users?q%5Busername%5D=user_4%2A');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(11, $data->total);
        $this->assertEquals('/admin/users?q%5Busername%5D=user_4%2A&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('user_49', $data->items[0]->username);
        $this->assertEquals('User 49', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000049', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users?q%5Busername%5D=user_4');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(1, $data->total);
        $this->assertArrayNotHasKey('_links', (array)$data);
        $this->assertEquals('user_4', $data->items[0]->username);
        $this->assertEquals('User 4', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000004', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users?q%5Busername%5D=%');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(0, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(0, $data->total);
        $this->assertArrayNotHasKey('_links', (array)$data);


        $this->client->request('GET', '/admin/users?q%5Bname%5D=User+4%2A');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(11, $data->total);
        $this->assertEquals('/admin/users?q%5Bname%5D=User+4%2A&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('user_49', $data->items[0]->username);
        $this->assertEquals('User 49', $data->items[0]->display_name);
        $this->assertEquals('/admin/users/00000000-0000-0000-0000-000000000049', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/users?q%5Bis%5D=enabled');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(120, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D=disabled');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(120, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D=user');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D=admin');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(240, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D=superadmin');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(30, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D%5B%5D=disabled&q%5Bis%5D%5B%5D=superadmin');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, $data->total);

        $this->client->request('GET', '/admin/users?q%5Bis%5D%5B%5D=user&q%5Bis%5D%5B%5D=admin');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0, $data->total);
    }

    public function testPost()
    {
        $this->client->request('POST', '/admin/users', array(
            'app_user' => array(
                'username' => 'xyz',
                'displayName' => 'User XYZ',
                'passwordEnabled' => '0',
                'role' => 'ROLE_USER',
            )
        ));

        $this->assertEquals(Codes::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('#http://localhost/admin/users/[0-9A-F]{8}(-[0-9A-F]{4}){3}-[0-9A-F]{12}#', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->{'non-locked'});
        $this->assertEquals('ROLE_USER', $data->role);
        $this->assertEquals('User XYZ', $data->display_name);
        $this->assertFalse($data->enabled);


        $this->client->request('POST', '/admin/users', array(
            'app_user' => array(
                'username' => 'xyz',
                'displayName' => 'User XYZ',
                'passwordEnabled' => '0'
            )
        ));

        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->errors->children->username->errors);


        $this->client->request('POST', '/admin/users', array(
            'app_user' => array(
                'username' => 'def',
                'displayName' => '',
                'passwordEnabled' => '0',
            )
        ));

        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->errors->children->displayName->errors);


        $this->client->request('POST', '/admin/users', array(
            'app_user' => array(
                'username' => 'def',
                'displayName' => 'User DEF',
                'password' => 'password',
                'passwordEnabled' => '1',
                'emailAddresses' => [
                    [
                        'email' => 'xy@example.com',
                    ],
                    [
                        'email' => 'qf@example.com',
                        'verified' => true,
                    ],
                    [
                        'email' => 'primary@example.com',
                        'primary' => true,
                    ]
                ],
                'enabled' => true,
                'role' => 'ROLE_ADMIN'
            )
        ));

        $this->assertEquals(Codes::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertRegExp('#/admin/users/[0-9A-F]{8}(-[0-9A-F]{4}){3}-[0-9A-F]{12}#', $this->client->getResponse()->headers->get('Location'));

        $this->client->request('GET', $this->client->getResponse()->headers->get('Location'));
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('User DEF', $data->display_name);
        $this->assertEquals('ROLE_ADMIN', $data->role);
        $this->assertFalse($data->{'non-locked'});
        $this->assertTrue($data->enabled);
        $this->assertCount(3, $data->emails);
    }

    public function testGet()
    {
        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('00000000-0000-0000-0000-000000000000', $data->guid);
        $this->assertEquals('user_0', $data->username);
        $this->assertEquals('User 0', $data->display_name);
        $this->assertTrue($data->enabled);
        $this->assertTrue($data->{'non-locked'});
        $this->assertEquals('ROLE_ADMIN', $data->role);
        $this->assertCount(1, $data->emails);
        $this->assertEquals('0@example.invalid', $data->emails[0]->addr);
        $this->assertTrue($data->emails[0]->primary);
        $this->assertTrue($data->emails[0]->verified);
        $this->assertRegExp('#/admin/users/00000000-0000-0000-0000-000000000000/emails/\d+#', $data->emails[0]->_links->self->href);
        $this->assertCount(1, $data->groups);
        $this->assertEquals('group_3', $data->groups[0]->name);
        $this->assertEquals('DisplayName 3', $data->groups[0]->display_name);
        $this->assertEquals('/admin/groups/group_3', $data->groups[0]->_links->self->href);


        $this->client->request('GET', '/admin/users/10000000-0000-0000-0000-000000000000');
        //TODO: $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testPatchDisplayname()
    {
        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000000/displayname', array(), array(), array(), 'New display name');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New display name', $data->display_name);

        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000001/displayname', array(), array(), array(), '');
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000001');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('User 1', $data->display_name);
    }

    public function testPatchUsername()
    {
        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000000/username', array(), array(), array(), 'xxxx');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('xxxx', $data->username);

        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000001/username', array(), array(), array(), '');
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000001');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('user_1', $data->username);
    }

    public function testPatchPassword()
    {
        $this->markTestIncomplete('Cannot check password after setting it');
    }

    public function testPatchPasswordEnabled()
    {
        $this->markTestIncomplete('Cannot check passwordEnabled after setting it');
    }

    public function testPatchRole()
    {
        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000000/role', array(), array(), array(), 'ROLE_USER');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('ROLE_USER', $data->role);

        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000001/username', array(), array(), array(), 'XXX');
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000001');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('ROLE_ADMIN', $data->role);
    }

    public function testPatchEnable()
    {
        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000000/disable');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertFalse($data->enabled);

        $this->client->request('PATCH', '/admin/users/00000000-0000-0000-0000-000000000000/enable');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($data->enabled);
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        //TODO: $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testLink()
    {
        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_1>; rel="group", </admin/groups/group_2>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(3, $data->groups);

        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->groups);

        $this->assertContains('group_1', $groupNames);
        $this->assertContains('group_2', $groupNames);
        $this->assertContains('group_3', $groupNames);


        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_2>; rel="group", </admin/groups/group_3>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_2>; rel="qq", </admin/groups/group_4>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_4>'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/non_existing_group>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('LINK', '/admin/users/00000000-0000-0000-0000-000000000000', array(), array(), array(
            'HTTP_LINK' => '</admin/users/00000000-0000-0000-0000-000000000000>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(3, $data->groups);

        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->groups);

        $this->assertContains('group_1', $groupNames);
        $this->assertContains('group_2', $groupNames);
        $this->assertContains('group_3', $groupNames);
    }

    public function testUnlink()
    {
        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_5>; rel="group", </admin/groups/group_6>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000008');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, $data->groups);
        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->groups);
        $this->assertContains('group_4', $groupNames);


        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_5>; rel="group", </admin/groups/group_6>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_7>; rel="qq", </admin/groups/group_8>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_7>'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/non_existing_group>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('UNLINK', '/admin/users/00000000-0000-0000-0000-000000000008', array(), array(), array(
            'HTTP_LINK' => '</admin/users/00000000-0000-0000-0000-000000000000>; rel="group"'
        ));
        $this->assertEquals(Codes::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/users/00000000-0000-0000-0000-000000000008');
        $this->assertEquals(Codes::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(1, $data->groups);
        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->groups);
        $this->assertContains('group_4', $groupNames);
    }
}
