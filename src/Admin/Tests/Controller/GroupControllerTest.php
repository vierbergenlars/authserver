<?php
/* Authserver, an OAuth2-based single-signon authentication provider written in PHP.
 *
 * Copyright (C) 2015  Lars Vierbergen
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Admin\Tests\Controller;

use Admin\Entity\ApiKey;
use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\UuidGenerator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;

class GroupControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;
    
    private $em;

    static private $numFlags;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        static::bootKernel();
        @unlink(self::$kernel->getRootDir().'/test_db.sqlite');
        $cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        if($cliApp->run(new StringInput('doctrine:migrations:migrate --no-interaction'), new NullOutput()))
            throw new \RuntimeException('Could not run the migrations.');

        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em EntityManager */

        self::$numFlags = new \stdClass();
        self::$numFlags->noGroups = 0;
        self::$numFlags->exportable = 0;
        self::$numFlags->noUsers = 0;
        self::$numFlags->userJoinable = 0;
        self::$numFlags->userLeaveable = 0;
        $groups = array();
        /* @var $groups Group[] */
        for($i=0;$i<0x40;$i++) {
            $group = new Group();
            $groups[] = $group;
            $group->setName('group_'.$i);
            $group->setDisplayName('DisplayName '.$i);
            if($i%0x2 == 1)
                self::$numFlags->noGroups++;
            $group->setNoGroups($i%0x2 == 1);
            if($i%0x4 == 1)
                self::$numFlags->exportable++;
            $group->setExportable($i%0x4 == 1);
            if($i%0x8 == 1)
                self::$numFlags->noUsers++;
            $group->setNoUsers($i%0x8 == 1);
            if($i%0x10 == 1)
                self::$numFlags->userJoinable++;
            $group->setUserJoinable($i%0x10 == 1);
            if($i%0x20 == 1)
                self::$numFlags->userLeaveable++;
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

        for($i=0;$i<10;$i++) {
            $user = new User();
            $users[] = $user;
            $user->setGuid($guidGenerator->generate($em, $user));
            $user->setUsername('user_'.$i);
            $user->setDisplayName('User '.$i);
            $user->setEnabled(true);
            $user->setRole('ROLE_USER');
            $user->setPasswordEnabled(0);
            $em->persist($user);
        }

        $users[0]->setGuid('00000000-0000-0000-0000-000000000000');
        $users[0]->addGroup($groups[3]);
        $users[1]->addGroup($groups[3]);
        $users[2]->addGroup($groups[4]);
        $users[3]->addGroup($groups[4]);
        $users[4]->addGroup($groups[4]);
        $users[5]->addGroup($groups[5]);
        $users[6]->addGroup($groups[6]);
        $users[7]->addGroup($groups[6]);
        $users[8]->addGroup($groups[6])->addGroup($groups[4]);
        $users[9]->addGroup($groups[6])->addGroup($groups[4]);

        $apiKey = new ApiKey();
        $apiKey->setName('Test API Key');
        $apiKey->setScopes(array('r_group', 'w_group'));
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

    /**
     * Clean up Kernel usage in this test.
     */
    protected function tearDown()
    {
        /*$cliApp = new Application(static::$kernel);
        $cliApp->setAutoExit(false);
        if($cliApp->run(new StringInput('doctrine:database:drop --force'), new NullOutput()))
            throw new \RuntimeException('Could not drop the database.');*/
        parent::tearDown();
    }

    
    public function testCget()
    {
        $this->client->request('GET', '/admin/groups');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(64, $data->total);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('/admin/groups?page=2', $data->_links->next->href);
        $this->assertEquals('group_63', $data->items[0]->name);
        $this->assertEquals('DisplayName 63', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_63', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?page=2');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(2, $data->page);
        $this->assertEquals(64, $data->total);
        $this->assertEquals('/admin/groups?page=1', $data->_links->prev->href);
        $this->assertEquals('/admin/groups?page=3', $data->_links->next->href);
        $this->assertEquals('group_53', $data->items[0]->name);
        $this->assertEquals('DisplayName 53', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_53', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?page=7');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(4, $data->items);
        $this->assertEquals(7, $data->page);
        $this->assertEquals(64, $data->total);
        $this->assertEquals('/admin/groups?page=6', $data->_links->prev->href);
        $this->assertArrayNotHasKey('next', (array)$data->_links);
        $this->assertEquals('group_3', $data->items[0]->name);
        $this->assertEquals('DisplayName 3', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_3', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?per_page=20');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(20, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(64, $data->total);
        $this->assertEquals('/admin/groups?per_page=20&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('group_63', $data->items[0]->name);
        $this->assertEquals('DisplayName 63', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_63', $data->items[0]->_links->self->href);

    }

    public function testCgetSearch()
    {
        $this->client->request('GET', '/admin/groups?q%5Btechname%5D=group_4%25');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(11, $data->total);
        $this->assertEquals('/admin/groups?q%5Btechname%5D=group_4%25&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('group_49', $data->items[0]->name);
        $this->assertEquals('DisplayName 49', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_49', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?q%5Btechname%5D=group_4');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(1, $data->total);
        $this->assertArrayNotHasKey('_links', (array)$data);
        $this->assertEquals('group_4', $data->items[0]->name);
        $this->assertEquals('DisplayName 4', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_4', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?q%5Bname%5D=DisplayName+4%25');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(10, $data->items);
        $this->assertEquals(1, $data->page);
        $this->assertEquals(11, $data->total);
        $this->assertEquals('/admin/groups?q%5Bname%5D=DisplayName+4%25&page=2', $data->_links->next->href);
        $this->assertArrayNotHasKey('prev', (array)$data->_links);
        $this->assertEquals('group_49', $data->items[0]->name);
        $this->assertEquals('DisplayName 49', $data->items[0]->display_name);
        $this->assertEquals('/admin/groups/group_49', $data->items[0]->_links->self->href);


        $this->client->request('GET', '/admin/groups?q%5Bexportable%5D=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(self::$numFlags->exportable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Bexportable%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0x40-self::$numFlags->exportable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Bgroups%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(self::$numFlags->noGroups, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Bgroups%5D=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0x40-self::$numFlags->noGroups, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Busers%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(self::$numFlags->noUsers, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Busers%5D=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0x40-self::$numFlags->noUsers, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Buserjoin%5D=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(self::$numFlags->userJoinable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Buserjoin%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0x40-self::$numFlags->userJoinable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Buserleave%5D=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(self::$numFlags->userLeaveable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Buserleave%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(0x40-self::$numFlags->userLeaveable, $data->total);

        $this->client->request('GET', '/admin/groups?q%5Bexportable%5D=1&q%5Buserjoin%5D=0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals(12, $data->total);
    }

    public function testPost()
    {
        $this->client->request('POST', '/admin/groups', array(
            'app_group' => array(
                'name' => 'abc',
                'displayName' => 'Group abc'
            )
        ));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/admin/groups/abc', $this->client->getResponse()->headers->get('Location'));


        $this->client->request('GET', '/admin/groups/abc');
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('Group abc', $data->display_name);
        $this->assertEquals('/admin/groups/abc', $data->_links->self->href);


        $this->client->request('POST', '/admin/groups', array(
            'app_group' => array(
                'name' => 'abc',
                'displayName' => 'Group abc'
            )
        ));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->errors->children->name->errors);


        $this->client->request('POST', '/admin/groups', array(
            'app_group' => array(
                'name' => 'def',
                'displayName' => ''
            )
        ));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $data->errors->children->displayName->errors);


        $this->client->request('POST', '/admin/groups', array(
            'app_group' => array(
                'name' => 'def',
                'displayName' => 'Group DEF',
                'exportable' => '1',
                'userJoinable' => '1',
                'userLeaveable' => '1',
                'noGroups' => '1',
                'noUsers' => '1',
            )
        ));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/admin/groups/def', $this->client->getResponse()->headers->get('Location'));

    }

    public function testGet()
    {
        $this->client->request('GET', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('group_0', $data->name);
        $this->assertEquals('DisplayName 0', $data->display_name);
        $this->assertCount(0, $data->members);
        $this->assertCount(1, $data->parents);
        $this->assertEquals('group_1', $data->parents[0]->name);
        $this->assertEquals('DisplayName 1', $data->parents[0]->display_name);
        $this->assertEquals('/admin/groups/group_1', $data->parents[0]->_links->self->href);
        $this->assertEquals('/admin/groups/group_0', $data->_links->self->href);
        $this->assertEquals('/admin/groups/group_0/members', $data->_links->members->href);

        $this->client->request('GET', '/admin/groups/group_1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('group_1', $data->name);
        $this->assertEquals('DisplayName 1', $data->display_name);
        $this->assertCount(1, $data->members);
        $this->assertEquals('group_0', $data->members[0]->name);
        $this->assertEquals('DisplayName 0', $data->members[0]->display_name);
        $this->assertEquals('/admin/groups/group_0', $data->members[0]->_links->self->href);
        $this->assertCount(0, $data->parents);
        $this->assertEquals('/admin/groups/group_1', $data->_links->self->href);
        $this->assertEquals('/admin/groups/group_1/members', $data->_links->members->href);

        $this->client->request('GET', '/admin/groups/non_existing_group');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testGetMembers()
    {
        $this->client->request('GET', '/admin/groups/group_3/members');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $data->total);
        $this->assertCount(2, $data->items);

        $this->assertEquals('user_0', $data->items[0]->username);
        $this->assertEquals('user_1', $data->items[1]->username);


        $this->client->request('GET', '/admin/groups/group_3/members?all=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(8, $data->total);
        $this->assertCount(8, $data->items);

        $usernames = array_map(function($u) {
            return $u->username;
        }, $data->items);

        $this->assertContains('user_0', $usernames);
        $this->assertContains('user_1', $usernames);
        $this->assertContains('user_2', $usernames);
        $this->assertContains('user_3', $usernames);
        $this->assertContains('user_4', $usernames);
        $this->assertContains('user_5', $usernames);
        $this->assertContains('user_8', $usernames);
        $this->assertContains('user_9', $usernames);


        $this->client->request('GET', '/admin/groups/group_3/members?per_page=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $data->total);
        $this->assertCount(1, $data->items);
        $this->assertEquals('/admin/groups/group_3/members?per_page=1&page=2', $data->_links->next->href);


        $this->client->request('GET', '/admin/groups/group_3/members?all=1&per_page=1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(8, $data->total);
        $this->assertCount(1, $data->items);
        $this->assertEquals('/admin/groups/group_3/members?all=1&per_page=1&page=2', $data->_links->next->href);

    }

    public function testPatchDisplayname()
    {
        $this->client->request('PATCH', '/admin/groups/group_0/displayname', array(), array(), array(), 'New display name');
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('New display name', $data->display_name);

        $this->client->request('PATCH', '/admin/groups/group_1/displayname', array(), array(), array(), '');
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/groups/group_1');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('DisplayName 1', $data->display_name);
    }

    public function testPatchFlags()
    {
        $this->markTestIncomplete('Cannot check flag values after setting them.');
    }

    public function testDelete()
    {
        $this->client->request('DELETE', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    public function testLink()
    {
        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_2>; rel="group", </admin/groups/group_3>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(3, $data->parents);

        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->parents);

        $this->assertContains('group_1', $groupNames);
        $this->assertContains('group_2', $groupNames);
        $this->assertContains('group_3', $groupNames);


        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_2>; rel="group", </admin/groups/group_3>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_2>; rel="qq", </admin/groups/group_4>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_4>'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/non_existing_group>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('LINK', '/admin/groups/group_0', array(), array(), array(
            'HTTP_LINK' => '</admin/users/00000000-0000-0000-0000-000000000000>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/groups/group_0');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(3, $data->parents);

        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->parents);

        $this->assertContains('group_1', $groupNames);
        $this->assertContains('group_2', $groupNames);
        $this->assertContains('group_3', $groupNames);
    }

    public function testUnlink()
    {
        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_3>; rel="group", </admin/groups/group_6>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/admin/groups/group_5');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(2, $data->parents);
        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->parents);
        $this->assertContains('group_7', $groupNames);
        $this->assertContains('group_8', $groupNames);


        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_3>; rel="group", </admin/groups/group_6>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());


        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_7>; rel="qq", </admin/groups/group_8>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/group_7>'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());

        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/groups/non_existing_group>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());

        $this->client->request('UNLINK', '/admin/groups/group_5', array(), array(), array(
            'HTTP_LINK' => '</admin/users/00000000-0000-0000-0000-000000000000>; rel="group"'
        ));
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());


        $this->client->request('GET', '/admin/groups/group_5');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent());

        $this->assertCount(2, $data->parents);

        $groupNames = array_map(function($g) {
            return $g->name;
        }, $data->parents);

        $this->assertContains('group_7', $groupNames);
        $this->assertContains('group_8', $groupNames);
    }
}
