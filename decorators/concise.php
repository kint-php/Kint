<?php
class Kint_Decorators_Concise extends Kint
{
	/**
	 * output:
	 *
	 * [value]
	 *
	 * value [title="[access] [name] [operator *] [subtype] [size] "]
	 * OR
	 * type [title="[access] [name] type [operator *] [subtype] [size] "]
	 * <ul>extendedValue
	 *
	 * @param kintParser $kintVar
	 *
	 * @return string
	 */
	public static function decorate( kintParser $kintVar )
	{
		if ( $kintVar->extendedValue !== null ) {
			return Kint_Decorators_Rich::decorate( $kintVar );
		}

		if ( $kintVar->value !== null ) {
            $output = '';
            
		    if (isset($kintVar->id))
		        $output .= "<a href=\"#{$kintVar->id}\">";
		    	
			$output .= '<span title="';

			if ( $kintVar->access !== null ) {
				$output .= $kintVar->access . " ";
			}

			if ( $kintVar->name !== null ) {
				$output .= $kintVar->name . " ";
			}

			if ( $kintVar->type !== null ) {
				$output .= $kintVar->type;
				if ( $kintVar->subtype !== null ) {
					$output .= " " . $kintVar->subtype;
				}
				$output .= " ";
			}

			if ( $kintVar->operator !== null ) {
				$output .= $kintVar->operator . "";
			}

			if ( $kintVar->size !== null ) {
				$output .= "(" . $kintVar->size . ") ";
			}

			$output .= '"';
			
			if (isset($kintVar->color))
			    $output .= " style=\"color:{$kintVar->color}\"" ;
				
			$output .= ">";
			
		    if (isset($kintVar->id))
		        $output .= "</a>";

		    $output = trim($output) . $kintVar->value . '</span>';
		} else {
			$output = '<u>';

			if ( $kintVar->access !== null ) {
				$output .= $kintVar->access . " ";
			}

			if ( $kintVar->name !== null ) {
				$output .= $kintVar->name . " ";
			}

			if ( $kintVar->type !== null ) {
				$output .= $kintVar->type;
				if ( $kintVar->subtype !== null ) {
					$output .= " " . $kintVar->subtype;
				}
				$output .= " ";
			}

			if ( $kintVar->operator !== null ) {
				$output .= $kintVar->operator . "";
			}

			if ( $kintVar->size !== null ) {
				$output .= "(" . $kintVar->size . ")";
			}
			;

			$output = trim( $output ) . '</u>';
		}

		return $output;
	}
}