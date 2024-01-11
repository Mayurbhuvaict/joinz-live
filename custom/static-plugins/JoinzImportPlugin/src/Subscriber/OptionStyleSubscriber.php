<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OptionStyleSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			'property_group_option.loaded' => 'onLoad'
		];
	}

	public function onLoad( EntityLoadedEvent $event ): void
	{
		foreach ( $event->getEntities() as $option ) {
			if ( $option->getColorHexCode() ) {
				$option->addExtension( 'style', new ArrayEntity( [
					'css' => $this->getCss( $option )
				] ) );
			}
		}
	}

	protected function getCss( $option )
	{
		$colors = explode( '/', $option->getColorHexCode() );
		$styles = [];
		$styles[] = 'background:' . $this->hex2rgba( $colors[ 0 ] );

		if ( count( $colors ) == 2 ) {
			$styles[] = 'background:linear-gradient(45deg, ' . $this->hex2rgba( $colors[ 0 ] ) . ' 50%, ' . $this->hex2rgba( $colors[ 1 ] ) . ' 50%)';
		}
		$style = implode( ';', $styles );
		return $style;
	}

	protected function hex2rgba( $color, $opacity = true )
	{

		$default = 'rgb(0,0,0)';

		//Return default if no color provided
		if ( empty( $color ) )
			return $default;

		//Sanitize $color if "#" is provided
		if ( $color[ 0 ] == '#' ) {
			$color = substr( $color, 1 );
		}

		//Check if color has 6 or 3 characters and get values
		if ( strlen( $color ) == 6 ) {
			$hex = array($color[ 0 ] . $color[ 1 ], $color[ 2 ] . $color[ 3 ], $color[ 4 ] . $color[ 5 ]);
		} elseif ( strlen( $color ) == 3 ) {
			$hex = array($color[ 0 ] . $color[ 0 ], $color[ 1 ] . $color[ 1 ], $color[ 2 ] . $color[ 2 ]);
		} else {
			return $default;
		}

		//Convert hexadec to rgb
		$rgb = array_map( 'hexdec', $hex );

		//Check if opacity is set(rgba or rgb)
		if ( $opacity ) {
			if ( abs( $opacity ) > 1 )
				$opacity = 1.0;
			$output = 'rgba(' . implode( ",", $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ",", $rgb ) . ')';
		}

		//Return rgb(a) color string
		return $output;
	}
}
