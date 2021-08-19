<?php
class ControllerExtensionModulePromotion extends Controller {
	public function index($setting) {
        static $module = 0;
		$this->load->language('extension/module/promotion');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		if (!$setting['count_visible']) {
			$setting['count_visible'] = 4;
		}

        if (!$setting['promotion_text']) {
            $setting['promotion_text'] = 'До окончания:';
        }

		$data['heading_title'] = $setting['name'];
		$data['count_visible'] = $setting['count_visible'];
		$data['promotion_text']= $setting['promotion_text'];

		if (!empty($setting['product_id'])) {
			foreach ($setting['product_id'] as $key => $product_id) {
				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info) {
					if ($product_info['image']) {
						$image = $this->model_tool_image->resize($product_info['image'], 200, 200);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', 200, 200);
					}

					if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$price = false;
					}

					if ((float)$product_info['special']) {
						$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					} else {
						$special = false;
					}

					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $this->session->data['currency']);
					} else {
						$tax = false;
					}

					if ($this->config->get('config_review_status')) {
						$rating = $product_info['rating'];
					} else {
						$rating = false;
					}

					if($setting['date_end'][$key]) {
						$date_end = $this->downcounter($setting['date_end'][$key]);
					}

                    $this->load->model('catalog/category');
                    $this->load->model('catalog/product');
                    $getCategories = $this->model_catalog_product->getCategories($product_info['product_id']);
                    $path = '';

                    $categoriesPaths = array();
                    $max_count = 0;
                    foreach ($getCategories as $getCategory) {
                        $categoriesPaths[] = $this->model_catalog_category->getCategoryPathHighestLevel($getCategory['category_id']);
                    }
                    foreach ($categoriesPaths as $categoriesPath) {
                        if ($max_count < count($categoriesPath)) {
                            $max_count = count($categoriesPath);
                        }
                    }
                    foreach ($categoriesPaths as $key => $categoriesPath) {
                        if ($max_count > count($categoriesPath)) {
                            unset($categoriesPaths[$key]);
                        }
                    }
		            if (!empty($categoriesPaths)) {
		                $min_category_id = 1000000000;
		                $currentCategoryPaths = array();

		                foreach ($categoriesPaths as $key => $item) {
		                    if (isset($item[0]) && isset($item[0]['path_id']) && $item[0]['path_id'] < $min_category_id) {
		                        $min_category_id = $item[0]['path_id'];
		                        $currentCategoryPaths = $item;
		                    }
		                }

		                foreach ($currentCategoryPaths as $kk => $currentCategoryPath) {
		                    if ($kk != (count($currentCategoryPaths) - 1)) {
		                        $path .= $currentCategoryPath['path_id'] . '_';
		                    } else {
		                        $path .= $currentCategoryPath['path_id'];
		                    }
		                }
		            }

                    if(!empty($path)) {
                        $product_link = $this->url->link('product/product', 'path=' . $path . '&product_id=' . $product_info['product_id']);
                    } else {
                        $product_link = $this->url->link('product/product', 'product_id=' . $product_info['product_id']);
                    }

					$data['products'][] = array(
						'product_id'  => $product_info['product_id'],
						'thumb'       => $image,
						'name'        => $product_info['name'],
						'description' => utf8_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
						'price'       => $price,
						'special'     => $special,
						'tax'         => $tax,
						'rating'      => $rating,
						'href'        => $product_link,
						'date_end'    => $date_end
					);
				}
			}
		}

        $data['module'] = $module++;

		if ($data['products']) {
			return $this->load->view('extension/module/promotion', $data);
		}
	}

	protected function downcounter($date){
	    $check_time = strtotime($date) - time();
	    if($check_time <= 0){
	        return false;
	    }

	    $days = floor($check_time/86400);
	    $hours = floor(($check_time%86400)/3600);
	    $minutes = floor(($check_time%3600)/60);
	    $seconds = $check_time%60; 

	    $str = '';
//	    if($days > 0) $str .= $this->declension($days,array('день','дня','дней')).' ';
//	    if($hours > 0) $str .= $this->declension($hours,array('час','часа','часов')).' ';
//	    if($minutes > 0) $str .= $this->declension($minutes,array('минута','минуты','минут')).' ';
//	    if($seconds > 0) $str .= $this->declension($seconds,array('секунда','секунды','секунд'));

        $date = array();

        if($days > 0) $date[] = array(
            'count' => $days,
            'text'  => 'дн.'
        );
        if($hours > 0) $date[] = array(
            'count' => $hours,
            'text'  => 'час.'
        );
        if($minutes > 0) $date[] = array(
            'count' => $minutes,
            'text'  => 'мин.'
        );
        if($seconds > 0) $date[] = array(
            'count' => $seconds,
            'text'  => 'сек.'
        );

	    return $date;
	}

	protected function declension($digit,$expr,$onlyword=false){
	    if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
	    if(empty($expr[2])) $expr[2]=$expr[1];
	    $i=preg_replace('/[^0-9]+/s','',$digit)%100;
	    if($onlyword) $digit='';
	    if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
	    else
	    {
	        $i%=10;
	        if($i==1) $res=$digit.' '.$expr[0];
	        elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
	        else $res=$digit.' '.$expr[2];
	    }
	    return trim($res);
	}
}