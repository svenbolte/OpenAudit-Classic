<?php
/*
* This file is part of the imageToURI.
* Version 1.0.1
* (c) Daniel Rodrigues (geekcom) und PBMOd
*/

declare(strict_types=1);
namespace imageToURI;

class imageToURI
{
    /**
     * @param array $images
     * @param string $outputFile
     * @param bool $overWrite
     * @throws \Exception
     */

    public function imageToURI(array $images, string $outputFile, bool $overWrite = false) {
		$errors = [];
        $mode = $overWrite ? 'w' : 'a';
        $dataUris = fopen($outputFile, $mode);
        foreach ($images as $image) {
            $mime = getimagesize($image)['mime'];
            if (in_array($mime, $this->acceptableTypes())) {
                $data = file_get_contents($image);
                $output = basename($image) . PHP_EOL . '+++++++++++++++++++++' . PHP_EOL;
                $output .= "data:$mime;base64," . base64_encode($data);
                fwrite($dataUris, $output . PHP_EOL . PHP_EOL);
            } else {
                $errors[] = $image;
            }
			echo '<br><br>'. $output;
        }

        fclose($dataUris);

        echo '<br><br>'.count($images).' Favicons zu icon DataURL konvertiert und in '.$outputFile.' angehängt.';
        if ($errors) {
            throw new \Exception('Could not process' . join(', ', $errors));
        }
    }

    private function acceptableTypes() {
        return ['image/gif', 'image/jpeg', 'image/png'];
    }
}