<?php

class FileClass {

    public $file     = '';
    public $fields   = array();
    public $products = array();
    private $empty_fields = array();
    private $counter = 0;
    private $category_slug = '';
    private $db_prefix = 'mm970';
    private $category_id = 0;
    private $media_id = 0;

    public function __construct($_file_path = '', $fields = array(), $products = array() )
    {
        $this->_file_path = $_file_path;
        $this->fields = $fields;
        $this->products = $products;
    }

    public function parse_file($_file_path = '')
    {
        $this->file = fopen($this->_file_path ,"r");
        if ( $this->file == false )
        {
            die('Error trying to open file '. $this->file .'.<br /> No such file or directorie.');
        }
        
        while ( !feof($this->file) )
        {
            if ( $this->counter == 0 )
            {
                foreach (fgetcsv($this->file) as $key => $value) {
                    array_push($this->fields, $value);
                }
            } else {
                foreach (fgetcsv($this->file) as $key => $value) {
                    $this->check_empty_fields($key, $value);
                    $this->products[$this->counter][$this->fields[$key]] = $value;
                }
            }
            $this->counter++;
        }
    }

    public function print_results()
    {
        echo '<h2>Number of fields: ' . count($this->fields) . '</h2><br /><h2>Number of products: ' . count($this->products) . '</h2><br />';
        echo '<pre>'; print_r($this->fields); echo '</pre>';
        echo '<pre>'; print_r($this->products); echo '</pre>';
        $this->print_empty_fields();
    }

    private function check_empty_fields($key, $value)
    {
        if ( strcmp($value, '') == 0 ) if ( !in_array($this->fields[$key], $this->empty_fields) ) array_push( $this->empty_fields, $this->fields[$key] );
        if ( strcmp($value, '') != 0 ) if ( in_array($this->fields[$key], $this->empty_fields) ) unset( $this->empty_fields[$this->fields[$key]] );
    }

    public function print_empty_fields()
    {
        echo '<br />empty csv fields: <br />';
        foreach ($this->empty_fields as $key) {
            echo $key . '<br />';   
        }
    }

    public function get_product()
    {
        $this->counter = 1;
        foreach ($this->products as $key => $value) {

            $db = JFactory::getDbo();
            $this->insert_products($db);
            $product_id = $db->insertid();
            $this->insert_products_pt_pt($db, $product_id);
            $this->insert_products_manufacturer($db, $product_id);
            $this->insert_products_category($db, $product_id);
            $this->insert_product_custom_fields($db, $product_id);
            $this->insert_virtuermart_medias($db, $product_id);
            $this->insert_product_medias($db, $product_id);
            $this->insert_product_prices($db, $product_id);
            $this->counter++;
        }
        echo 'done';
    }

    private function insert_product_custom_fields($db, $product_id = 0)
    {
        /*
        *   INSERT INTO on273_virtuemart_product_customfields
        */
        $query = $db->getQuery(true); 
        
        $columns = array(
            'virtuemart_product_id',
            'virtuemart_category_id',
            'ordering'
            );

        $values = array( 
            $product_id,
            $this->category_id,
            '0'
            );

        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_product_customfields'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        //echo '<br />' . $query . '<br />';
        $db->setQuery($query);
        $db->query();
    }

    private function insert_products($db)
    {
        $query = $db->getQuery(true);
        $query->select("virtuemart_category_id")
        ->from($this->db_prefix . "_virtuemart_categories_pt_pt")
        ->where("category_name LIKE '" . $this->products[$this->counter]['Category']  ."'");
        $db->setQuery($query);
        $virtuemart_category_id = $db->loadObjectList();
        $category_id = $virtuemart_category_id[0]->{'virtuemart_category_id'};
        $this->category_id = $category_id;
        $query = $db->getQuery(true); 
        
        $columns = array(
            'pordering',
            'virtuemart_vendor_id',
            'product_parent_id',
            'product_sku',
            'product_weight',
            'product_weight_uom',
            'product_length',
            'product_width',
            'product_height',
            'product_lwh_uom',
            'product_in_stock',
            'product_ordered',
            'low_stock_notification',
            'product_available_date',
            'product_special',
            'product_sales',
            'product_unit',
            'product_packaging',
            'product_params',
            'hits',
            'layout',
            'published'
            );

        $values = array( 
            0,
            1,
            0,
            $db->quote($this->products[$this->counter]['Slug']),
            $db->quote($this->products[$this->counter]['Weight']),
            $db->quote('KG'),
            10.000,
            0,
            0,
            $db->quote('M'),
            $db->quote('1'),
            0,
            5,
            $db->quote('2013-01-11 00:00:00'),
            0,
            0,
            $db->quote('KG'),
            0,
            $db->quote('min_order_level=""|max_order_level=""|step_order_level=""|product_box=""|'),
            0,
            0,
            1
            );

        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_products'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        //echo '<br />' . $query . '<br />';
        $db->setQuery($query);
        if ( $db->query() ) {
            return $db->insertid();    
        } else {
            die("Error retrieving an id for the current product.");
        }
            
    }

