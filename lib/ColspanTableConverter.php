<?php
/**
 * Custom Table Converter that handles colspan attributes
 *
 * @package    JekyllExporter
 * @author     Ben Balter <ben@balter.com>
 * @copyright  2013-2025 Ben Balter
 * @license    GPLv3
 * @link       https://github.com/benbalter/wordpress-to-jekyll-exporter/
 */

use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\Coerce;
use League\HTMLToMarkdown\ElementInterface;

/**
 * Custom TableConverter that handles colspan attributes
 *
 * This converter extends the League\HTMLToMarkdown\Converter\TableConverter
 * to add support for colspan attributes in table cells. When a cell has a
 * colspan attribute, it adds the appropriate number of empty cells to maintain
 * the table structure in Markdown.
 */
class ColspanTableConverter extends TableConverter {

	/**
	 * Convert HTML table elements to Markdown
	 *
	 * This method overrides the parent convert method to add colspan support.
	 * When a colspan attribute is detected on a th or td element, it adds
	 * the appropriate number of empty cells to the Markdown output.
	 *
	 * @param ElementInterface $element The HTML element to convert.
	 * @return string The Markdown representation
	 */
	public function convert( ElementInterface $element ): string {
		$value = $element->getValue();
		$tag   = $element->getTagName();

		// Handle th and td elements with colspan support.
		if ( 'th' === $tag || 'td' === $tag ) {
			$align            = $element->getAttribute( 'align' );
			$column_alignments = $this->getColumnAlignments();

			if ( null !== $column_alignments ) {
				$this->addColumnAlignment( $align );
			}

			$value = str_replace( "\n", ' ', $value );
			$value = str_replace( '|', Coerce::toString( $this->getConfig()->getOption( 'table_pipe_escape' ) ?? '\|' ), $value );

			$result = '| ' . trim( $value ) . ' ';

			// Check for colspan attribute.
			$colspan_attr = $element->getAttribute( 'colspan' );
			$colspan      = $colspan_attr ? intval( $colspan_attr ) : 1;

			// Add empty cells for colspan > 1.
			if ( $colspan > 1 ) {
				for ( $i = 1; $i < $colspan; $i++ ) {
					// Add alignment for additional columns if we're tracking alignments.
					if ( null !== $column_alignments ) {
						$this->addColumnAlignment( $align );
					}
					$result .= '|  ';
				}
			}

			return $result;
		}

		// For all other elements, use the parent implementation.
		return parent::convert( $element );
	}

	/**
	 * Get the column alignments array
	 *
	 * Uses reflection to access the private property from the parent class.
	 *
	 * @return array|null The column alignments array or null
	 */
	private function getColumnAlignments() {
		$reflection = new ReflectionClass( parent::class );
		$property   = $reflection->getProperty( 'columnAlignments' );
		$property->setAccessible( true );
		return $property->getValue( $this );
	}

	/**
	 * Add a column alignment
	 *
	 * Uses reflection to modify the private property from the parent class.
	 *
	 * @param string $align The alignment value (left, right, center, or empty).
	 */
	private function addColumnAlignment( $align ) {
		$alignments_map = array(
			'left'   => ':--',
			'right'  => '--:',
			'center' => ':-:',
		);

		$reflection = new ReflectionClass( parent::class );
		$property   = $reflection->getProperty( 'columnAlignments' );
		$property->setAccessible( true );

		$current   = $property->getValue( $this );
		$current[] = $alignments_map[ $align ] ?? '---';
		$property->setValue( $this, $current );
	}

	/**
	 * Get the configuration object
	 *
	 * Uses reflection to access the protected property from the parent class.
	 *
	 * @return \League\HTMLToMarkdown\Configuration The configuration object
	 */
	private function getConfig() {
		$reflection = new ReflectionClass( parent::class );
		$property   = $reflection->getProperty( 'config' );
		$property->setAccessible( true );
		return $property->getValue( $this );
	}
}
