<?php
/**
 * Router old plugin
 * 
 * @link http://www.shopware.de
 * @copyright Copyright (c) 2011, shopware AG
 * @author Heiner Lohaus
 * @package Shopware
 * @subpackage Plugins
 */
class Shopware_Plugins_Frontend_RouterOld_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
	/**
	 * Install plugin method
	 *
	 * @return bool
	 */
	public function install()
	{		
		$event = $this->createEvent(
	 		'Enlight_Controller_Router_Route',
	 		'onRoute',
	 		10
	 	);
		$this->subscribeEvent($event);
		
		$event = $this->createEvent(
	 		'Enlight_Controller_Router_Assemble',
	 		'onAssemble',
	 		10
	 	);
		$this->subscribeEvent($event);
		
		return true;
	}
	
	/**
	 * Event listener method
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public static function onRoute(Enlight_Event_EventArgs $args)
	{
		$request = $args->getRequest();
		$url = $request->getPathInfo();
		$url = trim($url, '/');
		
		$query = array();
		if(preg_match('#.*?_(detail)_([0-9]+)(?:_([0-9]+))?_?(?:SESS\-(.*?))?.html#', $url, $match)) {
			$query['sViewport'] = $match[1];
			$query['sArticle'] = $match[2];
			$query['sCategory'] = $match[3];
			$query['sCoreId'] = $match[4];
		} elseif(preg_match('#.*?_(cat)_([0-9]+)(?:_([0-9]+))?_?(?:SESS\-(.*?))?.html#', $url, $match)) {
			$query['sViewport'] = $match[1];
			$query['sCategory'] = $match[2];
			$query['sPage'] = $match[3];
			$query['sCoreId'] = $match[4];
		} elseif(preg_match('#.*?_(campaign)_([0-9]+)_?(?:SESS\-(.*?))?.html#', $url, $match)) {
			$query['sViewport'] = $match[1];
			$query['sCampaign'] = $match[2];
			$query['sCoreId'] = $match[4];
		} elseif(preg_match('#unternehmen/.*?_(custom)_([0-9]+)_?([0-9]+)?_?(?:SESS\-(.*?))?.html#', $url, $match)) {
			$query['sViewport'] = $match[1];
			$query['sCustom'] = $match[2];
			$query['sCoreId'] = $match[4];
			if(!empty($match[3])) {
				$query['sId'] = $match[3];
			}
		} elseif(preg_match('#Artikelindex.*_(.*).html#', $url, $match)) {
			$query['sViewport'] = 'search';
			$query['sSearchMode'] = 'bychar';
			$query['sSearchChar'] = $match[1];
			$query['sSearchText'] = 'Artikelindex-'.$match[1];
		} elseif(preg_match('#Supplier-(.*)_(.*).html#', $url, $match)) {
			$query['sViewport'] = 'search';
			$query['sSearchMode'] = 'supplier';
			$query['sSearch'] = $match[2];
			$query['sSearchText'] = $match[1];
		} else {
			foreach(explode('/', $url) as $part) {
				$part = explode(',', $part);
				if(!empty($part[0]) && !empty($part[1])) {
					$query[$part[0]] = $part[1];
				}
			}
		}
		
		if(!empty($query) && !empty($query['sViewport'])) {
			$request->setParam('RewriteOld', true);
			return $query;
		} else {
			return;
		}
	}
	
	/**
	 * Event listener method
	 *
	 * @param Enlight_Event_EventArgs $args
	 */
	public static function onAssemble(Enlight_Event_EventArgs $args)
	{
		$query = $args->getParams();
		
		if(!empty($query['module']) && $query['module']!='frontend') {
			return;
		}
		if(!empty($query['title'])) {
			$title = $query['title'];
		} elseif (!empty($query['sViewport']) && $query['sViewport']=='detail') {
			$title = Shopware()->Modules()->Articles()->sGetArticleNameByArticleId($query['sArticle']);
		} elseif (!empty($query['sViewport']) && $query['sViewport']=='cat') {
			$sql = 'SELECT description FROM s_categories WHERE id=?';
			$title = Shopware()->Db()->fetchOne($sql, array($query['sCategory']));
		}
		unset($query['title'], $query['module']);
		
		if(!empty($query['sAction']) && $query['sAction']=='index') {
			unset($query['sAction']);
		}
		if(!empty($query['sViewport']) && $query['sViewport']=='index') {
			unset($query['sViewport']);
		}
		
		$result = '';
		
		if(!empty($query['sViewport'])) {
			switch ($query['sViewport']) {
				case 'custom':
					$result .= 'unternehmen/';
					$parts = array('sViewport', 'sCustom');
					break;
				case 'detail':
					$parts = array('sViewport', 'sArticle', 'sCategory');
					break;
				case 'cat':
					$parts = array('sViewport', 'sCategory', 'sPage');
					break;
				case 'campaign':
					$parts = array('sViewport', 'sCampaign');
					break;
				case 'search':
					if(!empty($query['sSearchMode']) && $query['sSearchMode'] == 'supplier') {
						$result .= 'Supplier-'.self::sCleanupPath($query['sSearchText']);
						$parts = array('sSearch');
						unset($query['sSearchText'], $query['sSearchMode'], $query['sViewport']);
					}
					break;
				default:
					break;
			}
		}
		
		if(!empty($parts)) {
			if(!empty($title)) {
				$result .= self::sCleanupPath($title);
			}
			foreach ($parts as $key) {
				if(!empty($query[$key])) {
					$result .= '_'.$query[$key];
					unset($query[$key]);
				}
			}
			$result .= '.html';
			if(!empty($query)) {
				$result .= '?'.http_build_query($query, '', '&');
			}
		} elseif(!empty($query)) {
			$result .= Shopware()->Config()->BaseFile;
			$result .= '/';
			if(!empty($query))
			{
				$result .= http_build_query($query, '', '/');
				$result = str_replace('=', ',', $result);
			}
		}
		return $result;
	}
	
	/**
	 * Cleanup path method
	 *
	 * @param string $path
	 * @param bool $remove_ds
	 * @return string
	 */
	public static function sCleanupPath ($path, $remove_ds=true)
	{
		$replace = array(
			' & ' => '-und-',
			'�'=>'ae',
			'�'=>'oe',
			'�'=>'ue',
			'�'=>'Ue',
			'�'=>'Ae',
			'�'=>'Oe',
			'�'=>'ss',
			//'/'=>'-',
			':'=>'-',
			','=>'-',
			"'"=>'-',
			'"'=>'-',
			' '=>'-',
			'+'=>'-',
			//'&'=>'-',
			'�'=>'a',
			'�'=>'a',
			'�'=>'e',
			'�'=>'e',
			'�'=>'u',
			'�'=>'u',
			'�'=>'e',
			'�'=>'c',
			'�'=>'C',
			'&#351;'=>'s',
			'&#350;'=>'S',
			'&#287;'=>'g',
			'&#286;'=>'G',
			'&#304;'=>'i',
		);
		$path = html_entity_decode($path);
		$path = str_replace(array_keys($replace), array_values($replace), $path);
		if($remove_ds) {
			$path = str_replace('/', '-', $path);
		}
		$path = preg_replace('/&[a-z0-9#]+;/i', '', $path);
		$path = preg_replace('#[^0-9a-z-_./]#i','',$path);
		$path = preg_replace('/-+/','-',$path);
		return trim($path,'-');
	}
}