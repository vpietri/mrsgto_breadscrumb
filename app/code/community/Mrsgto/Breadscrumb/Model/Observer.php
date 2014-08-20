<?php

/**
 * 
 *
 * @category    OCP
 * @package     Mrsgto_Breadscrumb
 * @author      
 */
class Mrsgto_Breadscrumb_Model_Observer
{
    /**
     * On the product block
     *
     * @param Varien_Object $observer
     * @return Mrsgto_Breadscrumb_Model_Observer
     */
    public function checkProductBreadSrumb($observer)
    {
        $product = $observer->getEvent()->getProduct();
        
        $helper = Mage::helper('mrsgto_breadscrumb');
        
        if (!$helper->refererFromSearch() and !Mage::registry('current_category')) {
        	$productCategoryIds= $product->getCategoryIds();
        	foreach ($productCategoryIds as $productCategory) {
        		if ($product->canBeShowInCategory($productCategory)) {
		            $category = Mage::getModel('catalog/category')->load($productCategory);
		            Mage::register('current_category', $category);
        			break;
        		}
        	}
        }
        
        return $this;
    }
    
    /**
     * On the catalog block
     *
     * @param Varien_Object $observer
     */    
    public function alterProductSearchBreadSrumb($observer)
    {
    	$block = $observer->getEvent()->getBlock();
    	if($block instanceof Mage_Catalog_Block_Breadcrumbs) {
    		$helper = Mage::helper('mrsgto_breadscrumb');
    		$breadcrumbs = $block->getLayout()->getBlock('breadcrumbs');
    		if ( $helper->refererFromSearch() and Mage::registry('current_product')) {
    			
    			if ( Mage::registry('current_category') ) {
    				Mage::unregister('current_category');
    			}
    			
    			$helper->alterSearchBreadcrumb($breadcrumbs);
    		} 
    	}
    }
    		
    
    /**
     * On the catalog search result block
     * 
     * @param Varien_Object $observer
     */
    public function alterSearchBreadSrumb($observer)
    {
    	$block = $observer->getEvent()->getBlock();
    	if($block instanceof Mage_CatalogSearch_Block_Result) {
    		$helper = Mage::helper('mrsgto_breadscrumb');
    		$breadcrumbs = $block->getLayout()->getBlock('breadcrumbs');
	
    		$helper->alterSearchBreadcrumb($breadcrumbs,false);
    	} elseif($block instanceof Mage_Catalog_Block_Breadcrumbs) {
    		$helper = Mage::helper('mrsgto_breadscrumb');
    		$breadcrumbs = $block->getLayout()->getBlock('breadcrumbs');
    		
    		$helper->alterSearchBreadcrumb($breadcrumbs,false);
    	}
    }

}
