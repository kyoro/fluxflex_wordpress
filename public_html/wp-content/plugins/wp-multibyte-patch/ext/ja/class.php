<?php
/* WP Multibyte Patch Extension File */

/*
WPLANG: ja
Plugin Version: 1.3
Description: Japanese Locale Extension.
Author: tenpura
Extension URI: http://eastcoder.com/code/wp-multibyte-patch/
*/

/*
    Copyright (C) 2010 tenpura (Email: 210pura at gmail dot com)
           This program is licensed under the GNU GPL Version 2.
*/

if(class_exists('multibyte_patch')) :
class multibyte_patch_ext extends multibyte_patch {

	function get_jis_name() {
		if(function_exists('mb_list_encodings')) {
			$list = "\t" . implode("\t", mb_list_encodings()) . "\t";
			return (preg_match("/\tISO-2022-JP-MS\t/i", $list)) ? 'ISO-2022-JP-MS' : 'ISO-2022-JP';
		}
		else
			return 'ISO-2022-JP';
	}

	function UTF8toJIS($string) {
		return $this->convenc($string, $this->get_jis_name(), 'UTF-8');
	}

	function JIStoUTF8($string) {
		return $this->convenc($string, 'UTF-8', $this->get_jis_name());
	}

	function encode_mimeheader_b_uncut($string, $charset = 'UTF8') {
		return "=?$charset?B?" . base64_encode($string) . '?=';
	}

	function wp_mail($phpmailer) {
		$blog_encoding = $this->blog_encoding;

		$phpmailer->FromName = preg_replace("/[\r\n]/", "", trim($phpmailer->FromName));
		$phpmailer->FromName = $this->convenc($phpmailer->FromName, 'UTF-8', $blog_encoding);
		$phpmailer->Subject = preg_replace("/[\r\n]/", "", trim($phpmailer->Subject));
		$phpmailer->Subject = $this->convenc($phpmailer->Subject, 'UTF-8', $blog_encoding);
		$phpmailer->Body = $this->convenc($phpmailer->Body, 'UTF-8', $blog_encoding);

		if('UTF-8' == strtoupper(trim($this->conf['mail_mode'])))
			$mode = 'UTF-8';
		elseif('JIS' == strtoupper(trim($this->conf['mail_mode'])))
			$mode = 'JIS';
		else { // Check unmappable characters and decide what to do.
			$test_str_before = $phpmailer->FromName . $phpmailer->Subject . $phpmailer->Body;
			$test_str_after = $this->UTF8toJIS($test_str_before);
			$test_str_after = $this->JIStoUTF8($test_str_after);
			$mode = ($test_str_after != $test_str_before) ? 'UTF-8' : 'JIS';
		}

		if('UTF-8' == $mode) {
			$phpmailer->CharSet = 'UTF-8';
			$phpmailer->Encoding = 'base64';
			$phpmailer->AddCustomHeader('Content-Disposition: inline');
			$phpmailer->FromName = $this->encode_mimeheader_b_uncut($phpmailer->FromName, 'UTF-8');
			$phpmailer->Subject = $this->encode_mimeheader_b_uncut($phpmailer->Subject, 'UTF-8');
		}
		elseif('JIS' == $mode) {
			$phpmailer->CharSet = 'ISO-2022-JP';
			$phpmailer->Encoding = '7bit';
			$phpmailer->FromName = $this->UTF8toJIS($phpmailer->FromName);
			$phpmailer->FromName = $this->encode_mimeheader_b_uncut($phpmailer->FromName, 'ISO-2022-JP');
			$phpmailer->Subject = $this->UTF8toJIS($phpmailer->Subject);
			$phpmailer->Subject = $this->encode_mimeheader_b_uncut($phpmailer->Subject, 'ISO-2022-JP');
			$phpmailer->Body = $this->UTF8toJIS($phpmailer->Body);
		}
	}

	function process_search_terms() {
		global $wpdb;
		$blog_encoding = $this->blog_encoding;

		if(isset($_GET['s'])) {
			$_GET['s'] = stripslashes($_GET['s']);
			$_GET['s'] = mb_convert_kana($_GET['s'], 's', $blog_encoding);
			$_GET['s'] = preg_replace("/ +/", " ", $_GET['s']);
			$_GET['s'] = $wpdb->escape($_GET['s']);
		}
	}

	function guess_encoding($string, $encoding = '') {
		$guess_list = 'UTF-8, eucJP-win, SJIS-win';

		if(preg_match("/^utf-8$/i", $encoding))
			return 'UTF-8';
		elseif(preg_match("/^euc-jp$/i", $encoding))
			return 'eucJP-win';
		elseif(preg_match("/^(sjis|shift_jis)$/i", $encoding))
			return 'SJIS-win';
		elseif(!$encoding)
			return mb_detect_encoding($string, $guess_list);
		else
			return $encoding;
	}

	function admin_custom_css() {
	    echo "\n" . '<link rel="stylesheet" type="text/css" href="' . plugin_dir_url(__FILE__) . 'admin.css' . '" />' . "\n";
	}

	function word_count_js(&$scripts) {
		$scripts->add('word-count', plugin_dir_url(__FILE__) . 'word-count.js', array('jquery'), '20090422');
	}

	function word_count_js_localize(&$scripts) {
		$scripts->localize( 'word-count', 'wordCountL10n', array(
			'count' => __('%d characters', 'wp-multibyte-patch'),
			'l10n_print_after' => 'try{convertEntities(wordCountL10n);}catch(e){};'
		));
	}

	function multibyte_patch_ext() {
		// auto, jis, UTF-8
		$this->conf['mail_mode'] = 'jis';
		// Treats any post as a multibyte text.
		$this->conf['ascii_threshold'] = 100;

		$this->multibyte_patch();
	}
}
endif;

?>