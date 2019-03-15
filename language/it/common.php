<?php
/** 
* 
* @package StaffIt - Topic List
* @copyright (c) 2018 micogian
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2 
* 
*/ 
if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}
$lang = array_merge($lang, array(
	'L_TOPIC_TITLE'			=> 'Titolo',
	'L_TOPIC_AUTHOR'		=> 'Autore',
	'L_TOPIC_DATE'			=> 'Data',
	'L_REPLIES'			=> 'Risposte',
	'L_VIEWS'			=> 'Visite',
	'TITLE_TOPIC_LIST'		=> 'LISTA ALFABETICA DEI TOPICS DEI FORUM',
	'TEXT_TIME_1'			=> 'TUTTO',
	'TEXT_TIME_2'			=> '7 giorni',
	'TEXT_TIME_3'			=> '30 giorni',
	'TEXT_TIME_4'			=> '60 giorni',
	'TEXT_TIME_5'			=> '90 giorni',
	'TEXT_TIME_6'			=> '120 giorni',
	'TEXT_TIME_7'			=> '365 giorni',
));
