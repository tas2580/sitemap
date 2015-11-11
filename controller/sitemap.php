<?php
/**
*
* @package phpBB Extension - tas2580 Social Media Buttons
* @copyright (c) 2014 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace tas2580\sitemap\controller;

class sitemap
{
	/** @var \phpbb\auth\auth */
	protected $auth;
	/** @var \phpbb\db\driver\driver */
	protected $db;
	/** @var \phpbb\controller\helper */
	protected $helper;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var string php_ext */
	protected $php_ext;
	/** @var string */
	protected $phpbb_extension_manager;
	/**
	* Constructor
	*
	* @param \phpbb\auth\auth			$auth		Auth object
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\template\template $template, $php_ext, $phpbb_extension_manager)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->helper = $helper;
		$this->template = $template;
		$this->php_ext = $php_ext;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
	}

	public function sitemap($id)
	{
		header('Content-Type: application/xml');
		$board_url = generate_board_url();
		$sql = 'SELECT forum_name, forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$style_xsl = $board_url . '/'. $this->phpbb_extension_manager->get_extension_path('tas2580/sitemap', false) . 'style.xsl';
		$this->template->assign_var('U_XSL_FILE', $style_xsl);

		$this->template->assign_block_vars('urlset', array(
			'URL'			=> $board_url . 'viewforum.' . $this->php_ext . '?f=' . $id,
			'TIME'		=> gmdate('Y-m-d\TH:i:s+00:00', (int) $row['forum_last_post_time']),
		));
		$sql = 'SELECT topic_id, topic_title, topic_last_post_time, topic_status
			FROM ' . TOPICS_TABLE . '
			WHERE forum_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['topic_status'] <> ITEM_MOVED)
			{
				$this->template->assign_block_vars('urlset', array(
					'URL'			=> $board_url .  '/viewtopic.' . $this->php_ext  . '?f=' . $id . '&t='. $row['topic_id'],
					'TIME'		=> ($row['topic_last_post_time'] <> 0)  ? gmdate('Y-m-d\TH:i:s+00:00', (int) $row['topic_last_post_time']) : '',
				));
			}
		}

		return $this->helper->render('sitemap.html');
	}

	public function index()
	{
		header('Content-Type: application/xml');

		$board_url = generate_board_url();
		$style_xsl = $board_url . '/'. $this->phpbb_extension_manager->get_extension_path('tas2580/sitemap', false) . 'style.xsl';
		$this->template->assign_var('U_XSL_FILE', $style_xsl);

		$sql = 'SELECT forum_id, forum_name, forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_type = ' . (int) FORUM_POST . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($this->auth->acl_get('f_list', $row['forum_id']))
			{
				$this->template->assign_block_vars('forumlist', array(
					'URL'			=> $this->helper->route('tas2580_sitemap_sitemap', array('id' => $row['forum_id']), true, '', \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
					'TIME'		=>($row['forum_last_post_time'] <> 0) ? gmdate('Y-m-d\TH:i:s+00:00', (int) $row['forum_last_post_time']) : '',
				));
			}
		}

		return $this->helper->render('sitemap_index.html');
	}
}
