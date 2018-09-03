<?php

function norm($x, $y) {
    // Return norm of the complex number x+iy (the square of the modulus)
    $mod = $x*$x + $y*$y; //x^2 + y^2
    return $mod;
}

function pixelColor($steps) {
    // coloring algorithm. Takes number of steps (between 1 and 256) as an input, and returns an array (r,g,b) for the colours
    $coloring = array(
        'red' => 0,
        'green' => 0,
        'blue' => 0
    );
    
        if ($steps < 6) {
            // outer blue region. Start dark blue towards extreme edges and move towards medium/light blue in the middle
            $coloring['red'] = $steps*$steps + $steps;
            $coloring['green'] = 255*($steps/255.0)*($steps/255.0);
            $coloring['blue'] = 170*($steps*$steps*$steps/125.0)+ 85*($steps*$steps/144.0) + 5*(6-$steps)+10;
        } else if ($steps < 10) {
            // purple/blue section. put more weight on red for closer to the centre to allow for "smooth" gradient
            $coloring['red'] = 2*$steps*$steps - $steps;
            $coloring['green'] = 255*($steps/255.0)*($steps/255.0)+10;
            $coloring['blue']= 185 - $steps*$steps + $steps; // from next section, sub in steps=5 to get 170 + 85*(25.0/144.0) = 185
        } else if ($steps <= 12) {
            // move from dark red/purple to pure red as steps increases
            $coloring['red'] = 255 - 70*(12-$steps); // 21 = 255/12
            $coloring['green'] = $steps;
            $coloring['blue'] = -2*($steps-8)*($steps-13); //the bigger the number, the less weighting on blue. hence use a concave function
        } else if ($steps <= 22) {
            // move from red to orange to yellow. red and blue values are constant. we need to change green
            $coloring['red'] = 255;// constant 255
            $coloring['green'] = 12+27*($steps-13); // start from 12 and increase to 255. steps is a number between 13 and 20. (255-12)/7=34
            // use linear interpolation
            $coloring['blue'] = 0; //constant 0
        } else if ($steps <= 32) {
            // move from yellow -> green
            $coloring['red'] = 255-(12+27*($steps-23)); // start from 12 and increase to 255. steps is a number between 13 and 20. (255-12)/7=34
            // use linear interpolation
            $coloring['green'] = 255;
            $coloring['blue'] = 0; //constant 0
        } else if ($steps <= 45) {
            // move from green -> cyan
            $coloring['red'] = 0; // start from 12 and increase to 255. steps is a number between 13 and 20. (255-12)/7=34
            // use linear interpolation
            $coloring['green'] = 255;
            $coloring['blue'] = 19*($steps-33); //constant 0
        } else if ($steps <= 75) {
            // move from cyan -> dark blue
            $coloring['red'] = 0; // start from 12 and increase to 255. steps is a number between 13 and 20. (255-12)/7=34
            // use linear interpolation
            $coloring['green'] = 255-(8.5*($steps-46));
            $coloring['blue'] = 255;
        } else if ($steps <= 110) {
            // move from pink/magenta -> purple -> blue
            $coloring['red'] = 7*($steps-76); //255/(110-75) = 7
            $coloring['green'] = 0;
            $coloring['blue'] = 255;
        } else if ($steps <= 130) {
            // move from magenta -> red
            $coloring['red'] = 255;
            $coloring['green'] = 0;
            $coloring['blue'] = 255-(12*($steps-111));
        } else if ($steps <= 160) {
            // move from red -> yellow
            $coloring['red'] = 255;
            $coloring['green'] = 8*($steps-131);
            $coloring['blue'] = 0;
        } else if ($steps <= 180) {
            // move from yellow -> green
            $coloring['red'] = 255-8*($steps-161);
            $coloring['green'] = 255;
            $coloring['blue'] = 0;
        } else if ($steps <= 210) {
            // green -> cyan
            $coloring['red'] = 0;
            $coloring['green'] = 255;
            $coloring['blue'] = 8*($steps-181);
        } else {
            // cyan -> dark blue
            $coloring['red'] = 0;
            $coloring['green'] = 255-5*($steps-211);
            $coloring['blue'] = 255;
        }
    
    return $coloring;
}

function escapeSteps($x, $y) {
    // Returns the number of steps to escape the mandelbrot set, given a complex number x+iy
    $steps = 0;
	$x1 = 0; //w
	$y1 = 0;
	
	while (norm($x1, $y1) < 4.0 && $steps < 256) {
		$xtemp = $x1 * $x1 - $y1 * $y1 + $x;
		$y1 = 2 * $y1 * $x1 + $y;
        $x1 = $xtemp;
		$steps++;
	}
    return $steps;
}

function getCurrentUri() {
    // Gets URL so we can redirect a URL of the form /mandelbrot/2/zoom/x-coord/y-coord/tile.bmp 
    // to this file to process it
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = '/' . trim($uri, '/');
    return $uri;
}

$base_url = getCurrentUri();
$routes = array();
$routes = explode('/', $base_url);
foreach($routes as $route) {
    if(trim($route) != '')
        array_push($routes, $route);
}



if($routes[1] == 'index.html') {
    header("Location: index.html");
}

if($routes[1] == '') {
    header("Location: index.html");
}

if($routes[1] == '2')
{
    $zoom = $routes[2];
    $x = $routes[3]; // centre
    $y = $routes[4]; // centre
    $im = imagecreatetruecolor(512, 512);
    
    for ($i = 0; $i < 512; $i++) {
        for ($j = 0; $j < 512; $j++) {
            $xt = ($j - 256)/(2 ** $zoom) + $x;
            $yt = (-$i + 256)/(2 ** $zoom) + $y; // adjustment for PHP coordinate system (different to the C system used for COMP1511)
            $count = escapeSteps($xt, $yt);
            if ($count == 256) {
                // In the Mandelbrot set. Colour black
                $black = imagecolorallocate($im, 0, 0, 0);
                imagesetpixel($im, $j, $i, $black);
            } else {
                // color in pretty colours
                $colours = pixelColor($count);
                $customColor = imagecolorallocate($im, $colours['red'], $colours['green'], $colours['blue']);
                imagesetpixel($im, $j, $i, $customColor);
            }
        }
    }
    
    // Save the image to the url 2/zoom/x-coord/y-coord/tile.bmp
    $url_route = '2/'.$zoom.'/'.$x.'/'.$y.'/tile.bmp';
    imagebmp($im);
    imagedestroy($im); // free memory
}
?>