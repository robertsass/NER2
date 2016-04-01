$(document).ready(function(){
	extendBootstrap();

	$('#menu .icon-menu').click(function(){
		toggleMenu();
	});

	$('img').unveil(300, function(){
		$(this).load(function(){
			if( this.width < this.height )
				$(this).addClass('portrait');
		});
	});
	
	initSlider();

	$('.photo-galery').each(function(){
		$(this).find('a > img').parent().magnificPopup({
			type: 'image',
			gallery: {
				enabled: true
			},
			callbacks: {
				change: function() {
					console.log( this.currItem );
					var anchor = this.currItem.el.attr('data-anchor');
					if( anchor )
						window.location.hash = anchor;
				},
				close: function() {
					window.location.hash = '/';
				}
			}
		});
	});
	$('.magnific').magnificPopup({
		disableOn: 700,
		type: 'iframe',
		mainClass: 'mfp-fade',
	//	removalDelay: 160,
		preloader: false,
		fixedContentPos: false
	});

	var anchor = window.location.hash.substr(1);
	var anchoredElement = $('a[data-anchor="'+ anchor +'"]');
	if( anchoredElement.length > 0 )
		anchoredElement.click();

	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover({
		'before': function(){
			$('.popover').popover('hide');
		}
	});
});

$(window).scroll(function(){
	var viewportHeight = $(window).height();
	var scrollDelay = viewportHeight/4;
	var scrollOffset = $(window).scrollTop() > viewportHeight ? viewportHeight : $(window).scrollTop();
	var scrolled = (scrollOffset - scrollDelay) / (viewportHeight - scrollDelay);
	scrolled = parseInt( parseFloat( scrolled >= 0 ? scrolled : 0 ).toFixed(1) *10 );
	$('body')
		.removeClass( 'scrolled-0' )
		.removeClass( 'scrolled-1' )
		.removeClass( 'scrolled-2' )
		.removeClass( 'scrolled-3' )
		.removeClass( 'scrolled-4' )
		.removeClass( 'scrolled-5' )
		.removeClass( 'scrolled-6' )
		.removeClass( 'scrolled-7' )
		.removeClass( 'scrolled-8' )
		.removeClass( 'scrolled-9' )
		.removeClass( 'scrolled-10' )
		.addClass( 'scrolled-'+ scrolled );
		
	// Pause Slider-Autoplay if scrolled down
	var panoramaSlider = $('#panorama .slider');
	var sliderInstance = panoramaSlider.data('slider');
	if( panoramaSlider.data('autoplay-stopped') == undefined )
		panoramaSlider.data('autoplay-stopped', false);
	if( scrolled > 0 && !panoramaSlider.data('autoplay-stopped') ) {
		sliderInstance.trigger('stop.owl.autoplay');
		panoramaSlider.data('autoplay-stopped', true);
	} else if( scrolled == 0 && panoramaSlider.data('autoplay-stopped') ) {
		sliderInstance.trigger('play.owl.autoplay');
		panoramaSlider.data('autoplay-stopped', false);
	}
});
    
    
function toggleMenu() {
	var menu = $('#menu nav');
	menu.toggleClass('unfolded');
	$('body').toggleClass( 'menu-unfolded' );
}

function showMenu( menu, body, animateBody ) {
	if( animateBody == undefined )
		animateBody = true;

	if( menu.css('display') == 'none' ) {
		menu.css('right', '-'+menu.outerWidth()+'px');
		menu.css('display', 'block');
	}
	menu.animate({
		'right': '0px'
	}, 300, 'linear');
	if( animateBody ) {
		body.animate({
			'left': '-'+menu.outerWidth()+'px',
			'right': menu.outerWidth()+'px'
		}, 300, 'swing');
	}
	body.addClass('menu-opened');
}

function hideMenu( menu, body, animateBody ) {
	if( animateBody == undefined )
		animateBody = true;

	menu.animate({
		'right': '-'+menu.outerWidth()+'px'
	}, 300, 'swing');
	if( animateBody ) {
		body.animate({
			'left': '0px',
			'right': '0px'
		}, 300, 'swing', function(){
			body.removeClass('menu-opened');
		});
	}
	else
		body.removeClass('menu-opened');
}


function initSlider() {
	$('.slider').each(function(){
		var instance = $(this).owlCarousel({
			items: 1,
			loop: true,
			nav: true,
			navText: ['<span class="icon-left-open-big"></span>', '<span class="icon-right-open-big"></span>'],
			dots: false,
			autoplay: true,
			lazyLoad: true,
		});
		$(this).data('slider', instance);
	});
}


function initBackTop() {
	var breakpoint = $('#main #content').offset().top + parseInt( $('#main #content').css('padding-top') ) -10;
	if( breakpoint == undefined )
		breakpoint = 500;
	var button = $('#backtop');

	if( $(window).scrollTop() >= breakpoint )
		button.fadeIn();

	$(window).scroll(function () {
		if( $(this).scrollTop() >= breakpoint ) {
			button.fadeIn();
		} else {
			button.fadeOut();
		}
	});

	button.click(function(){
		$('body,html').animate({ scrollTop: 0 }, 800);
		return false;
	});

	initScrollDown( breakpoint );
}

function initScrollDown( breakpoint ) {
	var breakpoint = breakpoint != undefined ? breakpoint : 500;
	var button = $('#scrolldown');

	if( $(window).scrollTop() < breakpoint )
		button.fadeIn();

	$(window).scroll(function () {
		if( $(this).scrollTop() < breakpoint ) {
			button.fadeIn();
		} else {
			button.fadeOut();
		}
	});

	button.click(function(){
		$('body,html').animate({ scrollTop: breakpoint }, 800);
		return false;
	});
}

function extendBootstrap() {
	// Extend Popover functions to support events
	var popoverShow = $.fn.popover.Constructor.prototype.show;
	$.fn.popover.Constructor.prototype.show = function() {
	    if( this.options.before ) {
	        this.options.before();
	    }
	    popoverShow.call(this);
	    if( this.options.after ) {
	        this.options.after();
	    }
	}
	// Bug that causes the need to click twice
	if ($.fn.popover.Constructor.VERSION == "3.3.5") {
		$('[data-toggle="popover"]').on('hidden.bs.popover', function() {
			$(this).data('bs.popover').inState.click = false;
		})
	}
}