    private function insert_products_manufacturer($db, $product_id = 0)
    {
            /*
            *   INSERT INTO on273_virtuemart_products_manufacturers
            */
      
            $query = $db->getQuery(true); 
            
            $columns = array(
                'virtuemart_product_id',
                'virtuemart_manufacturer_id'
                );

            $values = array( 
                $product_id,
                '1'
                );

            $query
            ->insert($db->quoteName($this->db_prefix . '_virtuemart_product_manufacturers'))
            ->columns($db->quoteName($columns))
            ->values(implode(',', $values));
            
            $db->setQuery($query);
            //echo '<br />' . $query . '<br />';
            $db->query();
    }

    private function insert_virtuermart_medias($db, $product_id)
    {
        /*
        *   INSERT INTO on273_virtuemart_medias
        */
        $query = $db->getQuery(true); 
        
        $columns = array(
            'file_url',
            'file_url_thumb',
            'virtuemart_vendor_id',
            'file_title',
            'file_mimetype',
            'file_type',
            'file_is_product_image',
            'file_is_downloadable',
            'file_is_forSale',
            'shared',
            'published',
            'created_on',
            'created_by',
            'modified_on',
            'modified_by',
            'locked_on',
            'locked_by'
            );
        
        $values = array( 
            $db->quote( 'images/product' . str_replace('http://ervanariamaringa.pt/wp-content', '', $this->products[$this->counter]['Image']) ),
            $db->quote( 'images/product' . str_replace('http://ervanariamaringa.pt/wp-content', '', $this->products[$this->counter]['Image']) ),
            1,
            $db->quote( $this->products[$this->counter]['Product Name'] ),
            $db->quote( 'image/jpeg' ),
            $db->quote( 'product' ),
            $db->quote( 'product' ),
            0,
            0,
            0,
            1,
            $db->quote('2013-01-11 00:00:00'),
            0,
            $db->quote('2013-01-11 00:00:00'),
            0,
            $db->quote('2013-01-11 00:00:00'),
            0
            );

        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_medias'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        //echo '<br />' . $query . '<br />';
        $db->setQuery($query);
        $db->query();
        $this->media_id = $db->insertid();
    }

    private function insert_product_medias($db, $product_id)
    {
        $query = $db->getQuery(true); 
        
        $columns = array(
            'virtuemart_product_id',
            'virtuemart_media_id',
            'ordering'
            );

        $values = array( 
            $product_id,
            $this->media_id,
            '0'
            );
        
        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_product_medias'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        //echo '<br />' . $query . '<br />';
        $db->setQuery($query);
        $db->query();
    }

    private function insert_product_prices($db, $product_id)
    {
        $query = $db->getQuery(true); 
        
        $columns = array(
            'virtuemart_product_id',
            'virtuemart_shoppergroup_id',
            'product_price',
            'override',
            'product_override_price',
            'product_tax_id',
            'product_discount_id',
            'product_currency',
            'product_price_publish_up',
            'product_price_publish_down',
            'price_quantity_start',
            'price_quantity_end',
            'created_on',
            'created_by',
            'modified_on',
            'modified_by',
            'locked_on',
            'locked_by'
            );

        $values = array( 
            $product_id,
            0,
            $this->products[$this->counter]['Price'],
            0,
            $this->products[$this->counter]['Price'],
            0,
            -1,
            144,
            $db->quote('2013-01-11 00:00:00'),
            $db->quote('2013-01-11 00:00:00'),
            0,
            0,
            $db->quote('2013-01-11 00:00:00'),
            0,
            $db->quote('2013-01-11 00:00:00'),
            0,
            $db->quote('2013-01-11 00:00:00'),
            0,
            );
        
        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_product_prices'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        //echo '<br />' . $query . '<br />';
        $db->setQuery($query);
        $db->query();
    }

    private function insert_products_pt_pt($db, $product_id = 0)
    {
        /*
        *   INSERT INTO on273_virtuemart_products_pt_pt
        */

        $query = $db->getQuery(true); 
        
        $columns = array(
            'virtuemart_product_id',
            'product_s_desc',
            'product_desc',
            'product_name',
            'slug'
            );

        $values = array( 
            $product_id,
            $db->quote($this->products[$this->counter]['Additional Description']),
            $db->quote($this->products[$this->counter]['Description']),
            $db->quote($this->products[$this->counter]['Product Name']),
            $db->quote($this->products[$this->counter]['Slug'])
            );

        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_products_pt_pt'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        $db->setQuery($query);
        //echo '<br />' . $query . '<br />';
        $db->query();
    }

    private function insert_products_category($db, $product_id = 0)
    {
        /*
        *   INSERT INTO on273_virtuemart_product_categories
        */
        $query = $db->getQuery(true); 
        
        $columns = array(
            'virtuemart_product_id',
            'virtuemart_category_id',
            'ordering'
            );

        $values = array( 
            $product_id,
            $db->quote( $this->category_id ),
            $db->quote('0')
            );

        $query
        ->insert($db->quoteName($this->db_prefix . '_virtuemart_product_categories'))
        ->columns($db->quoteName($columns))
        ->values(implode(',', $values));
        
        $db->setQuery($query);
        //echo '<br />' . $query . '<br />'; 
        $db->query();
    }

    public function __destruct()
    {
        fclose($this->file);
    }
    
}