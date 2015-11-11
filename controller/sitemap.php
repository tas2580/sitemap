<?php
/**
*
* @package phpBB Extension - tas2580 Social Media Buttons
* @copyright (c) 2014 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace tas2580\sitemap\controller;

use Symfony\Component\HttpFoundation\Response;

class sitemap
{
	/** @var \phpbb\auth\auth */
	protected $auth;
	/** @var \phpbb\db\driver\driver */
	protected $db;
	/** @var \phpbb\controller\helper */
	protected $helper;
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
	public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, $php_ext, $phpbb_extension_manager)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->helper = $helper;
		$this->php_ext = $php_ext;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
	}

	public function sitemap($id)
	{
		$board_url = generate_board_url();
		$sql = 'SELECT forum_name, forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$style_xsl = $board_url . '/'. $this->phpbb_extension_manager->get_extension_path('tas2580/sitemap', false) . 'style.xsl';

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . $style_xsl . '" ?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		$xml .= '	<url>' . "\n";
		$xml .= '		<loc>' . $board_url . '/viewforum.' . $this->php_ext . '?f=' . $id . '</loc>' . "\n";
		$xml .= ($row['forum_last_post_time'] <> 0) ? '		<lastmod>' . gmdate('Y-m-d\TH:i:s+00:00', (int) $row['forum_last_post_time']) . '</lastmod>' . "\n" : '';
		$xml .= '	</url>' . "\n";

		$sql = 'SELECT topic_id, topic_title, topic_last_post_time, topic_status
			FROM ' . TOPICS_TABLE . '
			WHERE forum_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['topic_status'] <> ITEM_MOVED)
			{
				$xml .= '	<url>' . "\n";
				$xml .= '		<loc>' . $board_url . '/viewtopic.' . $this->php_ext . '?f=' . $id . '&amp;t=' . $row['topic_id'] . '</loc>' . "\n";
				$xml .= ($row['topic_last_post_time'] <> 0) ? '		<lastmod>' . gmdate('Y-m-d\TH:i:s+00:00', (int) $row['topic_last_post_time']) . '</lastmod>' . "\n" : '';
				$xml .= '	</url>' . "\n";
			}
		}
		$xml .= '</urlset>';

		header("Content-type: application/xml");
		return new Response($xml);
	}

	public function index()
	{
		$board_url = generate_board_url();
		$style_xsl = $board_url . '/'. $this->phpbb_extension_manager->get_extension_path('tas2580/sitemap', false) . 'style.xsl';

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . $style_xsl . '" ?>' . "\n";
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$sql = 'SELECT forum_id, forum_name, forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_type = ' . (int) FORUM_POST . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($this->auth->acl_get('f_list', $row['forum_id']))
			{
				$xml .= '	<sitemap>' . "\n";
				$xml .= '		<loc>' . $this->helper->route('tas2580_sitemap_sitemap', array('id' => $row['forum_id']), true, '', \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '</loc>' . "\n";
				$xml .= ($row['forum_last_post_time'] <> 0) ? '		<lastmod>' . gmdate('Y-m-d\TH:i:s+00:00', (int) $row['forum_last_post_time']) . '</lastmod>' . "\n" : '';
				$xml .= '	</sitemap>' . "\n";
			}
		}
		$xml .= '</sitemapindex>';

		header("Content-type: application/xml");
		return new Response($xml);
	}
}
