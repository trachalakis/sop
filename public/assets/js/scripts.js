var featuredSlides;
var blogSlides;

function initSwiper(){
    //initialize swiper when document ready
    featuredSlides = new Swiper ('.slides_featured', {
      // Optional parameters
    parallax:true,
      navigation: {
      nextEl: '.slides__btn_next_featured',
      prevEl: '.slides__btn_prev_featured',
        },
      loop: true
    });
    blogSlides = new Swiper ('.slides_blog', {
      // Optional parameters
    parallax:true,
        slidesPerView:2,
        spaceBetween:50,
      navigation: {
      nextEl: '.slides__btn_next_blog',
      prevEl: '.slides__btn_prev_blog',
        },
      loop: true,
       // when window width is <= 1365px
      breakpoints: {
      1365: {
        slidesPerView: 1,
        }
      },
    });
}

$(document).ready(function(){
    initSwiper();
    $(".js-nav-toggle").click(function(){
        $("#js-nav-holder").toggleClass("open");
        $(this).attr('aria-expanded', function (i, attr) {
          return attr == 'true' ? 'false' : 'true'
        });
        $("body").toggleClass("nav-open");
    });
    $(".header__title").click(function(){
       if ($('body').hasClass('nav-open')) {
            $(".js-nav-toggle").attr('aria-expanded','false');
            $("#js-nav-holder").toggleClass("open");
            $("body").toggleClass("nav-open");
       }
    });
    $("#reservations-link").click(function(){
       if ($('body').hasClass('nav-open')) {
            $(".js-nav-toggle").attr('aria-expanded','false');
            $("#js-nav-holder").toggleClass("open");
            $("body").toggleClass("nav-open");
       }
    });
    $("#js-nav-holder").click(function(){
            $(".js-nav-toggle").attr('aria-expanded','false');
            $("#js-nav-holder").toggleClass("open");
            $("body").toggleClass("nav-open");
    });
});

// grab an element
var myElement = document.querySelector("#header");
// construct an instance of Headroom, passing the element
var headroom = new Headroom(myElement, {
  "offset": 200,
  "tolerance": 5,
});
// initialise
headroom.init();

$(".js-datepicker").flatpickr({
  allowInput: true,
});

$(".js-timepicker").flatpickr({
enableTime: true,
noCalendar: true,
dateFormat: "H:i",
allowInput: true,
});


const options = {
   plugins: [new SwupScrollPlugin(),
             new SwupPreloadPlugin(),
             new SwupBodyClassPlugin({
                    prefix: 'page-'
                }),
            ],
   containers: [
    '#swup',
        ],
};
// TODO debug in iPad

var iOS = parseFloat(('' + (/CPU.*OS ([0-9_]{1,5})|(CPU like).*AppleWebKit.*Mobile/i.exec(navigator.userAgent) || [0,''])[1]) .replace('undefined', '3_2').replace('_', '.').replace('_', '')) || false;
if (iOS) { 
console.log(iOS);
}


if (!iOS || iOS >= 10.4) { 


  const swup = new Swup(options);

  swup.on('contentReplaced', function () {

      initSwiper();

      AOS.init();
      
      $(".js-datepicker").flatpickr( {
          allowInput: true,
      });

      $(".js-timepicker").flatpickr( {
          enableTime: true,
          noCalendar: true,
          dateFormat: "H:i",
          allowInput: true,
      });
  });

  $('.js-load').imagesLoaded( {
    background: true
    },
    function() {
      $('.loader').removeClass('show');
      console.log("img");
    }
  );

} else {
  $('.loader').removeClass('show');
}

// TODO debug in iPad end





