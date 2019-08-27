<?php
/**
*
* @package phpBB Extension - tas2580 Social Media Buttons
* @copyright (c) 2014 tas2580 (https://tas2580.net)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace tas2580\sitemap\controller;

define('SQL_CACHE_TIME',	120);  //only update the data after X seconds to reduce sql load a little 

use Symfony\Component\HttpFoundation\Response;

class sitemap
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver */
	protected $db;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\event\dispatcher_interface */
	protected $phpbb_dispatcher;

	/** @var string php_ext */
	protected $php_ext;

	/** @var string */
	protected $phpbb_extension_manager;

	/**
	* Constructor
	*
	* @param \phpbb\auth\auth						$auth						Auth object
	* @param \phpbb\config\config					$config						Config object
	* @param \phpbb\db\driver\driver_interface		$db							Database object
	* @param \phpbb\controller\helper				$helper						Helper object
	* @param string									$php_ext					phpEx
	* @param \phpbb_extension_manager				$phpbb_extension_manager    phpbb_extension_manager
	*/
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\controller\helper $helper, \phpbb\event\dispatcher_interface $phpbb_dispatcher, $php_ext, $phpbb_extension_manager)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->helper = $helper;
		$this->phpbb_dispatcher = $phpbb_dispatcher;
		$this->php_ext = $php_ext;
		$this->phpbb_extension_manager = $phpbb_extension_manager;

		$this->board_url = generate_board_url();
	}

	/**
	 * Generate sitemap for a forum
	 *
	 * @param int		$id		The forum ID
	 * @return object
	 */
	public function sitemap($id)
	{
		if (!$this->auth->acl_get('f_list', $id))
		{
			trigger_error('SORRY_AUTH_READ');
		}

		$sql = 'SELECT forum_id, forum_name, forum_last_post_time, forum_topics_approved
			FROM ' . FORUMS_TABLE . '
			WHERE forum_id = ' . (int) $id;
		$result = $this->db->sql_query($sql, SQL_CACHE_TIME);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$start = 0;
		do
		{
			// URL for the forum
			$url = $this->board_url . '/viewforum.' . $this->php_ext . '?f=' . $id;
			if ($start > 0)
			{
				$url .= '&amp;start=' . $start;
			}
			$url_data[] = array(
				'url'	=> $url,
				'time'	=> $row['forum_last_post_time'],
				'row'	=> $row,
				'start'	=> $start
			);
			$start += $this->config['topics_per_page'];
		}
		while ($start < $row['forum_topics_approved']);

		// Get all topics in the forum
		$sql = 'SELECT topic_id, topic_title, topic_last_post_time, topic_posts_approved
			FROM ' . TOPICS_TABLE . '
			WHERE forum_id = ' . (int) $id . '
			AND topic_visibility = ' . ITEM_APPROVED . '
			AND topic_status <> ' . ITEM_MOVED;
		$result = $this->db->sql_query($sql, SQL_CACHE_TIME);
		while ($topic_row = $this->db->sql_fetchrow($result))
		{
			// Put forum data to each topic row
			$topic_row['forum_id'] = $id;
			$topic_row['forum_name'] = $row['forum_name'];
			$topic_row['forum_last_post_time'] = $row['forum_last_post_time'];

			$start = 0;
			do
			{
				// URL for topic
				$url = $this->board_url . '/viewtopic.' . $this->php_ext . '?f=' . $id . '&amp;t=' . $topic_row['topic_id'];
				if ($start > 0)
				{
					$url .= '&amp;start=' . $start;
				}
				$url_data[] = array(
					'url'	=> $url,
					'time'	=> $topic_row['topic_last_post_time'],
					'row'	=> $topic_row,
					'start'	=> $start
				);
				$start += $this->config['posts_per_page'];
			}
			while ($start < $topic_row['topic_posts_approved']);
		}
		$this->db->sql_freeresult($result);

		return $this->output_sitemap($url_data, 'urlset');
	}

	/**
	 * Generate sitemap index
	 *
	 * @return object
	 */
	public function index()
	{
		$sql = 'SELECT forum_id, forum_name, forum_last_post_time
			FROM ' . FORUMS_TABLE . '
			WHERE forum_type = ' . (int) FORUM_POST . '
			ORDER BY left_id ASC';
		$result = $this->db->sql_query($sql, SQL_CACHE_TIME);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($this->auth->acl_get('f_list', $row['forum_id']))
			{
				$url_data[] = array(
					'url'		=> $this->helper->route('tas2580_sitemap_sitemap', array('id' => $row['forum_id']), true, '', \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
					'time'		=> $row['forum_last_post_time'],
					'row'		=> $row,
					'start'		=> 0
				);
			}
		}
		$this->db->sql_freeresult($result);
		return $this->output_sitemap($url_data, 'sitemapindex');
	}

	/**
	 * Generate the XML sitemap
	 *
	 * @param array	$url_data
	 * @param string	$type
	 * @return Response
	 */
	private function output_sitemap($url_data, $type = 'sitemapindex')
	{
		$style_xsl = $this->board_url . '/'. $this->phpbb_extension_manager->get_extension_path('tas2580/sitemap', false) . 'style.xsl';

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<?xml-stylesheet type="text/xsl" href="' . $style_xsl . '" ?>' . "\n";
		$xml .= '<' . $type . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		/**
		* Modify the sitemap link before output
		*
		* @event tas2580.sitemap.modify_before_output
		* @var	string	type			Type of the sitemap (sitemapindex or urlset)
		* @var	array		url_data		URL informations
		* @since 0.1.4
		*/
		$vars = array(
			'type',
			'url_data',
		);
		extract($this->phpbb_dispatcher->trigger_event('tas2580.sitemap.modify_before_output', compact($vars)));

		$tag = ($type == 'sitemapindex') ? 'sitemap' : 'url';
		foreach ($url_data as $data)
		{
			$xml .= '	<' . $tag . '>' . "\n";
			$xml .= '		<loc>' . $data['url'] . '</loc>'. "\n";
			$xml .= ($data['time'] <> 0) ? '		<lastmod>' . gmdate('Y-m-d\TH:i:s+00:00', (int) $data['time']) . '</lastmod>' .  "\n" : '';
			$xml .= '	</' . $tag . '>' . "\n";
		}
		$xml .= '</' . $type . '>';

		$headers = array(
			'Content-Type'		=> 'application/xml; charset=UTF-8',
		);
		return new Response($xml, '200', $headers);
	}
}
