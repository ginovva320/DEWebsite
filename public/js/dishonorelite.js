window.addEvent('domready', function() {
	/* settings */
	var showDuration = 8000;
	var container = $('slideshow-container');
	var images = container.getElements('img');
	var currentIndex = 0;
	var interval;
	var toc = [];
	var tocActive = 'toc-active';
	var shadow = 'shadow';
	var thumbOpacity = 0.75;

	/* new: create caption area */
	var captionDIV = new Element('div', {
		id : 'slideshow-container-caption',
		styles : {
			opacity : thumbOpacity
		}
	}).inject(container);
	
	var captionHeight = captionDIV.getSize().y;
	captionDIV.setStyle('height', 0);

	/* new: starts the show */
	var start = function() {
		stop();
		interval = show.periodical(showDuration);
	};
	
	var stop = function() {
		$clear(interval);
	};
	
	var show = function(to) {
		images[currentIndex].fade('out');
		images[currentIndex = ($defined(to) ? to
				: (currentIndex < images.length - 1 ? currentIndex + 1 : 0))]
				.fade('in');
		captionDIV.get('tween').removeEvents('complete');
		captionDIV.set(
				'tween',
				{
					onComplete : function() {
						captionDIV.set('tween', {
							onComplete : $empty
						}).tween('height', captionHeight);
						/* parse caption */
						var title = '';
						var captionText = '';
						if (images[currentIndex].get('alt')) {
							cap = images[currentIndex].get('alt').split('::');
							title = cap[0];
							captionText = cap[1];
							captionDIV.set('html', '<h3>'
									+ title
									+ '</h3>'
									+ (captionText ? '<p>' + captionText
											+ '</p>' : ''));
						}
					}
				}).tween('height', 0);
	};

	var next = new Element('div', {
		id : 'next',
		events : {
			click : function(e) {
				if (e)
					e.stop();
				show(currentIndex < images.length - 1 ? currentIndex + 1 : 0);
			},
			mouseenter : function() {
				this.setStyle('background-position', '0 -50px');
			},
			mouseleave : function() {
				this.setStyle('background-position', '0 0');
			}
		}
	}).inject(container);

	images.each(function(img, i) {
		if (i > 0) {
			img.setStyle('opacity', 0);
		}
	});

	var prev = new Element('div', {
		id : 'prev',
		events : {
			click : function(e) {
				if (e)
					e.stop();
				show((currentIndex > 0 ? currentIndex - 1
						: images.length - 1));
			},
			mouseenter : function() {
				this.setStyle('background-position', '0 -50px');
			},
			mouseleave : function() {
				this.setStyle('background-position', '0 0');
			}
		}
	}).inject(container);

	/* control: start/stop on mouseover/mouseout */
	container.addEvents({
		mouseenter : function() {
			stop();
		},
		mouseleave : function() {
			start();
		}
	});

	/* start once the page is finished loading */
	window.addEvent('load', function() {
		show(0);
		start();
	});
});
window.addEvent('domready', function() {
	var myForm = $('shoutform');
	var scroll = new Fx.Scroll($$('#shoutbox > article')[0]);
	scroll.set(0,5000);
	var value;
	if (myForm != null)
	{
		myForm.addEvent('submit', function(e) {
			e.stop();
			if ($$('#shoutform input')[0].value != "") {
				new Request.HTML({
					url : baseUrl+'/shout',
					append : $$('#shoutbox > article')[0],
					onSuccess : function(tree, ele, ht) {
						$$('#shoutform input')[0].value = null;
						scroll.toBottom();
					}
				}).post(myForm).send();
			}
		});
	}
});
window.addEvent('domready', function() {
	var dwProgressBar = new Class({
		Implements : [ Options ],
	
		// options
		options : {
			container : $$('body')[0],
			boxID : '',
			boxclass : '',
			percentageID : '',
			percclass : '',
			displayID : '',
			startPercentage : 0,
			displayText : false,
			speed : 10
		},
	
		// initialization
		initialize : function(options) {
			// set options
			this.setOptions(options);
			// create elements
			this.createElements();
		},
	
		// creates the box and percentage elements
		createElements : function() {
			var box = new Element('div', {
				id : this.options.boxID,
				'class' : this.options.boxclass
			});
			var perc = new Element('div', {
				id : this.options.percentageID,
				'class' : this.options.percclass,
				'style' : 'width:0px;'
			});
			perc.inject(box);
			box.inject(this.options.container);
			if (this.options.displayText) {
				var text = new Element('div', {
					id : this.options.displayID
				});
				text.inject(this.options.container);
			}
			this.set(this.options.startPercentage);
		},
	
		// calculates width in pixels from percentage
		calculate : function(percentage) {
			return ($(this.options.boxID).getStyle('width')
					.replace('px', '') * (percentage / 100))
					.toInt();
		},
	
		// animates the change in percentage
		animate : function(to) {
			$(this.options.percentageID).set('morph', {
				duration : this.options.speed,
				link : 'cancel'
			}).morph({
				width : this.calculate(to.toInt())
			});
			if (this.options.displayText) {
				$(this.options.displayID).set('text',
						to.toInt() + '%');
			}
		},
	
		// sets the percentage from its current state to desired
		// percentage
		set : function(to) {
			this.animate(to);
		}
	
	});
});
var DEmessenger = function(title,caption,timeout) {
	if (!caption)
		caption = '';
	if (!timeout)
		timeout = 4000;
	
	clearTimeout(this.fadeTimer);
	clearTimeout(this.destroyMsg);
	
	var messenger = $('messenger');
	
	$$('#messenger *').destroy();
	messenger.setStyle('opacity',0);
	
	new Element('header', {
		text: title
	}).inject(messenger);
	
	new Element('p', {
		text: caption
	}).inject(messenger);
	
	messenger.fade(1);
	
	this.fadeTimer = function(){
		messenger.fade(0);
	}.delay(timeout);
	
	this.destroyMsg = function(){
		$$('#messenger *').destroy();
	}.delay(2000 + timeout);
};
var dwProgressBar = new Class(
{

	// implements
	Implements : [ Options ],

	// options
	options : {
		container : $$('body')[0],
		boxID : '',
		boxclass : '',
		percentageID : '',
		percclass : '',
		displayID : '',
		startPercentage : 0,
		displayText : false,
		speed : 10
	},

	// initialization
	initialize : function(options) {
		// set options
		this.setOptions(options);
		// create elements
		this.createElements();
	},

	// creates the box and percentage elements
	createElements : function() {
		var box = new Element('div', {
			id : this.options.boxID,
			'class' : this.options.boxclass
		});
		var perc = new Element('div', {
			id : this.options.percentageID,
			'class' : this.options.percclass,
			'style' : 'width:0px;'
		});
		perc.inject(box);
		box.inject(this.options.container);
		if (this.options.displayText) {
			var text = new Element('div', {
				id : this.options.displayID
			});
			text.inject(this.options.container);
		}
		this.set(this.options.startPercentage);
	},

	// calculates width in pixels from percentage
	calculate : function(percentage) {
		return ($(this.options.boxID).getStyle('width').replace('px',
				'') * (percentage / 100)).toInt();
	},

	// animates the change in percentage
	animate : function(to) {
		$(this.options.percentageID).set('morph', {
			duration : this.options.speed,
			link : 'cancel'
		}).morph({
			width : this.calculate(to.toInt())
		});
		if (this.options.displayText) {
			$(this.options.displayID).set('text', to.toInt() + '%');
		}
	},

	// sets the percentage from its current state to desired
	// percentage
	set : function(to) {
		this.animate(to);
	}

});