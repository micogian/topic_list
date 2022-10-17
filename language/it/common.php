<?php
if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}
$lang = array_merge($lang, array(
	'TOPIC_TITLE'			=> 'Titolo',
	'FORUM_TITLE'			=> 'Forum',
	'TOPIC_AUTHOR'			=> 'Autore',
	'TOPIC_DATE'			=> 'Data',
	'REPLIES'				=> 'Risposte',
	'VIEWS'					=> 'Visite',
	'LAST_MESSAGE'		 	=> 'Ultimo messaggio',
	'TITLE_TOPIC_LIST'		=> 'LISTA ALFABETICA DEI TOPICS DEI FORUM',
	'TEXT_TIME_1'			=> 'TUTTO',
	'TEXT_TIME_2'			=> '7 giorni',
	'TEXT_TIME_3'			=> '30 giorni',
	'TEXT_TIME_4'			=> '60 giorni',
	'TEXT_TIME_5'			=> '90 giorni',
	'TEXT_TIME_6'			=> '120 giorni',
	'TEXT_TIME_7'			=> '365 giorni',
	'TIME_SEARCH'           => 'Periodo di ricerca',
	'TITLE_SEARCH'          => 'Ricerca per titolo',
	'SELECT_AUTHOR'         => 'Seleziona argomenti per Autore',
	'TEXT_SEARCH'           => 'Cerca qui ... (min. 3 car.)',
	'SEARCH_USER'           => 'user_id ...',
	'ALL_TOPICS'            => '(0 = TUTTI I TOPICS)',
	'TOPICS_SELECTED'       => 'Argomenti selezionati',
	'GO_TO_PAGE'            => 'Vai alla pagina',
));
