<?php
/* Copyright (C) singleview.co.kr <https://singleview.co.kr> */
/**
 * High class of the category module
 * @author singleview.co.kr
 */
namespace X2board\Includes\Modules\Category;

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

if (!class_exists('\\X2board\\Includes\\Modules\\Category\\categoryAdminModel')) {

	class categoryAdminModel {

		/**
		 * @brief constructor
		 **/
		public function __construct(){
// var_dump('categoryAdminModel');
			$o_current_user = wp_get_current_user();
			if( !user_can( $o_current_user, 'administrator' ) || !current_user_can('manage_x2board') ) {
				unset($o_current_user);
				wp_die(__('You do not have permission.', 'x2board'));
			}
			unset($o_current_user);
		}

		/**
		 * WP 관리자 UX용 계층형 카테고리를 반환한다.
		 */
		// public function buildAdminTreeCategorySortableRow() {
		public function build_category_sortable_html() {
			// allocae memorty to save in the DB via saveAdminTreeCategory()
			$a_tree_category_recursive = $this->_construct_category_array();
			$html = null;
			$this->_build_category_sortable($html, $a_tree_category_recursive);
			return $html;
		}

		/**
		 * DB의 메뉴 구조를 계층형으로 변환한다.
		 */
		function _construct_category_array() {
			$n_board_id = esc_sql($_GET['board_id']);
			global $wpdb;
			$results = $wpdb->get_results("SELECT `category_id`, `title`, `parent_id`, `post_count`, `is_default` FROM `{$wpdb->prefix}x2b_categories` WHERE `board_id`='{$n_board_id}' AND `deleted`='N' ORDER BY `list_order` ASC ");
			$wpdb->flush();

			$tree_category_source = [];
			foreach($results as $row){
				$category_id = intval($row->category_id);
				$tree_category_source[$category_id] = ['id' => $category_id, 
													   'parent_id' => $row->parent_id ? intval($row->parent_id) : null,
													   'title' => $row->title, 
													   'post_count' => intval($row->post_count),
													   'is_default' => $row->is_default ? $row->is_default : null];
			}
			unset($results);
			$tree_category_recursive = array();
			foreach($tree_category_source as $item) {
				if(!(isset($item['parent_id']) && $item['parent_id'])) {
					$children = $this->_get_category_recurisve($tree_category_source, $item['id']);
					if($children) {
						$item['children'] = $children;
					}
					$tree_category_recursive[$item['id']] = $item;
				}
			}
			unset($tree_category_source);
			return $tree_category_recursive;
		}

		/**
		 * 재귀적으로 구성된 하위 카테고리를 반환한다.
		 * @param string $parent_id
		 * @return array
		 */
		private function _get_category_recurisve($tree_category_source, $parent_id){
			$a_category = [];
			foreach($tree_category_source as $item){
				if(isset($item['parent_id']) && $parent_id == $item['parent_id']){
					$children = $this->_get_category_recurisve($tree_category_source, $item['id']);
					if($children) $item['children'] = $children;
					$a_category[$item['id']] = $item;
				}
			}
			return $a_category;
		}

		/**
		 * 관리자 페이지의 계층형 카테고리를 HTML로 그린다.
		 * @param array $tree_category
		 * @param number $level
		 * @return none
		 */
		private function _build_category_sortable(&$html, $tree_category, $level=0){
			if(is_null($tree_category)) {
				return;
			}
			foreach($tree_category as $key=>$value){
				if( $value['is_default'] == 'Y' ){
					$default_select = '('.esc_html__( 'Default category', 'x2board' ).')';
				}
				else {
					$default_select = '';
				}
				$html .= '<li id="tree_category_'.$value['id'].'" style="display: list-item;">'.
					'<div id="tree-category-'.$value['id'].'" class="menu-item-bar">'.
						'<div data-id="'.$value['id'].'" class="menu-item-handle ui-sortable-handle" onclick="x2board_category_edit(\''.$value['id'].'\', \''.$value['title'].'\', \''.$value['parent_id'].'\', \''.$value['is_default'].'\')">'.
							'<span class="item-title">'.$value['title'].' '.$default_select.'</span>'.
							'<input type="hidden" id="tree-category-id-'.$value['id'].'" name="tree_category['.$value['id'].'][id]" value="'.$value['id'].'">'.
							'<input type="hidden" id="tree-category-default-'.$value['id'].'" name="tree_category['.$value['id'].'][is_default]" value="'.$value['is_default'].'">'.
							'<input type="hidden" id="tree-category-name-'.$value['id'].'" name="tree_category['.$value['id'].'][title]" value="'.$value['title'].'">'.
							'<input type="hidden" id="tree-category-parent-'.$value['id'].'" class="x2board-tree-category-parents" name="tree_category['.$value['id'].'][parent_id]" value="'.$value['parent_id'].'">'.
						'</div>
					</div>';
				if(isset($value['children']) && $value['children']){
					$html .= '<ul>';
					$this->_build_category_sortable($html, $value['children'], $level+1);
					$html .= '</ul>';
				}
				$html .= '</li>';
			}
		}
		// }
	}
}