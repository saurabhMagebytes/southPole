<?php
namespace Iwmart\ApiSyncing\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Iwmart\ApiSyncing\Helper\ProductHelper;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;

class Data extends AbstractHelper
{	
    protected $productRepository;
    protected $directoryList;
    protected $category;
    protected $product;
    protected $_mediaDirectory;
    protected $filesystem;
    protected $websiteCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Model\Category $category,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Filesystem $filesystem,
        ProductHelper $productHelper,
        WebsiteCollectionFactory $websiteCollectionFactory
    ) {
        parent::__construct($context);
        $this->productRepository    = $productRepository;
        $this->directoryList    = $directoryList;
        $this->category    = $category;
        $this->product    = $product;
        $this->productHelper = $productHelper;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->websiteCollectionFactory = $websiteCollectionFactory;
    }
    /*
     * @return bool
     */
    public function isEnabled($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->isSetFlag(
            'iwmart_apisyncing/general/enabled',
            $scope
        );
    }

    /*
     * @return string
     */
    public function getApiProjectsUrl($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/webservice_projectsurl',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getApiCreditsUrl($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/webservice_creditsurl',
            $scope
        );
    }
    /*
     * @return string
     */
    public function getToken($scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        return $this->scopeConfig->getValue(
            'iwmart_apisyncing/oauth/access_token',
            $scope
        );
    }
    /*
     * @return array
     */	
    public function getApiServiceData($apiUrl,$apiKey)
    {
        if(!$this->isEnabled()) return array('');

        $headers = array(
            "GET",
            "Content-Type: application/json; charset=utf-8",
            "X-API-Key: ".$apiKey
        ); 

        $url = $apiUrl;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch); 
        curl_close($ch);
        $allDataResultArr = json_decode($response, true);
        return $allDataResultArr;
    }
    /*
     * @return array
     */	
    public function getProjectsData()
    {
        if(!$this->isEnabled()) return array(''); 
        $apiUrl = $this->getApiProjectsUrl();
        $apiKey = $this->getToken();
        $dataResultArr = $this->getApiServiceData($apiUrl,$apiKey);
        return $dataResultArr;
    }
    /*
     * @return array
     */	
    public function getProjectData($project_no)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/projectApiDetail.log');
        $logger = new \Zend_Log();
		$logger->addWriter($writer);
        if(!$this->isEnabled()) return array(''); 
        $apiUrl = $this->getApiProjectsUrl().'/'.$project_no;
        $apiKey = $this->getToken();
        $dataResultArr = $this->getApiServiceData($apiUrl,$apiKey);
        $logger->info(print_r($dataResultArr,true));
        return $dataResultArr;
    }
    /*
     * @return array
     */	
    public function getCreditsData($project_no)
    {
        if(!$this->isEnabled()) return array(''); 
        $apiUrl = $this->getApiCreditsUrl().'/'.$project_no;
        $apiKey = $this->getToken();
        $dataResultArr = $this->getApiServiceData($apiUrl,$apiKey);
        return $dataResultArr;
    }
    /*
     * @return array
     */	
    public function getAllCreditsData()
    {
        if(!$this->isEnabled()) return array(''); 
        $apiUrl = $this->getApiCreditsUrl();
        $apiKey = $this->getToken();
        $dataResultArr = $this->getApiServiceData($apiUrl,$apiKey);
        return $dataResultArr;
    }
    /*
     * @return string
     */	
    public function importData($data)
    {
         $projectDetailsArr = $this->getProjectData($data['project_no']);
         $creditsDataArr = $this->getCreditsData($data['project_no']);
         $language = 'en';		 
         $result = $this->importProductData($projectDetailsArr,$creditsDataArr,$language);
		 //echo $result;  die;
         return $result;
    }
    /*
     * @return string
     */	
    public function UpdateStockData($data)
    {
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance();	
         $checkProduct = $objectManager->create('Magento\Catalog\Model\Product');
         if ($productId = $checkProduct->getIdBySku($data['project_no']))
         {
           $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId)->setStoreId(0);
           $creditsDataArr = $this->getCreditsData($data['project_no']);
           try
           {
              $is_in_stock = 1;
              // credits code here
              $optionvalues = [];
              $qtyStock = 0;
              if(!empty($creditsDataArr)){
                $i=0;
                $keys = array_column($creditsDataArr, 'vintage_year');
                array_multisort($keys, SORT_ASC, $creditsDataArr);
                foreach($creditsDataArr as $creditsData){
                    $is_default = 0;
                    if($i==0) $is_default = 1;				
                    $optionvalues[] = array('title' => $creditsData['vintage_year'],'price' => '','price_type' => 'fixed','sku' => '','qty' => $creditsData['volume'],'manage_stock' => 1,'sort_order' => $i);
                    $qtyStock = $qtyStock+$creditsData['volume'];
                    $i++;
                }
              }
			  
              $_product->setStockData(array(
                   'use_config_manage_stock' => 0,
                   'manage_stock' => 1,
                   'min_sale_qty' => 0.1,
                   'max_sale_qty' => 1000000,
                   'is_in_stock' => $is_in_stock,
                   'is_qty_decimal' => 1,
                   'qty' => $qtyStock
               ));

              /*$price = 8;		   
              $_product->setPrice($price);*/
              $_product->save();		  
              // credits code here
              if(!empty($optionvalues)){
                  $availableVintageintage = false;
                  $newValues = array();
                  foreach ($_product->getOptions() as $option) {
                      if($option->getTitle() == 'Vintage'){
                          $optionOldValues = $option->getValues();
                          $i = 0;
                          $availableVintageintage = true;
                          foreach($optionOldValues as $value)
                          {
                             $value->setTitle($optionvalues[$i]['title']);
                             $value->setQty($optionvalues[$i]['qty']);
                             $value->setManageStock(1);
                             $value->save();
                             $i++;
                          }
                          $option->save();
                          $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->save($_product);
                      }
                  }  
              }

              if(!$availableVintageintage){
                  //$_product->save();
                      $options = [
                          [
                              'title' => 'Vintage',
                              'type' => 'drop_down',
                              'is_require' => true,
                              'sort_order' => 1,
                              'values' => $optionvalues,
                          ]
                      ];
                      foreach ($options as $arrayOption) {
                            $option = $objectManager->create(\Magento\Catalog\Model\Product\Option::class)
                                ->setProductId($_product->getId())
                                ->setStoreId($_product->getStoreId())
                                ->addData($arrayOption);
                            $option->save();
                            $_product->addOption($option);
                      }
			    }
                return "Upload syncing product id : " . $_product->getId();
		      }        
              catch(\Exception $e)
              {
                  return $e->getMessage();
              }
		}
    }
    /*
     * @return string
     */	
    public function importProductData($projectDetailsArr,$creditsDataArr,$language)
    {
         $websiteCollection = $this->websiteCollectionFactory->create();
         $websiteIds = array();
         foreach($websiteCollection as $website) {
            $websiteIds[] = $website->getWebsiteId();
         }
         $data = $projectDetailsArr;
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance();	

         $catDataArr = [];
         if(!empty($data['mitigation_technology'])) $catDataArr = explode('/', $data['mitigation_technology']);
         $catIdArr = [];
         if(!empty($catDataArr)){
             $parentCatId = 27;
             foreach($catDataArr as $catData){
                 $categoryName = $catData;
                 $catItem = $this->category->getCollection()->addAttributeToFilter('name',$categoryName)->getFirstItem();
                 if($catItem->getId()){
                     $parentCatId = $catItem->getId();
                     $catIdArr[] = $catItem->getId();
                 }else{
                     $catId = $this->createCategory($categoryName,$parentCatId);
                     $parentCatId = $catId;
                     $catIdArr[] = $catId;						 
                 }
             }
         }

         $_product_exist = false;
         $checkProduct = $objectManager->create('Magento\Catalog\Model\Product');
         if ($productId = $checkProduct->getIdBySku($data['project_no']))
         {
             $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId)->setStoreId(0);
             $_product_exist = true;
         } else {
             $_product = $objectManager->create('Magento\Catalog\Model\Product');
         }
		 
         try
         {			  
              $price = 1;
              $is_in_stock = 1;
			  
              $_product->setStatus(1);
              $_product->setTypeId('virtual');
              if(!empty($catIdArr)) $_product->setCategoryIds($catIdArr);
              $_product->setAttributeSetId(4);
              $_product->setWebsiteIds($websiteIds);
              $_product->setVisibility(4);
              if (!$_product_exist) {
                  $_product->setPrice($price);
              }
              
              $details = $data['details'];
              $_productName = $data['official_name'];
              if(!empty($details)){
                 foreach($details as $detail){
                     if(!empty($detail['language']) && strtolower($detail['language']) == $language){
                         $_productName = $detail['name'];
                         $_product->setDescription($detail['story']);
                         $_product->setShortDescription($detail['tagline']);
                         $_product->setProductNameLocalized($data['official_name']);
                         $_product->setSolutionDescription($detail['solution']);
                         $_product->setImpactDescription($detail['impact']);
                         $_product->setMetaDescription($detail['tagline']);
                     }
                 }
              }
		  
              //$_product->setGrouping($data['grouping']); // select
              $grouping_option_id = $this->getSelectOptionId($_product,'grouping',$data['grouping']);
              if($grouping_option_id) $_product->setData('grouping', $grouping_option_id);
					 
              $_product->setLocalName($data['local_name']);

			  $_product->setErType($data['type']); 

              $locations = $data['locations'];
              if(!empty($locations)){
                 foreach($locations as $location){
                     $_product->setCity($location['city']);
                     //$_product->setCountry($locations['country']); // select
                     $country_option_id = $this->getSelectOptionId($_product,'country',$location['country']);
                     if($country_option_id) $_product->setData('country', $country_option_id);
                     //$_product->setIso3166CountryCode($locations['iso3166']); // select
                     $iso3166_option_id = $this->getSelectOptionId($_product,'iso_3166_country_code',$location['iso3166']);
                     if($iso3166_option_id) $_product->setData('iso_3166_country_code', $iso3166_option_id);
					 
                     $_product->setLongitude($location['longitude']);
                     $_product->setLatitude($location['latitude']);
                 }
              }

              $methodologies_option_id = $this->getSelectOptionId($_product,'methodologies',$data['methodologies']);
              if($methodologies_option_id) $_product->setData('methodologies', $methodologies_option_id);
              //$_product->setMethodologies($data['methodologies']); // select		  
              $_product->setMitigationTechnology($data['mitigation_technology']);
              $_product->setMitigationTechnologyId($data['mitigation_technology_id']);
              $_product->setName($_productName);
              $_product->setSku($data['project_no']);
			  $_product->setProjectPartner($data['project_partner']);
			  $_product->setQualityLabel($data['quality_label']);
              // registries attribute code
              $registries = $data['registries'];
              if(!empty($registries)){
                 $registrieHtml = '<div class="registries">';
                 foreach($registries as $registrie){
                    $registrieHtml .= '<ul>';
                    $registrieHtml .= '<li>'.$registrie['name'].' <a href="'.$registrie['registry_link'].'" target="_blank">'.$registrie['registry_number'].'</a></li>';
                    $registrieHtml .= '</ul>';
                 } 
                 $registrieHtml .= '</div>';
                 $_product->setRegistries($registrieHtml); 
              }

              $scale_option_id = $this->getSelectOptionId($_product,'scale',$data['scale']);
              if($scale_option_id) $_product->setData('scale', $scale_option_id);
			  //$_product->setScale($data['scale']); // select
              // sdgs attribute code
              $sdgs = $data['sdgs'];
              if(!empty($sdgs)){
                 $sdgHtml = '';
                 $sdgTags = array();
                 foreach($sdgs as $sdg){
                   if(!empty($sdg) && strtolower($sdg['language']) == $language){
                   $sdgTags[] = $sdg['tag_name']; 
                      if($sdg['dimension'] == '128x128' && $sdg['type'] == 'normal'){
                          $sdgHtml .= '<div class="project-sustainable-goals__item"><div class="project-sustainable-goals__image"><img alt="'.$sdg['tag_name'].'" src="'.$sdg['aq_url'].'"></div> <div class="project-sustainable-goals__info"><h4>'.$sdg['sdg_kpi'].'</h4><p>'.$sdg['sdg_description'].'</p></div></div>';
                      }
                   }
                 }
                 $sdgHtml .= '';
                 $_product->setSustainableDevelopmentGoals($sdgHtml);
                 if (count($sdgTags) > 0) {
                        $uniqueSDGTags = array_unique($sdgTags);
                        $sdg_option_ids = $this->getMultiSelectOptionIdsSDG($_product,'sdg_tag',$uniqueSDGTags);
                        if($sdg_option_ids) $_product->setData('sdg_tag', $sdg_option_ids);
                  } 
              }				  

              $standard_option_ids = $this->getMultiSelectOptionIds($_product,'standard',$data['standard']);		  
              if($standard_option_ids) $_product->setData('standard', $standard_option_ids); // multi select
			  
              //$_product->setGrouping($data['grouping']); // select
              $sectoral_scope_option_id = $this->getSelectOptionId($_product,'sectoral_scope',$data['sectoral_scope']);
              if($sectoral_scope_option_id) $_product->setData('sectoral_scope', $sectoral_scope_option_id);
			  
			  $_product->setMetaTitle($data['official_name']);
              $_product->setMetaKeyword($data['local_name']);

              // credits code here
              $optionvalues = [];
              $qtyStock = 0;
              if(!empty($creditsDataArr)){
                $i=0;
                $keys = array_column($creditsDataArr, 'vintage_year');
                array_multisort($keys, SORT_ASC, $creditsDataArr);
                foreach($creditsDataArr as $creditsData){
                    $optionvalues[] = array('title' => $creditsData['vintage_year'],'price' => '','price_type' => 'fixed','sku' => '','qty' => $creditsData['volume'],'manage_stock' => 1,'sort_order' => $i);
                    $qtyStock = $qtyStock+$creditsData['volume'];
                    $i++;
                }
                //$qtyStock = round($qtyStock); 
              }
			  
              $_product->setStockData(array(
                   'use_config_manage_stock' => 0, //'Use config settings' checkbox
                   'manage_stock' => 1, //manage stock
                   'min_sale_qty' => 0.1, //Minimum Qty Allowed in Shopping Cart
                   'max_sale_qty' => 1000000, //Maximum Qty Allowed in Shopping Cart
                   'is_in_stock' => $is_in_stock, //Stock Availability,
                   'is_qty_decimal' => 1,
                   'qty' => $qtyStock
               ));
              
              if(!$_product_exist){                  
			    $images = $data['media'];
                foreach($images as $image){
                   if(!empty($image) && strtolower($image['language']) == $language)
					{
                        if($image['type'] == 'Image'){
                            $imageUrlPath = $image['aq_url'];
                            if($image['file_name'] == "300030_Antai_Waste_Photo_1_women_control_panel.jpg") continue;
                            if(strpos($image['file_name'], "x")) continue;
                            $imageRole = ['image', 'small_image', 'thumbnail'];
						    $returnUploadedImagePath = $this->addImageFromUrl($imageUrlPath);
						    $_product->addImageToMediaGallery($returnUploadedImagePath,$imageRole,false,false);
                            //break;
                        }
					}
			    }
              }
              // else{
              //   $this->updateProductImagesOnUpdate($_product, $data, $language);
              // }
			  $images = $data['media'];
              if(!empty($images)){
                foreach($images as $image){
                   if(!empty($image) && strtolower($image['language']) == $language)
					{
                        if($image['type'] == 'Video'){
                            if($image['aq_url']){
                                $_product->setVideoLink($image['aq_url']);
                            }
                        }
                        if($image['type'] == 'ER webpages'){
                            if($image['aq_url']){
                                $fileurl = $image['aq_url'];
								
                                if (strpos($image['file_name'], ".") !== false)  $fileurl = $this->uploadPdfFromUrl($image['aq_url']);
                                $_product->setErWebpages($fileurl);
                            }
                        }
					}
			    }
              }
              $_product->save();
              // get id of product
              $productId = $_product->getId();
			  
              // credits code here
              if(!empty($optionvalues) && !$_product_exist){
                $options = [
                  [
                    'title' => 'Vintage',
                    'type' => 'drop_down',
                    'is_require' => true,
                    'sort_order' => 4,
                    'values' => $optionvalues,
                  ]
                ];
        
                foreach ($options as $arrayOption) {
                    $option = $objectManager->create(\Magento\Catalog\Model\Product\Option::class)
                        ->setProductId($_product->getId())
                        ->setStoreId($_product->getStoreId())
                        ->addData($arrayOption);
                    $option->save();
                    $_product->addOption($option);
                    //$_product->save();
                }
              }else{
                 if(!empty($optionvalues)){
                    $newValues = array();
                    foreach ($_product->getOptions() as $option) {
                      if($option->getTitle() !== 'Vintage') continue;
                      $optionOldValues = $option->getValues();
                      $i = 0;
                      foreach($optionOldValues as $value)
                      {
                        $value->setTitle($optionvalues[$i]['title']);
                        $value->setQty($optionvalues[$i]['qty']);
                        $value->setManageStock(1);
                        $value->save();
                        $i++;
                      }
                      $option->save();
                    }
                    $objectManager->create('Magento\Catalog\Api\ProductRepositoryInterface')->save($_product);
                  }
              }			  
              return "Upload syncing product id : " . $productId;
        }
        catch(\Exception $e)
        {
           return $e->getMessage();
        }
         return 'project_no : '.$projectDetailsArr['project_no'];
    }

    // function updateProductImagesOnUpdate($_product, $data, $language)
    // {
    //     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    //     $checkProduct = $objectManager->create('Magento\Catalog\Model\Product');
    //     if ($productId = $checkProduct->getIdBySku($data['project_no']))
    //     {
    //         $_product = $objectManager->create('Magento\Catalog\Model\Product')->load($productId)->setStoreId(0);
    //         if($_product->getId()) {
    //             $images = $data['media'];

    //             $images = $data['media'];
    //             foreach ($images as $image) {
    //                 if (!empty($image) && strtolower($image['language']) == $language) 
    //                 {
    //                     if ($image['type'] == 'Image')
    //                     {
    //                         $imageUrlPath = $image['aq_url'];
    //                         $skipImages = ["300030_Antai_Waste_Photo_1_women_control_panel.jpg"];
    //                         if (in_array($image['file_name'], $skipImages) || strpos($image['file_name'], "x") !== false)
    //                         {
    //                             continue;
    //                         }
    //                         $imageRole = ['image', 'small_image', 'thumbnail'];
    //                         $returnUploadedImagePath = $this->addImageFromUrl($imageUrlPath);
    //                         $_product->addImageToMediaGallery($returnUploadedImagePath, $imageRole, false, false);
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // } 

	public function addImageFromUrl($urlToImage)
    {
		$mySaveDir  	= $this->directoryList->getRoot()."/pub/media/temp_upload/";
		$filename 		= basename($urlToImage);
		$completeSaveLoc = $mySaveDir.$filename;
		try {
		    file_put_contents($completeSaveLoc,file_get_contents($urlToImage));
		    return $completeSaveLoc;
		}
		catch (Exception $e){
		    echo $e->getMessage();
		}
	}

	public function uploadPdfFromUrl($urlToPdf)
    {
		$mySaveDir  	= $this->_mediaDirectory->getAbsolutePath('catalog/product/file/');
		$filename 		= basename($urlToPdf);
		$completeSaveLoc = $mySaveDir.$filename;
        $urlToPdf = str_replace(' ', '%20', $urlToPdf);
		if($urlToPdf) {
            file_put_contents($completeSaveLoc, file_get_contents($urlToPdf));
		    return $filename;
		}
		return null;
	}
	
	public function getSelectOptionId($_product,$attributeCode,$attributeTitle)
    {
         $option_id = '';
         if ($attributeTitle) {
             $product_resource = $_product->getResource();
             $attribute = $product_resource->getAttribute($attributeCode);
             if ($attribute->usesSource()) {
                 $option_id = $attribute->getSource()->getOptionId($attributeTitle);
             }
        }
        return $option_id;
	}

    public function getMultiSelectOptionIdsSDG($_product,$attributeCode,$attributeOptions)
    {
         $option_ids = [];
         if ($attributeOptions) {
             $product_resource = $_product->getResource();
             $attribute = $product_resource->getAttribute($attributeCode);
             if ($attribute->usesSource()) {
                  foreach ($attributeOptions as $attribute_value) {
                    if ($this->productHelper->createOrGetId($attributeCode, $attribute_value)) {
                        $option_ids[] = $this->productHelper->createOrGetId($attributeCode, $attribute_value);
                    }
                 }
             }
        }
        return $option_ids;
    }
	
	public function getMultiSelectOptionIds($_product,$attributeCode,$attributeTitle)
    {
         $option_ids = [];
         if ($attributeTitle) {
             $product_resource = $_product->getResource();
             $attribute = $product_resource->getAttribute($attributeCode);
             if ($attribute->usesSource()) {
                  $attribute_values = explode(',', $attributeTitle);
                  foreach ($attribute_values as $attribute_value) {
                    $option_ids[] = $attribute->getSource()->getOptionId($attribute_value);
                 }
             }
        }
        return $option_ids;
	}


    public function createCategory($categoryName,$parentCatId)
    {
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
         $catId = '';
         $parentCategory = $this->category->load($parentCatId);		
         $categoryName = trim($categoryName);
         $category = $objectManager->create('Magento\Catalog\Model\Category');
         try
         {
              //Check exist category
              $cate = $this->category->getCollection()->addAttributeToFilter('name',$categoryName)->getFirstItem();

              $cat_url = strtolower($categoryName);
              $clean_url = trim(preg_replace('/ +/', '', preg_replace('/[^A-Za-z0-9 ]/', '', urldecode(html_entity_decode(strip_tags($cat_url))))));
              if (!$cate->getId())
              {
                  $category->setPath($parentCategory->getPath())
                        ->setUrlKey($clean_url)
                        ->setParentId($parentCatId)
                        ->setName($categoryName)
                        ->setIsActive(true);
                  $category->save();
                  $catId = $category->getId();
              }
              if($catId){
                  return $catId;
              }else{
                  $cate->save();
                  return $cate->getId();		  
              }
        }
        catch(\Exception $e)
        {
           return $e->getMessage();
        }
    }
}   

