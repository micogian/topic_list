<?php
/**
*
* Topic List extension for the phpBB Forum Software package.
* version 1.0.8 - 17/10/2022
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
         'core.user_setup'               => 'load_language_on_setup',
         'core.viewtopic_modify_post_row'    => 'viewtopic_add',
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
      $rowmessage   =$event['post_row'];
      $message   =$rowmessage['MESSAGE'];
      $post_id   =$rowmessage['POST_ID'];
      $multiforum   = false;
      
      //###########################################
      $per_page     = 4 ;   // Records per pagina
      //###########################################
      $icons = $this->cache->obtain_icons();

      if(strpos($message,"ttlist]"))
      {
         //echo "Message: " . $message . "<br />";
         // VARIABILI INIZIALI
         $time_cor         = request_var('days', 1);  // periodo della ricerca, per default TUTTO il periodo.
         $page_cor         = request_var('page', '1');  // pagina di default della lista
         $char_cor         = request_var('char', '');  // carattere iniziale selezionato
         $forum_cor        = request_var('f', '');
         $topic_cor        = request_var('t', '');
         $start            = request_var('start', '0');
         $cerca            = request_var('string', '');
         $user_cor         = request_var('u', '0');
         $base_url         = $this->root_path . "viewtopic.php" . "?f=" . $forum_cor . "&amp;t=" . $topic_cor;  
         $mode             = request_var ('mode', '1');		 
         //echo "base_url = " . $base_url . "<br>";
		 if($page_cor == 1)
         {
            $start   = 0 ;
		 }else{
            $start   = (($page_cor * $per_page) - $per_page);
		 }
         echo "page_cor = "  . $page_cor . "  start = " . $start . "<br>";
      
         // PERIODO DI RICERCA         
         //echo "Periodo di ricerca: " . $time_cor . "<br>";
         // stabilisce il periodo di visualizzazione, TUTTO per default 
         if(!$time_cor){
            $time_cor = '1';
         }
         $time_stamp = ("86400" * $time_cor);
         $time1   = "" ;
         $time2   = "" ;
         $time3   = "" ;
         $time4   = "" ;
         $time5   = "" ;
         $time6   = "" ;
         $time7   = "" ;
         if($time_cor == '1'){
            $time_stamp = time() ;
            $time_cor = '(Tutti)' ;
            $time1   = "selected='selected'" ;
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
         'TOPIC_TIME_1'       => $time1,
         'TOPIC_TIME_2'       => $time2,
         'TOPIC_TIME_3'       => $time3,
         'TOPIC_TIME_4'       => $time4,
         'TOPIC_TIME_5'       => $time5,
         'TOPIC_TIME_6'       => $time6,
         'TOPIC_TIME_7'       => $time7,
         'TEXT_TIME_1'       => 'TUTTO',
         'TEXT_TIME_2'       => '7 giorni',
         'TEXT_TIME_3'       => '30 giorni',
         'TEXT_TIME_4'       => '60 giorni',
         'TEXT_TIME_5'       => '90 giorni',
         'TEXT_TIME_6'       => '120 giorni',
         'TEXT_TIME_7'       => '365 giorni',
         'TITLE_TOPIC_LIST'   => 'LISTA ALFABETICA DEI TOPICS DEI FORUM',
         ));
         $data_post = abs(time() - $time_stamp) ;  // è possibile associare questa data alla creazione del Topic o all'ultimo post
   
         // ABILITA I CSS ASSOCIATI A TOPIC_LIST
         $this->template->assign_vars(array(
         'TOPIC_LIST_DISPLAY'    => true,
         ));
         
         // LISTA DEI FORUM DA ELABORARE
         preg_match_all("#\[ttlist\](.*?)\[/ttlist\]#", $message, $forum_list);
         $lista_tmp   = $forum_list[1][0] ;
         
         if(!$lista_tmp)
         {
            // LISTA MONOFORUM
            // FORUM NON INSERITO NEL BBCODE E PERTANTO ASSOCIATO AL FORUM_ID DEL TOPIC CORRENTE
            $forum_query=$this->db->sql_query("SELECT forum_id
            FROM " . POSTS_TABLE . "
            WHERE post_id = $post_id");
            $forum_id_array=$this->db->sql_fetchrow($forum_query);
            $lista_tmp   = $forum_id_array['forum_id'];
         }else{
            // LISTA MULTIFORUM
            if (strpos($lista_tmp, ","))
            {
               $multiforum   = true ; 
               //$res ='<h2>Lista alfabetica dei topics dei Forum ' . $forum_cor . '</h2><br/>';
            }
         }
         
         //echo "Lista_tmp: " . $lista_tmp . "<br>";
         // CONTROLLO DEI PERMESSI DI LETTURA DEI FORUM 
         $forums_id = array();
         $sql = "SELECT forum_id, forum_type, forum_name FROM " . FORUMS_TABLE . " WHERE forum_id IN($lista_tmp) || parent_id IN($lista_tmp)";
         $result = $this->db->sql_query($sql);

         while($row = $this->db->sql_fetchrow($result)) 
         {
            if (!$this->auth->acl_gets('f_list', 'f_read', $row['forum_id']) === false && $row['forum_type'] == FORUM_POST) 
            {
               $forums_id[] = $row['forum_id'];
               $forums_name[] = $row['forum_name'];
            }
         }
         $this->db->sql_freeresult($result);

         $where_list = '' ;
         foreach($forums_id as $forum_cor)
         {
            if ($where_list == '')
            {
               $where_list = $forum_cor ;
            }else{
               $where_list = $where_list . "," . $forum_cor ;
            }
         }
      //echo "Forum da elaborare: " . $where_list ."<br>";
	  //#################################
      //###   FASE DI SELEZIONE MODE  ###
      //#################################
	  switch ($mode) {
        case 1:
          echo "mode=1 - tutti i records";
		   $sql1 = "SELECT 
          t.topic_id, t.icon_id, t.topic_title, t.topic_time, 
          t.topic_moved_id, t.topic_first_poster_name, t.topic_poster, t.topic_views, t.topic_first_poster_colour, t.topic_last_post_id,
          t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour, t.topic_last_post_time,
          f.parent_id, f.forum_id, f.forum_name AS forum_name_cor,
          UCASE(LEFT(t.topic_title, 1)) AS first_char
          FROM ". TOPICS_TABLE." t,". FORUMS_TABLE. " f
          WHERE t.forum_id IN($where_list)
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
          break;
        case 2:
          echo "mode=2 - cerca stringa titolo";
		  $stringa_cor = "%".$cerca."%";
          //QUERY DI SELEZIONE DEI DATI PER STRINGA
          $sql1 = "SELECT 
          t.topic_id, t.icon_id, t.topic_title, t.topic_time, 
          t.topic_moved_id, t.topic_first_poster_name, t.topic_poster, t.topic_views, t.topic_first_poster_colour, t.topic_last_post_id,
          t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour, t.topic_last_post_time,
          f.parent_id, f.forum_id, f.forum_name AS forum_name_cor,
          UCASE(LEFT(t.topic_title, 1)) AS first_char
          FROM ". TOPICS_TABLE." t,". FORUMS_TABLE. " f
          WHERE t.forum_id IN($where_list)
          AND t.topic_title like '".$stringa_cor."'
          AND f.forum_id = t.forum_id
          AND t.topic_moved_id = 0
          AND t.topic_last_post_time  > $data_post
          ORDER BY UCASE(t.topic_title)" ;
          $this->db->sql_query($sql1);
          $result1 = $this->db->sql_query($sql1);
          $current_char = '';
          $bg_row = "bg2";
          $i = 0;
          $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
          //$cerca   = '';
          break;
        case 3:
          echo "mode=3 - argomenti per user_id";
		  //QUERY DI SELEZIONE DEI DATI PER USER_ID
          $sql1 = "SELECT 
          t.topic_id, t.icon_id, t.topic_title, t.topic_time, 
          t.topic_moved_id, t.topic_first_poster_name, t.topic_poster, t.topic_views, t.topic_first_poster_colour, t.topic_last_post_id,
          t.topic_last_poster_id, t.topic_last_poster_name, t.topic_last_poster_colour, t.topic_last_post_time,
          f.parent_id, f.forum_id, f.forum_name AS forum_name_cor,
          UCASE(LEFT(t.topic_title, 1)) AS first_char
          FROM ". TOPICS_TABLE." t,". FORUMS_TABLE. " f
          WHERE t.forum_id IN($where_list)
          AND t.topic_poster = '".$user_cor."'
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
          break;
      }
 
     // ELABORAZIONE DEI DATI
	 if(isset($result1))
	 {
     while ($row1 = $this->db->sql_fetchrow($result1))
     {
        if (strchr("0123456789", $row1['first_char']))
        {
           $first_char[$i] = '0';
        }else if (strchr($string, $row1['first_char']) )
        {
           $first_char[$i] = $row1['first_char'];
        }else{
           $first_char[$i]    = "@";
        }
            
        if ($first_char[$i] != $current_char) 
        {
           $first_row[$i]         = true ;
        }else{
           $first_row[$i]         = false ;
        }
        $topic_number[$i]           = $start +$i +1;
        $topic_icon_img[$i]         = (!empty($icons[$row1['icon_id']])) ? "images/icons/".$icons[$row1['icon_id']]['img'] : 'ext/micogian/topic_list/images/empty.gif';
        $topic_id[$i]               = $row1['topic_id'];
        $forum_id[$i]               = $row1['forum_id'];
        $icon_id[$i]                = $row1['icon_id'];
        $topic_title[$i]            = $row1['topic_title'];
        $topic_link[$i]             = append_sid("{$this->root_path}viewtopic.{$this->phpEx}", 't='.$row1['topic_id']);
        $forum_link[$i]             = append_sid("{$this->root_path}viewforum.{$this->phpEx}", 'f='.$row1['forum_id']);
        $forum_name_cor[$i]         = $row1['forum_name_cor'];
        $topic_views[$i]            = $row1['topic_views'];
        $topic_author[$i]           = $row1['topic_first_poster_name'];
        $topic_author_full[$i]      = get_username_string('full', $row1['topic_poster'], $row1['topic_first_poster_name'], $row1['topic_first_poster_colour']);
        $first_post_time[$i]        = $this->user->format_date($row1['topic_time']); //date("d.m.Y",$row['topic_time']);
        $last_post_time[$i]         = $this->user->format_date($row1['topic_last_post_time']);
        $last_post_author_full[$i]  = get_username_string('full', $row1['topic_last_poster_id'], $row1['topic_last_poster_name'], $row1['topic_last_poster_colour']);
        $last_post_link[$i]         = append_sid("{$this->root_path}viewtopic.{$this->phpEx}", "f=" . $row1['forum_id'] . "&amp;p=" . $row1['topic_last_post_id'] . "#p" . $row1['topic_last_post_id']);
        $phpbb_root_path            = $this->root_path;

        // CALCOLA IL NUMERO DELLE RISPOSTE (reply) PER OGNI TOPIC
        $reply = "SELECT post_id FROM " . POSTS_TABLE . " WHERE topic_id = $topic_id[$i]";
        $result2 = $this->db->sql_query($reply);
        $rep= 0;
        while($row2 = $this->db->sql_fetchrow($result2))
        {
           ++$rep;
        }
        $topic_replies[$i]          = $rep;
         

        $current_char   = $first_char[$i] ;
        ++$i;
     }
	 
     $total_topics   = $i;                // totale dei topics selezionati
	 //echo "Total_topics = " . $total_topics . "<br>";
     $end  = $start + $per_page ;
     if($end > $total_topics)
      {
         $end   = $total_topics;
     }
      //echo "start = " . $start . " - end = " . $end . "<br>";       
     for( $j = $start; $j < $end; ++$j)
     {        
        $this->template->assign_block_vars('topic_list', array(
         'TOPIC_NUMBER'              => $j +1,
         'S_FIRST_ROW'               => $first_row[$j],
         'BG_ROW'                    => $bg_row,
         'FIRST_CHAR'                => $first_char[$j],
         'TOPIC_ICON_IMG'            => "<img src='".$phpbb_root_path.$topic_icon_img[$j]. "' alt=''>",
         'TOPIC_TITLE'               => $topic_title[$j],
         'TOPIC_LINK'                => $topic_link[$j],
         'FORUM_LINK'                => $forum_link[$j],
         'FORUM_NAME'                => $forum_name_cor[$j],
         'TOPIC_REPLIES'             => $topic_replies[$j],
         'TOPIC_VIEWS'               => $topic_views[$j],
         'TOPIC_AUTHOR'              => $topic_author[$j],
         'TOPIC_AUTHOR_FULL'         => $topic_author_full[$j],
         'FIRST_POST_TIME'           => $first_post_time[$j],
         'LAST_POST_TIME'            => $last_post_time[$j], 
         'LAST_POST_AUTHOR_FULL'     => $last_post_author_full[$j],
         'LAST_POST_LINK'            => $last_post_link[$j], 
         ));
     }  
         
         //####################################################       
         $total_pages    = ceil($total_topics / $per_page);  // totale pagine
         $end_topic      = $start + $per_page ;            // ultimo record della lista
         if( $start == '' or $start == 0)
         {
            $start      = 0 ;
            $page_cor   = 1 ;
         }else{
            $page_cor   = (($start / $per_page) + 1) ;
         }
         if($end_topic > $total_topics )
         {
            $end_topic   = $total_topics;
         }
	 
         // NUMERATORI DI PAGINA
         if( $total_pages == 1 )
         {
            //
            // UNA SOLA PAGINA
            $limit      = '';
            $multipages   = false ;
            $this->template->assign_block_vars('page_topiclist', array(
               'PAGE_NUMBER'   => '',
               'PAGE_URL'      => '',
               'S_IS_CURRENT'   => false,
               'S_IS_PREV'      => false,
               'S_IS_NEXT'      => false,
               'S_IS_ELLIPSIS'   => false,
            ));

         }
           //else
         
         elseif( $total_pages > 1 && $total_pages < 11 )
         {
            //echo "MODE 2 - Totale pagine: " . $total_pages . " page_cor: " . $page_cor . "<br>";
            // NUMERO DI PAGINE INFERIORE A 11
            $multipages   = true ;
            for($p = 1; $p < 11 ; ++$p )
            {
               if( $p <= $total_pages)
               {
                 if( $p == $page_cor)
                 {
                  $page_view[$p]       = true ;
                  $page_number[$p]     = $p;
                  $is_current[$p]      = true ;
                  $is_next[$p]         = false ;
                  $is_prev[$p]         = false ;
                  $start_page[$p]      = ($per_page * ($p -1));
                  $page_url[$p]        = $base_url . "&amp;page=" . $page_number[$p] ;
                 }else{
                  $page_view[$p]       = true ;                  
                  $page_number[$p]     = $p ;
                  $start_page[$p]      = ($per_page * ($p -1));
                  $page_url[$p]        = $base_url . "&amp;page=" . $page_number[$p] ;
                  $is_current[$p]      = '' ;
                 }
                 $this->template->assign_block_vars('page_topiclist', array(
                 'PAGE_NUMBER'       => $page_number[$p],
                 'PAGE_URL'          => $page_url[$p] ,
                 'S_IS_CURRENT'      => $is_current[$p],
                 ));
                 //echo "Mode 2 - start_page " . $p . " = " . $start_page[$p] . " <br>"; 
               }                  
            }           
         }
         elseif( $total_pages > 10)  // TOTALE PAGINE SUPERIORE A 10 
         {
            //echo "MODE 3 - Totale pagine: " . $total_pages . " page_cor: " . $page_cor . "<br>";
            $multipages   = true ;
			
            // Pagina corrente tra le prime 10  
            if( $page_cor < 11)
            {				
               for($p = 1; $p < 11 ; ++$p )
               {
                  if( $p <= $total_pages)
                  {
                    if( $p == $page_cor)
                    {
                      $page_view[$p]       = true ;
                      $page_number[$p]     = $p;
                      $is_current[$p]      = true ;
                      $is_next[$p]         = false ;
                      $is_prev[$p]         = false ;
                      $start_page[$p]      = ($per_page * ($p -1));
                      $page_url[$p]        = $base_url . "&amp;page=" . $page_number[$p] ;
                    }else{
                      $page_view[$p]       = true ;                  
                      $page_number[$p]     = $p ;
                      $start_page[$p]      = ($per_page * ($p -1));
                      $page_url[$p]        = $base_url . "&amp;page=" . $page_number[$p] ;
                      $is_current[$p]      = '' ;
                    }
                    $this->template->assign_block_vars('page_topiclist', array(
                    'PAGE_NUMBER'       => $page_number[$p],
                    'PAGE_URL'          => $base_url . "&amp;page=" . $page_number[$p] ,
                    'S_IS_CURRENT'      => $is_current[$p],
                    ));
                    //echo "Mode 2 - start_page " . $p . " = " . $start_page[$p] . " <br>"; 
                  }                  
               }
			   $this->template->assign_block_vars('page_topiclist', array(
               'S_IS_ELLIPSIS'      => true,
               ));			
               $this->template->assign_block_vars('page_topiclist', array(
               'PAGE_NUMBER'       => $total_pages,
               'PAGE_URL'          => $base_url . "&amp;page=" . $total_pages,
               ));
            }			   
			else
			{
			   for($p = 1; $p < 11 ; ++$p )
               {
                   $page_view[$p]       = true ;                  
                   $page_number[$p]     = $p ;
                   $start_page[$p]      = ($per_page * ($p -1));
                   $page_url[$p]        = $base_url . "&amp;page=" . $page_number[$p] ;
                   $is_current[$p]      = true ;
               
                    $this->template->assign_block_vars('page_topiclist', array(
                    'PAGE_NUMBER'       => $page_number[$p],
                    'PAGE_URL'          => $base_url . "&amp;page=" . $page_number[$p] ,
                    ));
                    //echo "Mode 2 - start_page " . $p . " = " . $start_page[$p] . " <br>";                   
               }
               $page_view[11]           = '' ;
               $page_number[11]         = '';
               $start_page[11]          = '';
               $page_url[11]            = '';
			   $is_current[11]          = '' ;
			   $ellipsis[11]            = true ;
                  
               $page_view[13]           = true ;
               $page_number[13]         = $page_cor;
               $start_page[13]          = (($per_page * $page_cor) - $per_page);
               $page_url[13]            = $base_url . "&amp;page=" . $page_cor ;
               $is_current[13]          = true ;
			   $ellipsis[13]            = '' ;
				
			   if($page_cor == 11)
			   {
				  $page_view[12]           = '' ;
                  $page_number[12]         = '';
                  $start_page[12]          = '';
                  $page_url[12]            = '';
			      $is_current[12]          = '' ;
			      $ellipsis[12]            = true ;   
			   }else{
               $page_view[12]           = true ;
               $page_number[12]         = $page_cor -1;
               $start_page[12]          = (($per_page * $page_cor -1) - $per_page);
               $page_url[12]            = $base_url . "&amp;page=" . $page_cor -1;
			   $is_current[12]          = '' ;
			   $ellipsis[12]            = '' ;
               }
			   
			   $page_view[14]           = true ;
               $page_number[14]         = $page_cor +1;
               $start_page[14]          = (($per_page * $page_cor +1) - $per_page);
               $page_url[14]            = $base_url . "&amp;page=" . $page_cor +1;
               $is_current[14]          = '' ;
               $ellipsis[14]            = '' ;			   
				
               for($p = 11; $p < 15 ; ++$p )
               {
                  $this->template->assign_block_vars('page_topiclist', array(
                     'PAGE_VIEW'         => $page_view[$p],
                     'PAGE_NUMBER'       => $page_number[$p],
                     'PAGE_URL'          => $page_url[$p] ,
                     'S_IS_CURRENT'      => $is_current[$p],
					 'S_IS_ELLIPSIS'     => $ellipsis[$p],
                     ));
               }
               if($page_number[14] < $total_pages)
               {				   
			      $this->template->assign_block_vars('page_topiclist', array(
                  'S_IS_ELLIPSIS'      => true,
                  ));			
                  $this->template->assign_block_vars('page_topiclist', array(
                  'PAGE_NUMBER'       => $total_pages,
                  'PAGE_URL'          => $base_url . "&amp;page=" . $total_pages,
                  ));	
			   }
			} 
         }					
      }else
	  {
		 //echo "Nessun record selezionato <br>";
		 $total_topics          = 1;
		 $total_pages           = 1;
		 $start                 = 0;
		 $total_topic           = 1;
		 $end_topic             = 1;
		 $multipages            = false;
		 $i                     = 1;
		 $mode                  = $mode;
		 $this->template->assign_block_vars('topic_list', array(
         'TOPIC_TITLE'               => "Nessun record. Premi RESET per continuare.",
         ));
	  }		
        if($time_cor = "(Tutti)")
		{
			$time_cor     = 1;
		}			
		$this->template->assign_vars(array(
            'FORUM_LIST_COR'    => $where_list,
            'TOTAL_TOPICS'      => $total_topics,
            'FIRST_TOPIC'       => $start + 1,
            'LAST_TOPIC'        => $end_topic,
            'TOTAL_PAGES'       => $total_pages,
            'PAGE_COR'          => $page_cor,
            'START'             => $start,
            'PER_PAGE'          => $per_page,
            'MULTIPAGES'        => $multipages,
			'MODE_COR'          => $mode,
			'TIME_COR'          => $time_cor,
			'CERCA_COR'         => $cerca,
			'USER_COR'          => $user_cor,
         ));

         //echo "Totale records: " . $i . "<br>";
         $this->template->assign_vars(array(
         'TOT_TOPIC_LIST'    => $i ,
         ));
         $res = "";
         $message   = $res ;
         $rowmessage['MESSAGE']=$message;
         $event['post_row'] = $rowmessage;
      }      
   }   
}
