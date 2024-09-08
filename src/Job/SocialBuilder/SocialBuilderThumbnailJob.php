<?php

declare(strict_types=1);

namespace App\Job\FlowExamples\SocialBuilder;

use Exception;
use Flow\JobInterface;

/**
 * @implements JobInterface<string, bool>
 */
class SocialBuilderThumbnailJob implements JobInterface
{
    public function __invoke($data): bool
    {
        // Define the path where the generated image will be saved
        $imagePath = __DIR__ . '/thumbnail.png';

        // Create an image with width 400px and height 300px
        $width = 400;
        $height = 300;
        $image = imagecreatetruecolor($width, $height);

        // Set background color (white)
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $backgroundColor);

        // Set text color (black)
        $textColor = imagecolorallocate($image, 0, 0, 0);

        // Add the input string text to the image
        $fontPath = __DIR__ . '/arial.ttf';  // Ensure this is a valid font path
        $fontSize = 12;
        $angle = 0;
        $x = 10;
        $y = 50;

        // If a TTF font file exists, use it; otherwise, fall back to a built-in font
        if (file_exists($fontPath)) {
            imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $data);
        } else {
            imagestring($image, 5, $x, $y, $data, $textColor);
        }

        // Save the image to the defined path
        if (!imagepng($image, $imagePath)) {
            throw new Exception('Failed to save the generated image.');
        }

        // Free up memory
        imagedestroy($image);

        return true;
    }
}
