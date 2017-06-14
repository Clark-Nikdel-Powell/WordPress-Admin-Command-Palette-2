<?php

namespace ACP;

class Data_Template {

	public $title;
	public $object_type;
	public $subtitle;
	public $url;

	public function __construct( $title, $object_type, $subtitle, $url ) {
		$this->title       = $title;
		$this->object_type = $object_type;
		$this->subtitle    = $subtitle;
		$this->url         = $url;
	}
}
