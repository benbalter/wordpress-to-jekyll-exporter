<?php
class Oxymel {
	private $xml;
	private $dom;
	private $current_element;
	private $last_inserted;
	private $go_deep_on_next_element = 0;
	private $go_up_on_next_element = 0;
	private $nesting_level = 0;
	private $contains_nesting_level = 0;
	private $indentation= '  ';

	public function __construct() {
		$this->xml = '';
		$this->init_new_dom();
	}

	public function to_string() {
		return $this->xml .= $this->indent( $this->xml_from_dom(), $this->nesting_level );
	}

	public function __call( $name, $args ) {
		array_unshift( $args, $name );
		return call_user_func_array( array( $this, 'tag' ), $args );
	}

	public function __get( $name ) {
		return $this->$name();
	}

	public function contains() {
		$this->contains_nesting_level++;
		$this->nesting_level++;
		if ( $this->go_deep_on_next_element ) {
			throw new OxymelException( 'contains cannot be used consecutively more than once' );
		}
		$this->go_deep_on_next_element++;
		return $this;
	}

	public function end() {
		$this->contains_nesting_level--;
		$this->nesting_level--;
		if ( $this->contains_nesting_level < 0 ) {
			throw new OxymelException( 'end is used without a matching contains' );
		}
		$this->go_up_on_next_element++;
		return $this;
	}

	public function tag( $name, $content_or_attributes = null, $attributes = array() ) {
		list( $content, $attributes ) = $this->get_content_and_attributes_from_tag_args( $content_or_attributes, $attributes );
		$is_opening =  0 === strpos( $name, 'open_' );
		$is_closing =  0 === strpos( $name, 'close_' );
		$name = preg_replace("/^(open|close)_/", '', $name );

		$element = $this->create_element( $name, $content, $attributes );

		if ( !$is_opening && !$is_closing )
			$this->add_element_to_dom( $element );
		elseif ( $is_opening )
			$this->add_opening_tag_from_element( $element );
		elseif ( $is_closing )
			$this->add_closing_tag_from_tag_name( $name );

		return $this;
	}

	public function cdata( $text ) {
		$this->add_element_to_dom( $this->dom->createCDATASection( $text ) );
		return $this;
	}

	public function text( $text ) {
		$this->add_element_to_dom( $this->dom->createTextNode( $text ) );
		return $this;
	}

	public function comment( $text ) {
		$this->add_element_to_dom( $this->dom->createComment( $text ) );
		return $this;
	}

	public function xml() {
		$this->add_element_to_dom( $this->dom->createProcessingInstruction( 'xml', 'version="1.0" encoding="UTF-8"' ) );
		return $this;
	}

	public function oxymel( Oxymel $other ) {
		foreach( $other->dom->childNodes as $child ) {
			$child = $this->dom->importNode( $child, true );
			$this->add_element_to_dom( $child );
		}
		return $this;
	}

	public function raw(  $raw_xml ) {
		if ( !$raw_xml ) {
			return $this;
		}
		$fragment = $this->dom->createDocumentFragment();
		$fragment->appendXML($raw_xml);
		$this->add_element_to_dom( $fragment );
		return $this;
	}

	private function add_element_to_dom( $element ) {
		$this->move_current_element_deep();
		$this->move_current_element_up();
		$this->last_inserted = $this->current_element->appendChild($element);
	}

	private function move_current_element_deep() {
		if ( $this->go_deep_on_next_element ) {
			if ( !$this->last_inserted ) {
				throw new OxymelException( 'contains has been used before adding any tags' );
			}
			$this->current_element = $this->last_inserted;
			$this->go_deep_on_next_element--;
		}
	}

	private function move_current_element_up() {
		if ( $this->go_up_on_next_element ) {
			while ( $this->go_up_on_next_element ) {
				$this->current_element = $this->current_element->parentNode;
				$this->go_up_on_next_element--;
			}
		}
	}

	private function get_content_and_attributes_from_tag_args( $content_or_attributes, array $attributes ) {
		$content = null;
		if ( !$attributes ) {
			if ( is_array( $content_or_attributes ) )
				$attributes = $content_or_attributes;
			else
				$content = $content_or_attributes;
		} else {
			$content = $content_or_attributes;
		}
		return array( $content, $attributes );
	}

	private function init_new_dom() {
		unset( $this->dom, $this->current_element );
		$this->dom = new DOMDocument();
		$this->dom->formatOutput = true;
		$this->current_element = $this->dom;
		$this->last_inserted = null;
	}

	private function xml_from_dom() {
		if ( 0 !== $this->contains_nesting_level ) {
			throw new OxymelException( 'contains and end calls do not match' );
		}
		$xml = '';
		foreach( $this->dom->childNodes as $child ) {
			$xml .= $this->dom->saveXML( $child ) . "\n";
		}
		return $xml;
	}

	private function create_element( $name, $content, $attributes ) {
		if ( !is_null( $content ) )
			$element = $this->dom->createElement( $name, $content );
		else
			$element = $this->dom->createElement( $name );

		foreach( $attributes as $attribute_name => $attribute_value ) {
			$element->setAttribute( $attribute_name, $attribute_value );
		}

		return $element;
	}

	private function add_opening_tag_from_element( $element ) {
		$this->xml .= $this->indent( $this->xml_from_dom(), $this->nesting_level );
		$tag = $this->dom->saveXML($element);
		$this->xml .= $this->indent( str_replace( '/>', '>', $tag ) . "\n", $this->nesting_level );
		$this->nesting_level++;
		$this->init_new_dom();
	}

	private function add_closing_tag_from_tag_name( $name ) {
		$this->xml .= $this->xml_from_dom();
		$this->nesting_level--;
		if ( $this->nesting_level < 0 ) {
			$this->xml = $this->indent( $this->xml, -$this->nesting_level );
			$this->nesting_level = 0;
		}
		$this->xml .= $this->indent( "</$name>\n", $this->nesting_level );
		$this->init_new_dom();
	}

	private function indent( $string, $level ) {
		if ( !$level ) {
			return $string;
		}
		$lines = explode( "\n", $string );
		foreach( $lines as &$line ) {
			if ( !trim( $line ) )
				continue;
			$line = str_repeat( $this->indentation, $level ) . $line;
		}
		return implode( "\n", $lines );
	}
}

class OxymelException extends Exception {
}
