<?php
/**
*
* Topic List extension for the phpBB Forum Software package.
*
* @copyright (c) 2018 Giovanni Dose (Micogian)
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace micogian\topic_list\event;
/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	protected $db;
	protected $template;
	protected $auth;
	protected $user;
	protected $cache;
	protected $root_path;
	protected $phpEx;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\template\template $template, \phpbb\auth\auth $auth, \phpbb\user $user, \phpbb\cache\service $cache, $root_path, $phpEx )
	{
		$this->db = $db;
		$this->template = $template; 
		$this->auth = $auth;
		$this->user = $user;
		$this->cache = $cache;
		$this->root_path = $root_path;
		$this->phpEx = $phpEx;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'					=> 'load_language_on_setup',
			'core.viewtopic_modify_post_row' 	=> 'viewtopic_add',
		);
	}
	public function load_language_on_setup($event)	
	{	
		//language start
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'micogian/topic_list',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
	public function viewtopic_add($event)	
	{
		$rowmessage	=$event['post_row'];
		$message	=$rowmessage['MESSAGE'];
		$post_id	=$rowmessage['POST_ID'];
		$multiforum	= false;
		
		$icons = $this->cache->obtain_icons();

		if(strpos($message,"ttlist]"))
		{
			// PERIODO DI RICERCA
			$time_cor	= request_var('days', 1);  // periodo della ricerca, per default TUTTO il periodo.
			//echo "Periodo di ricerca: " . $time_cor . "<br>";
			// stabilisce il periodo di visualizzazione, TUTTO per default 
			if(!$time_cor){
				$time_cor = '1';
			}
			$time_stamp = ("86400" * $time_cor);
			$time1	= "" ;
			$time2	= "" ;
			$time3	= "" ;
			$time4	= "" ;
			$time5	= "" ;
			$time6	= "" ;
			$time7	= "" ;
			if($time_cor == '1'){
				$time_stamp = time() ;
				$time_cor = '(Tutti)' ;
				$time1	= "selected='selected'" ;
			}elseif($time_cor == '7'){
				$time2 = "selected='selected'" ;
			}elseif($time_cor == '30'){
				$time3 = "selected='selected'" ;
			}elseif($time_cor == '60'){
				$time4 = "selected='selected'" ;
			}elseif($time_cor == '90'){
				$time5 = "selected='selected'" ;
			}elseif($time_cor == '120'){
				$time6 = "selected='selected'" ;
			}elseif($time_cor == '365'){
				$time7 = "selected='selected'" ;
			}
			$this->template->assign_vars(array(
			'TOPIC_TIME_1' 		=> $time1,
			'TOPIC_TIME_2' 		=> $time2,
			'TOPIC_TIME_3' 		=> $time3,
			'TOPIC_TIME_4' 		=> $time4,
			'TOPIC_TIME_5' 		=> $time5,
			'TOPIC_TIME_6' 		=> $time6,
			'TOPIC_TIME_7' 		=> $time7,
			'TEXT_TIME_1' 		=> 'TUTTO',
			'TEXT_TIME_2' 		=> '7 giorni',
			'TEXT_TIME_3' 		=> '30 giorni',
			'TEXT_TIME_4' 		=> '60 giorni',
			'TEXT_TIME_5' 		=> '90 giorni',
			'TEXT_TIME_6' 		=> '120 giorni',
			'TEXT_TIME_7' 		=> '365 giorni',
			'TITLE_TOPIC_LIST'	=> 'LISTA ALFABETICA DEI TOPICS DEI FORUM',
			));
			$data_post = abs(time() - $time_stamp) ;  // è possibile associare questa data alla creazione del Topic o all'ultimo post
	
			// ABILITA I CSS ASSOCOATI A TOPIC_LIST
			$this->template->assign_vars(array(
			'TOPIC_LIST_DISPLAY' 	=> true,
			));
			
			// LISTA DEI FORUM DA ELABORARE
			preg_match_all("#\[ttlist\](.*?)\[/ttlist\]#", $message, $forum_list);

			if($forum_list[1][0])
			{
					// LISTA MONOFORUM
					$forum_cor=$forum_list[1][0];	
				if (strpos($forum_cor, ","))
				{
					// LISTA MULTIFORUM
					$multiforum	= true ; 
					//$res ='<h2>Lista alfabetica dei topics dei Forum ' . $forum_cor . '</h2><br/>';
				}
			}
			
			else{
				// LISTA ASSOCIATA AL FORUM_ID DEL TOPIC CORRENTE
				$forum_query=$this->db->sql_query("SELECT forum_id
				FROM " . POSTS_TABLE . "
				WHERE post_id = $post_id");
				$forum_id_array=$this->db->sql_fetchrow($forum_query);
				$forum_cor=$forum_id_array['forum_id'];
			}
			$this->template->assign_vars(array(
			'FORUM_LIST_COR' 	=> $forum_cor,
			));
			
			//QUERY DI SELEZIONE DEI DATI MULTIFORUM
			$sql1 = "SELECT 
			t.topic_id, t.icon_id, t.topic_title, t.topic_time, 
			t.topic_moved_id, t.topic_first_poster_name, t.topic_poster, t.topic_views, t.topic_first_poster_colour, t.topic_last_post_id,
			t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour, t.topic_last_post_time,
			f.parent_id, f.forum_id, f.forum_name AS forum_name_cor,
			UCASE(LEFT(t.topic_title, 1)) AS first_char
			FROM ". TOPICS_TABLE." t,". FORUMS_TABLE. " f
			WHERE t.forum_id IN($forum_cor)
			AND f.forum_id = t.forum_id
			AND t.topic_moved_id = 0
			AND t.topic_last_post_time  > $data_post
			ORDER BY UCASE(t.topic_title)";
			$this->db->sql_query($sql1);
			$result1 = $this->db->sql_query($sql1);
			$current_char = '';
			$bg_row = "bg2";
			$i = 0;
			$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			while ($row1 = $this->db->sql_fetchrow($result1))
			{
				if (strchr("0123456789", $row1['first_char']))
				{
					$first_char[$i] = '0';
				}else if (strchr($string, $row1['first_char']) ){
					$first_char[$i] = $row1['first_char'];
				}else{
					$first_char[$i] 	= "@";
				}
				
				if ($first_char[$i] != $current_char) 
				{
					$first_row[$i]			= true ;
				}else{
					$first_row[$i]			= false ;
				}
				$topic_icon_img[$i]			= (!empty($icons[$row1['icon_id']])) ? "images/icons/".$icons[$row1['icon_id']]['img'] : 'ext/micogian/topic_list/images/empty.gif';
				$topic_id[$i]				= $row1['topic_id'];
				$forum_id[$i]				= $row1['forum_id'];
				$icon_id[$i]				= $row1['icon_id'];
				$topic_title[$i]			= $row1['topic_title'];
				$topic_link[$i]				= append_sid("{$this->root_path}viewtopic.{$this->phpEx}", 't='.$row1['topic_id']);
				$forum_link[$i]				= append_sid("{$this->root_path}viewforum.{$this->phpEx}", 'f='.$row1['forum_id']);
				$forum_name_cor[$i]			= $row1['forum_name_cor'];
				$topic_replies[$i]			= $row1['topic_replies'];
				$topic_views[$i]			= $row1['topic_views'];
				$topic_author[$i]			= $row1['topic_first_poster_name'];
				$topic_author_full[$i]		= get_username_string('full', $row1['topic_poster'], $row1['topic_first_poster_name'], $row1['topic_first_poster_colour']);
				$first_post_time[$i]		= $this->user->format_date($row1['topic_time']); //date("d.m.Y",$row['topic_time']);
				$last_post_time[$i]			= $this->user->format_date($row1['topic_last_post_time']);
				$last_post_author_full[$i]	= get_username_string('full', $row1['topic_last_poster_id'], $row1['topic_last_poster_name'], $row1['topic_last_poster_colour']);
				$last_post_link[$i]			= append_sid("{$this->root_path}viewtopic.{$this->phpEx}", "f=" . $row1['forum_id'] . "&amp;p=" . $row1['topic_last_post_id'] . "#p" . $row1['topic_last_post_id']);
				
				$reply = "SELECT COUNT(post_id) AS tot_replies FROM " . POSTS_TABLE . " WHERE topic_id = $topic_id[$i]";
				$result2 = $this->db->sql_query($reply);
				$row2 = $this->db->sql_fetchrow($result2);
				$topic_replies[$i]			= $row2[tot_replies] - 1;
				
				$this->template->assign_block_vars('topic_list', array(
				'S_FIRST_ROW'          	 	=> $first_row[$i],
				'BG_ROW'           			=> $bg_row,
				'FIRST_CHAR'           		=> $first_char[$i],
				'TOPIC_ICON_IMG'        	=> "<img src='".$phpbb_root_path.$topic_icon_img[$i]. "'>",
				'TOPIC_TITLE'           	=> $topic_title[$i],
				'TOPIC_LINK'            	=> append_sid("{$phpbb_root_path}viewtopic.$this->phpEx", 't='.$row1['topic_id']),
				'FORUM_LINK'		 		=> append_sid("{$phpbb_root_path}viewforum.$this->phpEx", 'f='.$row1['forum_id']),
				'FORUM_NAME'        		=> $forum_name_cor[$i],
				'TOPIC_REPLIES'         	=> $topic_replies[$i],
				'TOPIC_VIEWS'         	    => $topic_views[$i],
				'TOPIC_AUTHOR'          	=> $topic_author[$i],
				'TOPIC_AUTHOR_FULL'     	=> $topic_author_full[$i],
				'FIRST_POST_TIME'       	=> $first_post_time[$i],
				'LAST_POST_TIME'			=> $last_post_time[$i], 
				'LAST_POST_AUTHOR_FULL' 	=> $last_post_author_full[$i],
				'LAST_POST_LINK'			=> $last_post_link[$i], 
				));

				$current_char	= $first_char[$i] ;
				++$i;
			}
			$res = "";
			$message	= $res ;
			$rowmessage['MESSAGE']=$message;
			$event['post_row'] = $rowmessage;
		}		
	}
}
