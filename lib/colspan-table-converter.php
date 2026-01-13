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
 *
 * Note: This class uses reflection to access the private $columnAlignments
 * property from the parent class. While not ideal, this is necessary because
 * the parent class doesn't provide accessor methods for this property, and
 * proper colspan support requires tracking multiple column alignments per cell.
 */
class ColspanTableConverter extends TableConverter {

	/**
	 * Alignment mapping for table columns
	 *
	 * @var array<string, string>
	 */
	private static $alignments_map = array(
		'left'   => ':--',
		'right'  => '--:',
		'center' => ':-:',
	);

	/**
	 * Cached reflection property for columnAlignments
	 *
	 * @var ReflectionProperty|null
	 */
	private $column_alignments_property = null;

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
		$tag = $element->getTagName();

		// Handle th and td elements with colspan support.
		if ( 'th' === $tag || 'td' === $tag ) {
			$align             = $element->getAttribute( 'align' );
			$column_alignments = $this->get_column_alignments();

			// Add alignment for the main cell.
			if ( null !== $column_alignments ) {
				$this->add_column_alignment( $align );
			}

			$value = $element->getValue();
			$value = str_replace( "\n", ' ', $value );
			$value = str_replace( '|', Coerce::toString( $this->config->getOption( 'table_pipe_escape' ) ?? '\|' ), $value );

			$result = '| ' . trim( $value ) . ' ';

			// Check for colspan attribute.
			$colspan_attr = $element->getAttribute( 'colspan' );
			$colspan      = $colspan_attr ? intval( $colspan_attr ) : 1;

			// Add empty cells for colspan > 1.
			if ( $colspan > 1 ) {
				for ( $i = 1; $i < $colspan; $i++ ) {
					// Add alignment for additional columns if we're tracking alignments.
					if ( null !== $column_alignments ) {
						$this->add_column_alignment( $align );
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
	 * Get the column alignments array from parent class
	 *
	 * Uses reflection to access the private property from the parent class.
	 * The property is cached to avoid repeated reflection lookups.
	 *
	 * @return array|null The column alignments array or null
	 */
	private function get_column_alignments() {
		if ( null === $this->column_alignments_property ) {
			$reflection                       = new ReflectionClass( parent::class );
			$this->column_alignments_property = $reflection->getProperty( 'columnAlignments' );
			$this->column_alignments_property->setAccessible( true );
		}

		return $this->column_alignments_property->getValue( $this );
	}

	/**
	 * Add a column alignment to parent class array
	 *
	 * Uses reflection to modify the private property from the parent class.
	 *
	 * @param string $align The alignment value (left, right, center, or empty).
	 */
	private function add_column_alignment( $align ) {
		if ( null === $this->column_alignments_property ) {
			$reflection                       = new ReflectionClass( parent::class );
			$this->column_alignments_property = $reflection->getProperty( 'columnAlignments' );
			$this->column_alignments_property->setAccessible( true );
		}

		$current   = $this->column_alignments_property->getValue( $this );
		$current[] = self::$alignments_map[ $align ] ?? '---';
		$this->column_alignments_property->setValue( $this, $current );
	}
}
