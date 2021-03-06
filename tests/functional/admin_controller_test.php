<?php
/**
*
* Board Rules extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace phpbb\boardrules\tests\functional;

/**
* @group functional
*/
class admin_controller_test extends boardrules_functional_base
{
	/**
	 * Test Board Rules ACP module appears
	 */
	public function test_acp_module()
	{
		$this->login();
		$this->admin_login();

		// Load Pages ACP page
		$crawler = self::request('GET', "adm/index.php?i=\\phpbb\\boardrules\\acp\\boardrules_module&mode=manage&language=1&sid={$this->sid}");

		// Assert Board Rules module appears in sidebar
		$this->assertContainsLang('ACP_BOARDRULES', $crawler->filter('.menu-block')->text());
		$this->assertContainsLang('ACP_BOARDRULES_MANAGE', $crawler->filter('#activemenu')->text());

		// Assert Board Rules display appears
		$this->assertContainsLang('ACP_BOARDRULES_MANAGE', $crawler->filter('#main')->text());
		$this->assertContainsLang('ACP_BOARDRULES_MANAGE_EXPLAIN', $crawler->filter('#main')->text());

		// Return $crawler for use in @depends functions
		return $crawler;
	}

	/**
	 * Test Board Rules ACP Create Rule
	 * @param $crawler \Symfony\Component\DomCrawler\Crawler
	 *
	 * @depends test_acp_module
	 */
	public function test_acp_create_rule($crawler)
	{
		// Jump to the create page
		$form = $crawler->selectButton($this->lang('SUBMIT'))->form();
		$crawler = self::submit($form);
		$this->assertContainsLang('ACP_BOARDRULES_CREATE_RULE', $crawler->filter('#main h1')->text());

		// Submit new rule data
		$form = $crawler->selectButton($this->lang('SUBMIT'))->form(array(
			'rule_title'	=> 'Test Rule',
			'rule_anchor'	=> 'test-rule',
			'rule_message'	=> str_repeat('test ', 1000), // 5000 character message
		));
		$crawler = self::submit($form);

		// Assert addition was success
		$this->assertGreaterThan(0, $crawler->filter('.successbox')->count());
		$this->assertContainsLang('ACP_RULE_ADDED', $crawler->text());
	}

	/**
	 * Test Board Rules ACP Edit Rule
	 */
	public function test_acp_edit_rule()
	{
		$this->login();
		$this->admin_login();

		// Edit the rule identified by id 3
		$crawler = self::request('GET', "adm/index.php?i=\\phpbb\\boardrules\\acp\\boardrules_module&mode=manage&language=1&action=edit&rule_id=3&sid={$this->sid}");

		// Assert edit page is displayed
		$this->assertContainsLang('ACP_BOARDRULES_EDIT_RULE', $crawler->filter('#main')->text());
		$this->assertContainsLang('ACP_BOARDRULES_EDIT_RULE_EXPLAIN', $crawler->filter('#main')->text());
	}

	/**
	 * Test Board Rules ACP Delete Rule
	 */
	public function test_acp_delete_rule()
	{
		$this->login();
		$this->admin_login();

		// Delete the rule identified by id 3
		$crawler = self::request('GET', "adm/index.php?i=\\phpbb\\boardrules\\acp\\boardrules_module&mode=manage&language=1&action=delete&rule_id=3&sid={$this->sid}");

		// Confirm delete
		$form = $crawler->selectButton('confirm')->form();
		$crawler = self::submit($form);

		// Assert deletion was success
		$this->assertGreaterThan(0, $crawler->filter('.successbox')->count());
		$this->assertContainsLang('ACP_RULE_DELETED', $crawler->text());
	}

	/**
	 * Test Board Rules Notifications
	 */
	public function test_notifications()
	{
		$this->login();
		$this->admin_login();

		// Load Board Rules Settings page
		$crawler = self::request('GET', "adm/index.php?i=\\phpbb\\boardrules\\acp\\boardrules_module&mode=settings&sid={$this->sid}");
		$this->assertContainsLang('ACP_BOARDRULES_SETTINGS', $crawler->filter('#main')->text(), 'The Board Rules settings page failed to load');

		// Send out notifications
		$form = $crawler->selectButton('action_send_notification')->form();
		$crawler = self::submit($form);
		$form = $crawler->selectButton('confirm')->form();
		$crawler = self::submit($form);

		// Assert no error occurred
		$this->assertContainsLang('ACP_BOARDRULES_SETTINGS', $crawler->filter('#main')->text(), 'Failed to successfully send notifications');

		// Assert notifications were sent
		$crawler = self::request('GET', "index.php?&sid={$this->sid}");
		$this->assertContainsLang('BOARDRULES_NOTIFICATION', $crawler->filter('.notification-title')->text(), 'The notification was not found in the notifications list');
	}

	/**
	 * Test Board Rules ACP Settings
	 */
	public function test_acp_settings_and_logs()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('phpbb/boardrules', 'info_acp_boardrules');
		$crawler = self::request('GET', "adm/index.php?i=-phpbb-boardrules-acp-boardrules_module&mode=settings&sid={$this->sid}");
		$form = $crawler->selectButton('submit')->form();
		$crawler = self::submit($form);
		$this->assertContainsLang('ACP_BOARDRULES_SETTINGS_CHANGED', $crawler->text());

		// Confirm the log entry has been added correctly
		$crawler = self::request('GET', 'adm/index.php?i=acp_logs&mode=admin&sid=' . $this->sid);
		$this->assertContains(strip_tags($this->lang('ACP_BOARDRULES_SETTINGS_LOG')), $crawler->text());
	}

	/**
	* Test Board Rules ACP manage permission
	*/
	public function test_boardrules_acp_permissions()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('phpbb/boardrules', 'permissions_boardrules');
		$crawler = self::request('GET', "adm/index.php?i=acp_permissions&mode=setting_group_global&sid={$this->sid}");
		$form = $crawler->selectButton('submit')->form();

		// Select Administrative permissions option
		$form->get('type')->setValue('a_');
		$crawler = self::submit($form);

		$this->assertContainsLang('ACL_A_BOARDRULES', $crawler->text());
	}
}
