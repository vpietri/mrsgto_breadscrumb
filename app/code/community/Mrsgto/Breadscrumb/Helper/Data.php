<?php

class Mrsgto_Breadscrumb_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	protected $_refererIsSearch;
	
	protected $_urlVars=array();
	
	protected $_layerContent=array();
	
	protected $_searchQuery='';
	
	protected $_baseUrl='';
	
	public function refererFromSearch()
	{
		if (is_null($this->_refererIsSearch)) {
			$refererUrl = Mage::helper('core/http')->getHttpReferer(true);
			if ($refererUrl and strpos($refererUrl,'catalogsearch/result')!==false) {
				$this->_refererIsSearch= true;
			} else {
				$this->_refererIsSearch= false;
			}
		}
		
		return $this->_refererIsSearch;
	}
	
	
	public function getSearchQuery()
	{
		return $this->_searchQuery;
	}
	
	
	public function alterSearchBreadcrumb($breadcrumbs, $referer=true)
	{
		if($breadcrumbs->getAltered()) {
			return false;
		}
		
		if ($referer) {
			if(is_string($referer)) {
				$url= $referer;
			} else {
				$url = Mage::helper('core/http')->getHttpReferer(true);
			}
		} else {
			$url= Mage::helper('core/url')->getCurrentUrl();
		}
		
		
		$crumbs=$this->getCrumArray($url, $referer);
		
		foreach ($crumbs as $crumKey=>$crumbValue) {
			$breadcrumbs->addCrumb($crumKey,$crumbValue);
		}
		
		$breadcrumbs->setAltered(true);
		
		return $crumbs;
	}
	
	protected function getBreadUrl($params)
	{
		$url = $this->_baseUrl;
		if (!empty($params)) {
			$url .= '?';
			$url .= implode('&', $params);
		}
		return $url;
		
	}
	
	protected function getCrumArray($urlToParse, $referer=true)
	{
		$crumbs=array();
			
		if (empty($urlToParse)) {
			return false;
		}
		
		$objUri= Mage::getModel('core/url')->parseUrl($urlToParse);
		if (empty($objUri)) {
			return false;
		}
			
		parse_str($objUri->getQuery(),$this->_urlVars);
		
		$_filters = Mage::getSingleton('catalog/layer')->getState()->getFilters();
		
		if (!empty($this->_urlVars)) {
				
			$this->_baseUrl= $objUri->getPath();

			$key= false;
			$urlBreadParam=array();
			
			$crumbs['home']=array(
					'label' => $this->__('Home'),
					'title' => $this->__('Go to Home Page'),
					'link'  => Mage::getBaseUrl());
			
			//Check query param
			$queryVar= Mage::helper('catalogsearch')->getQueryParamName();
			if (array_key_exists($queryVar,$this->_urlVars)) {
				$this->_searchQuery= $this->_urlVars[$queryVar];
				

				if ($this->getSearchQuery()) {
					$key='search';
					$urlBreadParam['search'] = Mage::helper('catalogsearch')->getQueryParamName().'='.$this->getSearchQuery();
						
					$title = $this->__("Search results for: '%s'", $this->getSearchQuery());
					$crumbs['search']=array('label' => $title,
							'title' => $title,
							'link'  => $this->_baseUrl.'?'.Mage::helper('catalogsearch')->getQueryParamName().'='.$this->getSearchQuery()
					);
				}
				
				
				unset($this->_urlVars[$queryVar]);
			}
				
			$currentCategory= Mage::registry('current_category');
			
			//Category
			if (array_key_exists('cat',$this->_urlVars)) {
				$varCategoryId= $this->_urlVars['cat'];
				
				$category = Mage::getModel('catalog/category')->load($varCategoryId);
				
				$pathInStore = $category->getPathInStore();
				$pathIds = array_reverse(explode(',', $pathInStore));
				
				$categories = $category->getParentCategories();
				
				$keepCatUrl= ($currentCategory) ? true : false;
				foreach ($pathIds as $categoryId) {
					if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
						$crumbs['category'.$categoryId] = array(
								'label' => $categories[$categoryId]->getName(),
								'link' =>  $keepCatUrl ? $categories[$categoryId]->getUrl() : false,
							    'value'=>$categories[$categoryId]->getId(),
							    'param'=>'cat'
						);
						if ($currentCategory and $categoryId==$currentCategory->getId()) {
							$keepCatUrl= false;
						}
					}
				}
			
				unset($this->_urlVars['cat']);
			}
			
			//Attributs
			$layer= Mage::getModel('catalog/layer');
			$layerAttributes= $layer->getFilterableAttributes();
			foreach ($layerAttributes as $attribute) {
				if (array_key_exists($attribute->getAttributeCode(),$this->_urlVars)) {
					$label = $attribute->getSource()
					->getOptionText($this->_urlVars[$attribute->getAttributeCode()]);
						
					$key=$attribute->getAttributeCode();
			
					$crumbs[$key]= array('label'=>$label,
							'title'=>$attribute->getFrontendLabel(). ': '.$label,
							'value'=>$this->_urlVars[$attribute->getAttributeCode()],
							'param'=>$attribute->getAttributeCode()
					);
					unset($this->_urlVars[$key]);
				}
			}			
			
			foreach ($crumbs as $key=>$layer) {
				if(empty($crumbs[$key]['link'])) {
					$urlBreadParam[$layer['param']] = $layer['param'].'='.$layer['value'];
					$crumbs[$key]['link']= $this->getBreadUrl($urlBreadParam);
					
				}
			}
			
			if (!$referer and $key and !empty($crumbs[$key])) {
				$crumbs[$key]['link'] = null;
			}			
			
		}

		return $crumbs;
	}
}
