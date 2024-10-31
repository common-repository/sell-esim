<?php

namespace tsim\expend\rest_api\open\v1;

//use Tmeister\Firebase\JWT;
use tsim\expend\helper\DbHelper;
use tsim\expend\rest_api\Base;
use tsim\expend\rest_api\OpenApi;

class Product extends Base
{


    public function productDetail(\WP_REST_Request $request)
    {
        $product_id = $request->get_param('product_id') ?? '';
        if (empty($product_id)) {
            return $this->resultError("invalid params", 400);
        }
        // 获取产品对象
        $product = wc_get_product($product_id);

        // 检查产品是否存在
        if (!$product) {
            return $this->resultError('Product not found');
        }
        // 检查产品是否有分类
        $product_categories = wp_get_post_terms($product_id, 'product_cat');
        if (empty($product_categories)) {
            return $this->resultError('Product has no category');
        }
        // 获取产品所属的分类
        $highest_parent_category = '';
        foreach ($product_categories as $val) {
            if ($val->parent == 0) {
                $highest_parent_category = $val->name;
                break;
            }
        }

        /**@var $product \WC_Product_Simple * */
        $image_id = $product->get_image_id();
        $img_obj = wp_get_attachment_image_src($image_id, 'full');
        // 获取产品的基本信息
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_status' => $product->get_stock_status(),
            'image_url' => $img_obj[0] ?? '',
            'categories' => wp_get_post_terms($product_id, 'product_cat', array('fields' => 'names')),
            'highest_parent_category' => $highest_parent_category,
            'attributes' => [], //
        );
        $attributes = $product->get_data()['attributes'] ?? [];
        if (!empty($attributes)) {
            foreach ($attributes as $val) {
                /**@var $val \WC_Product_Attribute * */
                $item = $val->get_data();
                if(($item['variation']??true)){
                    $product_data['attributes'][] = $item;
                }
            }
        }
        if ($product->is_type('variable')) {
            $attribute_combinations = array();
            $variations_data = array();
            $variations = $product->get_available_variations();
            foreach ($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_obj = new \WC_Product_Variation($variation_id);
                $image_id = $variation_obj->get_image_id();
                $img_obj = wp_get_attachment_image_src($image_id, 'full');
                $variation_data = array(
                    'id' => $variation_id,
                    'name' => implode("-", $variation_obj->get_attributes()),
                    'price' => $variation_obj->get_price(),
                    'regular_price' => $variation_obj->get_regular_price(),
                    'sale_price' => $variation_obj->get_sale_price(),
                    'image_url' => $img_obj[0] ?? '',
//                    'categories' => $variation_obj->get_categories(),
//                    'sku' => $variation_obj->get_sku(),
                    'stock_status' => $variation_obj->get_stock_status(),
                );
                $variations_data[] = $variation_data;
            }

            $product_data['variations'] = $variations_data;
            $product_data['asdas'] = $attribute_combinations;
        }
        return $this->result($product_data);
    }

    public function productList(\WP_REST_Request $request)
    {
        $category = $request->get_param('category') ?? '';
        $name = $request->get_param('name') ?? '';
        $page = $request->get_param('page') ?? 1; // 默认为第一页
//        if (empty($category)) {
//            return $this->resultError("invalid params", 400);
//        }
        $dataplan_list = get_option('sellesim_dataplan_list');
        $tsim_sku_list = [];
        foreach ($dataplan_list as $val) {
            $tsim_sku_list[] = $val['channel_dataplan_id'];
        }
        $sku_str = '"' . implode('","', $tsim_sku_list) . '"';

        $prolist = DbHelper::name('wc_product_meta_lookup')->where("sku in ($sku_str) and stock_status = 'instock'")->select();
        $ids = [];
        $pids = [];
        foreach ($prolist as $val) {
            $product = wc_get_product($val->product_id);
            if ($product) {
                $ids[] = $val->product_id;
                $pid = $product->get_parent_id();
                if (!empty($pid)) {
                    $ids[] = $pid;
                }
            }

        }
        if (!empty($name)) {
            $q = DbHelper::name('posts')->where("post_title like '%{$name}%' and post_type like '%product%'")->select();
            $item_id = [];
            foreach ($q as $val) {
                if (in_array($val->ID, $ids)) {
                    $item_id[] = $val->ID;
                }
            }
            $ids = $item_id;

        }
        if (empty($ids)) {
            $ids = [-1];
        }
        $args = array(
            'include' => $ids, //
//            'category' => array($category), //
            'status' => 'publish',
            'paginate' => true, //
            'limit' => 50, //
            'page' => $page, //
            'total' => true, //
        );
        if (!empty($category)) {
            $args['category'] = array($category);
        }

        $products = wc_get_products($args);
        $total_products = $products->total;
        $list = [
            'list' => [],
            'total' => $total_products,
            'page' => $page,

        ];
        if (!empty($products->products)) {
            $product_data = array();
            foreach ($products->products as $product) {
                /**@var \WC_Product $product * */
                $prices = [];
                if ($product->is_type('variable')) {
                    $variations = $product->get_available_variations();
                    foreach ($variations as $variation) {
                        $variation_id = $variation['variation_id'];
                        $variation_product = wc_get_product($variation_id);
                        if ($variation_product) {
                            $prices[] = $variation_product->get_regular_price();
                            $sale_price = $variation_product->get_sale_price();
                            if (!empty($sale_price)) {
                                $prices[] = $variation_product->get_sale_price();
                            }
                        }
                    }
                }
                $has_variable_product = true;
                $main_price = $product->get_price();
                if (!empty($prices)) {
                    $max_price = max(max($prices), $main_price);
                    $min_price = min(min($prices), $main_price);
                } else {
                    $has_variable_product = false;
                    $max_price = $min_price = $main_price;
                }

                // 获取产品信息
                /**@var $product \WC_Product_Simple * */
                $image_id = $product->get_image_id();
                $img_obj = wp_get_attachment_image_src($image_id, 'full');
                $product_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'slug' => $product->get_slug(),
                    'stock_status' => $product->get_stock_status(),
                    'backorders' => $product->get_backorders(),
                    'image_url' => $img_obj[0] ?? '',
                    'sku' => $product->get_sku(),
                    'has_variable_product' => $has_variable_product,
                    'max_price' => $max_price,
                    'min_price' => $min_price,
                    'is_variable' => $product->is_type('variable'),
//                    'data' => $product->get_data(),
                );
            }

            $list['list'] = $product_data;
            return $this->result($list);
            // 继续输出其他信息
        }
        return $this->result($list);
    }

    protected function getProduct()
    {
        $args = array(
            "errors" => array(
                'key' => '_product_name', // 产品名称的元数据键名（这里是示例，实际情况根据产品名称的存储方式而定）
                'value' => 1, // 设置产品名称的模糊搜索的关键词
                'compare' => 'LIKE', // 使用LIKE操作符进行模糊搜索
            ),
            "meta_query" => array(),
            "posts_per_page" => 50,
            "paginate" => true,
            "order" => "DESC",
            "orderby" => "date",
            "fields" => "objects",
            "post_type" => "product",
            "category" => array(
                "Uncategorized"
            ),
            "total" => true,
            "post_status" => "publish",
            "paged" => "1",
            "post__in" => array(
                126
            ),
            "date_query" => array(),

            "tax_query" => array(
                array(
                    "taxonomy" => "product_type",
                    "field" => "slug",
                    "terms" => array(
                        0 => "simple",
                        1 => "grouped",
                        2 => "external",
                        3 => "variable"
                    )
                ),
                array(
                    "taxonomy" => "product_cat",
                    "field" => "slug",
                    "terms" => array(
                        0 => "Uncategorized"
                    )
                )
            )
        );
        $query = new \WP_Query($args);

        $products = array_filter(array_map('wc_get_product', $query->posts));

        if (isset($query_vars['paginate']) && $query_vars['paginate']) {
            return (object)array(
                'products' => $products,
                'total' => $query->found_posts,
                'max_num_pages' => $query->max_num_pages,
            );
        }
    }

    public function category(\WP_REST_Request $request)
    {
        $parent = $request->get_param('parent') ?? '';
        $args = [
            'taxonomy' => 'product_cat', //
//            'slug' => 'esim', //
            'hide_empty' => false, //
            'orderby' => 'name',         // 按名稱排序
            'order' => 'asc',
        ];
        if (!empty($parent) || (int)$parent === 0) {
            $args['parent'] = $parent;
        }
        $product_categories = get_terms($args);
        // 检查是否成功获取了产品分类列表
        if (!empty($product_categories) && !is_wp_error($product_categories)) {
            $data = [];
            foreach ($product_categories as $val) {
                /**@var \WP_Term $val * */
                $item = [
                    'term_id' => $val->term_id,
                    'name' => htmlspecialchars_decode($val->name),
                    'slug' => $val->slug,
                    'description' => $val->description,
                    'parent' => $val->parent,
                ];
                $data[] = $item;
            }
            return $this->result($data);
        }
        if (is_wp_error($product_categories)) {
            // 输出错误信息
            return $this->resultError("product not found");

        }
        return $this->result([]);
    }

    public function banner(\WP_REST_Request $request)
    {
        $banner_list = DbHelper::name('posts')->where("post_status = 'draft' and post_type= 'product' and post_title like '%banner%'")->order('post_title desc')->select();
        $list = [];
        foreach ($banner_list as $val) {
            $product = wc_get_product($val->ID);
            $image_id = $product->get_image_id();
            $img_obj = wp_get_attachment_image_src($image_id, 'full');
            $img_url = $img_obj[0] ?? '';
            if (!empty($img_url)) {
                $item = [
                    'image_url' => $img_url,
                    'url' => $product->get_sku(),
                ];
                $list[] = $item;
            }
        }
        return $this->result($list);
    }
}