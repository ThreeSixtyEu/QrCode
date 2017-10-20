<?php

/*
 * (c) Jeroen van den Enden <info@endroid.nl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Endroid\QrCode\Writer;

use BaconQrCode\Renderer\Image\SvgPath;
use BaconQrCode\Writer;
use Endroid\QrCode\QrCodeInterface;
use SimpleXMLElement;

class SvgPathWriter extends AbstractBaconWriter
{
    /**
     * {@inheritdoc}
     */
    public function writeString(QrCodeInterface $qrCode)
    {
        $renderer = new SvgPath();
	    $renderer->setWidth($qrCode->getSize());
	    $renderer->setHeight($qrCode->getSize());
        $renderer->setMargin(0);
        $renderer->setForegroundColor($this->convertColor($qrCode->getForegroundColor()));
        $renderer->setBackgroundColor($this->convertColor($qrCode->getBackgroundColor()));

        $writer = new Writer($renderer);
        $string = $writer->writeString($qrCode->getText(), $qrCode->getEncoding(), $this->convertErrorCorrectionLevel($qrCode->getErrorCorrectionLevel()));

        if ($qrCode->getMargin() !== 0) {
            $string = $this->addMargin($string, $qrCode->getMargin(), $qrCode->getSize());
        }

        return $string;
    }

    /**
     * @param string $string
     * @param int    $margin
     * @param int    $size
     *
     * @return string
     */
    protected function addMargin($string, $margin, $size)
    {
        $targetSize = $size + ($margin * 2);
        $targetScale = $size / ($size);

        $xml = new SimpleXMLElement($string);
	    $xml['width'] = $targetSize;
	    $xml['height'] = $targetSize;
	    $xml['viewBox'] = '0 0 '.$targetSize.' '.$targetSize;

        $transforms = [
//	        sprintf('scale(%s)', $targetScale),
	        sprintf('translate(%s %s)', $margin, $margin),
        ];
        $transform = implode(',', $transforms);

        foreach ($xml->path as $path) {
        	// fix background size
            if (isset($path['id']) && (string) $path['id'] === 'bg') {
            	$path['d'] = sprintf('M 0 0 H %s V %s H 0 V 0', $targetSize, $targetSize);
                continue;
            }

            // translate all other paths
            if (!isset($path['transform'])) {
                $path->addAttribute('transform', $transform);
            } else {
                $path['transform'] .= ',' . $transform;
            }
        }

        return $xml->asXML();
    }

    /**
     * {@inheritdoc}
     */
    public static function getContentType()
    {
        return 'image/svg+xml';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSupportedExtensions()
    {
        return ['svg'];
    }
}
