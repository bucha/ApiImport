<?php
/*
 * Copyright 2011 Daniel Sloof
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

class Danslo_ApiImport_Helper_Test_V2
{

    /**
     * Stores array of imported simple products used for configurable, bundle and grouped product creation.
     *
     * @var array
     */
    protected $_linkedProducts = null;

    /**
     * Default attributes that are used for every product entity.
     *
     * @var array
     */
    protected $_defaultProductAttributes = array(
		array(
			'key' => 'description',
			'value' => 'Some description'
		),
		array(
			'key' => '_attribute_set',
			'value' => 'Default'
		),
		array(
			'key' => 'short_description',
			'value' => 'Some short description'
		),
		array(
			'key' => '_product_websites',
			'value' => 'german'
		),
		array(
			'key' => 'status',
			'value' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED
		),
		array(
			'key' => 'visibility',
			'value' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
		),
		array(
			'key' => 'tax_class_id',
			'value' => 0
		),
		array(
			'key' => 'is_in_stock',
			'value' => 1
		),
		array(
			'key' => 'tax_class_id',
			'value' => 2
		)
    );

    /**
     * Default attributes that are used for customers.
     *
     * @var array
     */
    protected $_defaultCustomerAttributes = array(
		array(
			'key' => '_website',
			'value' => 'german'
		),
		array(
			'key' => '_store',
			'value' => 'de'
		),
		array(
			'key' => 'group_id',
			'value' => 1
		)
    );

    /**
     * Default attributes that are used for categories. 
     *
     * @var array
     */
    protected $_defaultCategoryAttributes = array(
		array(
			'key' => '_root',
			'value' => 'Default Category'
		),
		array(
			'key' => 'is_active',
			'value' => 'yes'
		),
		array(
			'key' => 'include_in_menu',
			'value' => 'yes'
		),
		array(
			'key' => 'description',
			'value' => 'Category description'
		),
		array(
			'key' => 'meta_description',
			'value' => 'Category meta description'
		),
		array(
			'key' => 'available_sort_by',
			'value' => 'position'
		),
		array(
			'key' => 'default_sort_by',
			'value' => 'position'
		)
    );

	public function arrayToObject($array) {
		if (is_array($array)) {
			return (object) array_map(array($this, 'arrayToObject'), $array);
		} else {
			return $array;
		}
	}

    /**
     * Creates and stores 3 simple products with different values for the color attribute.
     * These products are used for configurable, bundle and grouped product generation.
     *
     * @return array
     */
    protected function _getLinkedProducts()
    {
        // We create 3 simple products so we can test configurable/bundle links.
        if ($this->_linkedProducts === null) {
            $linkedProductsArray = $this->generateRandomSimpleProduct(5);
            // Use the color option for configurables. Note that this attribute must be added to the specified attribute set!
            foreach (array('red', 'yellow', 'green') as $key => $color) {
				$linkedProductsArray[$key][] = array(
					'key' => 'color',
					'value' => $color
				);
            }

			$this->_linkedProducts = $this->arrayToObject($linkedProductsArray);

            Mage::getModel('api_import/import_api_v2')->importEntities($this->_linkedProducts, 'catalog_product', 'append');
        }
        return $this->_linkedProducts;
    }

    /**
     * Generates random simple products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomSimpleProduct($numProducts)
    {
        $products = array();

        for ($i = 0; $i <= $numProducts - 1; $i++) {
            $products[$i] = array_merge_recursive(
                $this->_defaultProductAttributes,
                array(
					array(
						'key' => 'sku',
						'value' => 'some_sku_' . $i
					),
					array(
						'key' => '_type',
						'value' => Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
					),
					array(
						'key' => 'name',
						'value' => 'Some product ( ' . $i . ' )'
					),
					array(
						'key' => 'price',
						'value' => rand(1, 1000)
					),
					array(
						'key' => 'weight',
						'value' => rand(1, 1000)
					),
					array(
						'key' => 'qty',
						'value' => rand(1, 30)
					)
                )
            );
        }

        return $products;
    }

    /**
     * Generates random configurable products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomConfigurableProduct($numProducts)
    {
        $products = array();

        for ($i = 0, $counter = 0; $i <= $numProducts - 1; $i++) {
            // Generate configurable product.
            $products[$counter] = array_merge_recursive(
                $this->_defaultProductAttributes,
                array(
					array(
						'key' => 'sku',
						'value' => 'some_configurable_' . $i
					),
					array(
						'key' => '_type',
						'value' => Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE
					),
					array(
						'key' => 'name',
						'value' => 'Some configurable ( ' . $i . ' )'
					),
					array(
						'key' => 'price',
						'value' => rand(1, 1000)
					),
					array(
						'key' => 'weight',
						'value' => rand(1, 1000)
					)
                )
            );

            // Associate child products.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
				$linkedProduct = (array) $linkedProduct;

				$sku = null;
				$color = null;
				foreach ($linkedProduct as $obj) {
					if ($obj->key == 'sku') {
						$sku = $obj->value;
					} else if ($obj->key == 'color') {
						$color= $obj->value;
					}
				}

                $products[$counter] = array_merge_recursive(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
						array(
							'key' => '_super_products_sku',
							'value' => $sku
						),
						array(
							'key' => '_super_attribute_code',
							'value' => 'color'
						),
						array(
							'key' => '_super_attribute_option',
							'value' => $color
						)
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random bundle products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomBundleProduct($numProducts)
    {
        $products = array();

        for ($i = 1, $counter = 1; $i <= $numProducts; $i++) {
            // Generate bundle product.
            $products[$counter] = array_merge(
                $this->_defaultProductAttributes, array(
                    'sku'        => 'some_bundle_' . $i,
                    '_type'      => Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                    'name'       => 'Some bundle ( ' . $i . ' )',
                    'price'      => rand(1, 1000),
                    'weight'     => rand(1, 1000),
                    'price_view' => 'price range',
                    'price_type' => Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED
                )
            );

            // Create an option.
            $optionTitle = 'Select a bundle item!';
            $products[$counter]['_bundle_option_title'] = $optionTitle;
            $products[$counter]['_bundle_option_type']  = Danslo_ApiImport_Model_Import_Entity_Product_Type_Bundle::DEFAULT_OPTION_TYPE;

            // Associate option selections.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
                $products[$counter] = array_merge(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
                        '_bundle_option_title'        => $optionTitle,
                        '_bundle_product_sku'         => $linkedProduct['sku'],
                        '_bundle_product_price_value' => rand(1, 1000)
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random grouped products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomGroupedProduct($numProducts)
    {
        $products = array();

        // Generate grouped product.
        for ($i = 1, $counter = 1; $i <= $numProducts; $i++) {
            $products[$counter] = array_merge(
                $this->_defaultProductAttributes,
                array(
                    'sku'   => 'some_grouped_' . $i,
                    '_type' => Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                    'name'  => 'Some grouped ( ' . $i . ' )'
                )
            );

            // Associated child products.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
                $products[$counter] = array_merge(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
                        '_associated_sku'         => $linkedProduct['sku'],
                        '_associated_default_qty' => '1', // optional
                        '_associated_position'    => '0'  // optional
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random customers.
     *
     * @param int $numCustomers
     * @return array
     */
    public function generateRandomStandardCustomer($numCustomers)
    {
        $customers = array();

        for ($i = 0; $i < $numCustomers; $i++) {
            $customers[$i] = array_merge(
                $this->_defaultCustomerAttributes,
                array(
                    'email'     => sprintf('%s@%s.com', uniqid(), uniqid()),
                    'firstname' => uniqid(),
                    'lastname'  => uniqid()
                )
            );
        }

        return $customers;
    }

    /**
     * Generates random categories.
     *
     * @param int $numCategories
     * @return array
     */
    public function generateRandomStandardCategory($numCategories)
    {   
        $categories = array();
        
        for ($i = 1; $i <= $numCategories; $i++) {
            $categories[$i - 1] = array_merge(
                $this->_defaultCategoryAttributes,
                array(
                    'name'          => sprintf('Test Category %d', $i),
                    '_category'     => sprintf('Test Category %d', $i),
                    'url_key'       => sprintf('test%d', $i),
                )
            );
        } 

        return $categories;
    }

}
