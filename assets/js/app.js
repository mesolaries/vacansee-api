/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
import $ from 'jquery';

// create global $ and jQuery variables
global.$ = global.jQuery = $;

import 'bootstrap';
require('@fortawesome/fontawesome-free/js/all.js');

// any CSS you import will output into a single css file (app.css in this case)
import '../css/app.scss';
import '../css/app.css';
