import $ from 'jquery';
import 'what-input';
import './slick.js';

// Foundation JS relies on a global variable. In ES6, all imports are hoisted
// to the top of the file so if we used `import` to import Foundation,
// it would execute earlier than we have assigned the global variable.
// This is why we have to use CommonJS require() here since it doesn't
// have the hoisting behavior.
window.jQuery = $;
require('foundation-sites');

// If you want to pick and choose which modules to include, comment out the above and uncomment
// the line below
//import './lib/foundation-explicit-pieces';


$(document).foundation();

if (jQuery('.js-drilldown-back').length) {
    jQuery('.js-drilldown-back').each(function () {
        jQuery('.js-drilldown-back').find('a').text('Alle categorieën');
    });
}

// Form-find-product interaction
$('.form-find-product button').on('click', function (e) {
    if (!$(this).hasClass('submit')) {
        e.preventDefault();

        $(this)
            .addClass('submit')
            .text('Submit')
            .parent()
            .find($('fieldset'))
            .toggleClass('hidden')
            .parent()
            .find($('.loading-bar-inner'))
            .toggleClass('loading-50 loading-100');
    }
});

// Accordion
$('.accordion-head:not(.accordion-disabled) span').on('click', function () {
   $(this)
       .parent()
       .next('.accordion-body')
       .slideToggle()
       .parent()
       .toggleClass('active')
       .siblings()
       .removeClass('active')
       .find('.accordion-body')
       .slideUp();
});

// Show and hide filters on category page
$('.filter-panel-wrapper-toggle').on('click', function () {
    $('.filter-panel-item-toggle').toggleClass('visible');
});

// Cart page - remove products from the cart
$('.shopping-cart-product .remove-button').on('click', function (e) {
    e.preventDefault();
    $(this).closest('.shopping-cart-product').remove();
});

// Login page - show the fields for additional address
$('.form-login .js-additional-address').on('click', function (e) {
    $('.form-login .additional-address').toggleClass('hidden');
});

// Expand search bar for small resolutions
$('#js-expand-search').on('click', function () {
    $(this).next('.search-bar-inner').toggleClass('expanded');
});

// Expand dropdown menu height based on the current selected element
$('.menu-left-list .menu-item').on('mouseenter click', function () {
    var windowWidth = $(window).width();
    if(windowWidth >= 640){
        var menuRightListHeight = $(this).find('.menu-right-list').outerHeight();
        var menuLeftListHeight = $(this).closest('.menu-left-list').outerHeight();
        var maxHeight = Math.max(menuRightListHeight, menuLeftListHeight);
        $(this).closest('.menu-categories').height(maxHeight);
    }
});

// Make the width of the right side of dropdown menu fit the window dynamically when the document is resized
$(window).on('resize orientationchange load', function(e) {
    setTimeout(function () {
        var windowWidth = $(window).width();
        if (windowWidth >= 640) {
            var dropdownWholeWidth = $('.navigation .menu-categories').width();
            var dropdownLeftWidth = $('.navigation .menu-wrapper').width();
            var offset = 5;
            var dropdownRightWidth = dropdownWholeWidth - dropdownLeftWidth - offset;
            $('.navigation .menu-right-list').width(dropdownRightWidth);
            $('.navigation .menu-left-list').removeClass('invisible');
        } else {
            $('.navigation .menu-right-list').width('100%');
        }
    }, 100);
});

// Sliders settings
$('.js-products-slider').slick({
    infinite: true,
    slidesToShow: 4,
    touchThreshold: 200,
    swipeToSlide: true,
    slidesToScroll: 1,
    prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-orange"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="icon-caret-right-orange"></i></button>',
    responsive: [
        {
            breakpoint: 1200,
            settings: {
                slidesToShow: 3
            }
        },
        {
            breakpoint: 890,
            settings: {
                slidesToShow: 2
            }
        },
        {
            breakpoint: 640,
            settings: {
                slidesToShow: 1,
                prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-white"></i></button>',
            }
        }
    ]
});

$('.js-products-slider-small').slick({
    infinite: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    touchThreshold: 200,
    swipeToSlide: true,
    prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-orange"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="icon-caret-right-orange"></i></button>',
    responsive: [
        {
            breakpoint: 1200,
            settings: {
                slidesToShow: 2
            }
        },
        {
            breakpoint: 640,
            settings: {
                slidesToShow: 1,
                prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-white"></i></button>',
            }
        }
    ]
});

$('.js-categories-slider').slick({
    infinite: true,
    slidesToShow: 6,
    slidesToScroll: 1,
    touchThreshold: 200,
    swipeToSlide: true,
    prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-orange"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="icon-caret-right-orange"></i></button>',
    responsive: [
        {
            breakpoint: 1200,
            settings: {
                slidesToShow: 5
            }
        },
        {
            breakpoint: 900,
            settings: {
                slidesToShow: 4
            }
        },
        {
            breakpoint: 730,
            settings: {
                slidesToShow: 3
            }
        },
        {
            breakpoint: 640,
            settings: {
                slidesToShow: 1.5,
                infinite: false,
                centerMode: false,
                prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-white"></i></button>'
            }
        }
    ]
});

$('.js-office-slider').slick({
    infinite: true,
    slidesToShow: 3,
    slidesToScroll: 1,
    touchThreshold: 200,
    swipeToSlide: true,
    prevArrow: '<button type="button" class="slick-prev"><i class="icon-caret-left-orange"></i></button>',
    nextArrow: '<button type="button" class="slick-next"><i class="icon-caret-right-orange"></i></button>',
    responsive: [
        {
            breakpoint: 1024,
            settings: {
                slidesToShow: 5
            }
        },
        {
            breakpoint: 900,
            settings: {
                slidesToShow: 4
            }
        },
        {
            breakpoint: 730,
            settings: {
                slidesToShow: 3
            }
        }
    ]
});

$('#registerbutton, label[for=registerbutton]').on('click',function(){
    $('#loginTab').hide();
    $('#registerTab').show();
});

$('#loginbutton, label[for=loginbutton]').on('click',function(){
    $('#loginTab').show();
    $('#registerTab').hide();
});

$("#phone_number_custom").change(function(){
    $("#billingAddressPhoneNumber").val($("#phone_number_custom").val())
});

$("#billingAddress_house_number").change(function(){
    $("#billingAddressAdditionalField1").val($("#billingAddress_house_number").val())
});

$("#shippingAddress_house_number").change(function(){
    $("#shippingAddressAdditionalField1").val($("#shippingAddress_house_number").val())
});

$("#address_house_number").change(function(){
    $("#addressAdditionalField1").val($("#address_house_number").val())
});
