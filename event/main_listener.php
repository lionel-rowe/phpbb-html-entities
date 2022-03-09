<?php
/**
 *
 * HTML Entities. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2022, Lionel Rowe, https://github.com/lionel-rowe
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace luoning\htmlentities\event;

/**
 * @ignore
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.text_formatter_s9e_render_after' 		=> 'render_html_entities',
		);
	}

	/* @var \phpbb\language\language */
	protected $language;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language	$language	Language object
	 */
	public function __construct(\phpbb\language\language $language)
	{
		$this->language = $language;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'luoning/htmlentities',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Replace all HTML entities within a string
	 *
	 * @param string	$str	String to process
	 */
	public function replace_html_entities($str)
	{
		$entity = <<<REGEX
			~
			&amp;                       # start with "&"
				(                       # then, any of the following:
					[a-z0-9]+           # - one or more alphanumeric characters
					| \#[0-9]{1,6}      # - or "#" then 1-6 decimal digits
					| \#x[0-9a-f]{1,6}  # - or "#x" then 1-6 hex digits
				)
			;                           # end with ";"
			~ix
		REGEX;

		return preg_replace(
			$entity,
			'&$1;',
			$str,
		);
	}

	/**
	 * Render a string segment
	 *
	 * @param string	$segment	Segment to process
	 * @param int	$idx	Index indicating whether segment should be modified
	 */
	public function render_segment($segment, $idx)
	{
		return $idx % 2
			// odd index: is code block or HTML tag content
			? $segment
			// even index: is text content
			: $this->replace_html_entities($segment);
	}

	/**
	 * Render HTML entities
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function render_html_entities($event)
	{
		// odd indexes are HTML tags and code blocks;
		// even indexes are text content
		$segments = preg_split(
			'/(<code[^>]*>[^<]+<\/code>|<[^>]+>)/',
			$event['html'],
			-1,
			PREG_SPLIT_DELIM_CAPTURE,
		);

		$segments = array_map(
			[$this, 'render_segment'],
			$segments,
			array_keys($segments),
		);

		$event['html'] = implode('', $segments);
	}
}
