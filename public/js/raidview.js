window.addEvent('domready',function()
{
	var Slider = new Class({

		Implements: [Events, Options],

		Binds: ['clickedElement', 'draggedKnob', 'scrolledElement'],

		options: {/*
			onTick: function(intPosition){},
			onChange: function(intStep){},
			onComplete: function(strStep){},*/
			onTick: function(position){
				this.setKnobPosition(position);
			},
			initialStep: 0,
			snap: false,
			offset: 0,
			range: false,
			wheel: false,
			steps: 100,
			mode: 'horizontal'
		},

		initialize: function(element, knob, options){
			this.setOptions(options);
			options = this.options;
			this.element = document.id(element);
			knob = this.knob = document.id(knob);
			this.previousChange = this.previousEnd = this.step = -1;

			var limit = {},
				modifiers = {x: false, y: false};

			switch (options.mode){
				case 'vertical':
					this.axis = 'y';
					this.property = 'top';
					this.offset = 'offsetHeight';
					break;
				case 'horizontal':
					this.axis = 'x';
					this.property = 'left';
					this.offset = 'offsetWidth';
			}

			this.setSliderDimensions();
			this.setRange(options.range);

			if (knob.getStyle('position') == 'static') knob.setStyle('position', 'relative');
			knob.setStyle(this.property, -options.offset);
			modifiers[this.axis] = this.property;
			limit[this.axis] = [-options.offset, this.full - options.offset];

			var dragOptions = {
				snap: 0,
				limit: limit,
				modifiers: modifiers,
				onDrag: this.draggedKnob,
				onStart: this.draggedKnob,
				onBeforeStart: (function(){
					this.isDragging = true;
				}).bind(this),
				onCancel: function(){
					this.isDragging = false;
				}.bind(this),
				onComplete: function(){
					this.isDragging = false;
					this.draggedKnob();
					this.end();
				}.bind(this)
			};
			if (options.snap) this.setSnap(dragOptions);

			this.drag = new Drag(knob, dragOptions);
			this.attach();
			if (options.initialStep != null) this.set(options.initialStep);
		},

		attach: function(){
			this.element.addEvent('mousedown', this.clickedElement);
			if (this.options.wheel) this.element.addEvent('mousewheel', this.scrolledElement);
			this.drag.attach();
			return this;
		},

		detach: function(){
			this.element.removeEvent('mousedown', this.clickedElement)
				.removeEvent('mousewheel', this.scrolledElement);
			this.drag.detach();
			return this;
		},

		autosize: function(){
			this.setSliderDimensions()
				.setKnobPosition(this.toPosition(this.step));
			this.drag.options.limit[this.axis] = [-this.options.offset, this.full - this.options.offset];
			if (this.options.snap) this.setSnap();
			return this;
		},

		setSnap: function(options){
			if (!options) options = this.drag.options;
			options.grid = Math.ceil(this.stepWidth);
			options.limit[this.axis][1] = this.full;
			return this;
		},

		setKnobPosition: function(position){
			if (this.options.snap) position = this.toPosition(this.step);
			this.knob.setStyle(this.property, position);
			return this;
		},

		setSliderDimensions: function(){
			this.full = this.element.measure(function(){
				this.half = this.knob[this.offset] / 2;
				return this.element[this.offset] - this.knob[this.offset] + (this.options.offset * 2);
			}.bind(this));
			return this;
		},

		set: function(step){
			if (!((this.range > 0) ^ (step < this.min))) step = this.min;
			if (!((this.range > 0) ^ (step > this.max))) step = this.max;

			this.step = Math.round(step);
			return this.checkStep()
				.fireEvent('tick', this.toPosition(this.step))
				.end();
		},

		setRange: function(range, pos){
			this.min = Array.pick([range[0], 0]);
			this.max = Array.pick([range[1], this.options.steps]);
			this.range = this.max - this.min;
			this.steps = this.options.steps || this.full;
			this.stepSize = Math.abs(this.range) / this.steps;
			this.stepWidth = this.stepSize * this.full / Math.abs(this.range);
			if (range) this.set(Array.pick([pos, this.step]).floor(this.min).max(this.max));
			return this;
		},

		clickedElement: function(event){
			if (this.isDragging || event.target == this.knob) return;

			var dir = this.range < 0 ? -1 : 1,
				position = event.page[this.axis] - this.element.getPosition()[this.axis] - this.half;

			position = position.limit(-this.options.offset, this.full - this.options.offset);

			this.step = Math.round(this.min + dir * this.toStep(position));

			this.checkStep()
				.fireEvent('tick', position)
				.end();
		},

		scrolledElement: function(event){
			var mode = (this.options.mode == 'horizontal') ? (event.wheel < 0) : (event.wheel > 0);
			this.set(this.step + (mode ? -1 : 1) * this.stepSize);
			event.stop();
		},

		draggedKnob: function(){
			var dir = this.range < 0 ? -1 : 1,
				position = this.drag.value.now[this.axis];

			position = position.limit(-this.options.offset, this.full -this.options.offset);

			this.step = Math.round(this.min + dir * this.toStep(position));
			this.checkStep();
		},

		checkStep: function(){
			var step = this.step;
			if (this.previousChange != step){
				this.previousChange = step;
				this.fireEvent('change', step);
			}
			return this;
		},

		end: function(){
			var step = this.step;
			if (this.previousEnd !== step){
				this.previousEnd = step;
				this.fireEvent('complete', step + '');
			}
			return this;
		},

		toStep: function(position){
			var step = (position + this.options.offset) * this.stepSize / this.full * this.steps;
			return this.options.steps ? Math.round(step -= step % this.stepSize) : step;
		},

		toPosition: function(step){
			return (this.full * Math.abs(this.min - step)) / (this.steps * this.stepSize) - this.options.offset;
		}

	});
	
	var media_events = {
	   loadstart: 2, progress: 2, suspend: 2, abort: 2,
	   error: 2, emptied: 2, stalled: 2, play: 2, pause: 2,
	   loadedmetadata: 2, loadeddata: 2, waiting: 2, playing: 2,
	   canplay: 2, canplaythrough: 2, seeking: 2, seeked: 2,
	   timeupdate: 2, ended: 2, ratechange: 2, durationchange: 2, volumechange: 2
	}
			
	Element.NativeEvents = $merge(Element.NativeEvents, media_events);

	var media_properties = [
	  'videoWidth', 'videoHeight', 'readyState', 'autobuffer',
	  'error', 'networkState', 'currentTime', 'duration', 'paused', 'seeking',
	  'ended', 'autoplay', 'loop',  'controls', 'volume', 'muted',
	  'startTime', 'buffered', 'defaultPlaybackRate', 'playbackRate', 'played', 'seekable' // these 6 properties currently don't work in firefox     
	];

	media_properties.each(function(prop){
	  Element.Properties.set(prop, {
	     set: function(value){
	        this[prop] = value;
	     },
	     get: function(){
	        return this[prop];
	     }
	  })
	});
	
	var playAll = function() {
		$$('video').each(function(item){
			item.play();
		});
	};

	var pauseAll = function() {
		$$('video').each(function(item){
			item.pause();
		});
	};

	var parseMinutes = function(seconds) {
		seconds = Math.round(seconds);
		minutes = Math.floor(seconds / 60);
		seconds = seconds % 60;
		if (seconds > 9) {
			return minutes + ':' + seconds;
		} else {
			return minutes + ':0' + seconds;
		}
	};

	var syncVids = function() {
		time = mainVid.get('currentTime');
		$$('video').set('currentTime', time);
	};

	var pwidth = Number.from($('slider_cont').getStyle('width'));
	$('slider_prog').setStyle('max-width',Number.from($('slider_cont').getStyle('width')));
	var swidth = pwidth - 20;
	
	$$('video.rv-mute').set('muted',true);
	
	$('vid_play').addEvent('click',function(){
		this.highlight('#555');
		if (mainVid.get('paused'))
			playAll();
		else
			pauseAll();
	});

	$('vid_mute').addEvent('click',function() {
		this.highlight('#555');
		muted = !muted;
		$$('video:not(.rv-mute)').set('muted', muted);
		$('vid_mute').toggleClass('vid_muted');
	});
	
	var activeVid = $$('#main_section video')[0];
	var mainVid = $$('#main_section video')[0];
	var muted = false;

	$$('video').addEvent('click',function() {
			if (this != activeVid) {
			this.inject($('main_section'));
			activeVid.inject($('sec_section'));
			playAll();
			syncVids();
			activeVid = this;
		}
	});

	var timeSlider = new Slider('slider_cont', 'slider', {
	    steps: 9000,
	    onTick: function(position) {
		  $('slider_prog').highlight('#555');
	      duration = mainVid.get('duration');
	      if (duration == 0 || !Number.from(duration) || mainVid.get('seeking')) return;
	      time = ( position / swidth * duration); // from position to time
	      $$('video').set('currentTime', time);
	      this.knob.setStyle('left', position);
	    }
	});

	mainVid.addEvents({
		timeupdate: function() {
			duration = this.get('duration');
			if (duration == 0 ||  this.get('seeking')) return;
			time = this.get('currentTime');
			position = ( time / duration * swidth );
			timeSlider.knob.setStyle('left', position);
			$('vid_time').set('html',parseMinutes(time) + ' / ' + parseMinutes(duration));
		},

		play: function() {
			$('vid_play').addClass('vid_pause');
		},

		pause: function() {
			$('vid_play').removeClass('vid_pause');
		},

		ended: function() {
			$('vid_play').removeClass('vid_pause');
		}
	});

	$$('video').addEvents({
		seeking: function() {
			pauseAll();
		},

		seeked: function() {
			playAll();
		},

		waiting: function() {
			this.fade(0.5);
			$('vid_play').addClass('vid_load');
		},

		playing: function() {
			this.fade(1);
			$('vid_play').removeClass('vid_load');
		},

		loadstart: function() {
			this.fade(0.5);
			$('vid_play').addClass('vid_load');
		},

		canplay: function() {
			this.fade(1);
			$('vid_play').removeClass('vid_load');
			$('vid_time').set('html',parseMinutes(0) + ' / ' + parseMinutes(mainVid.get('duration')));
		},

		progress: function() {
			if (this.get('buffered').length == 1) {
				sum = 0;
				$$('video').get('buffered').each(function(range) {
					sum += range.end(0);
				});
				duration = this.get('duration');
				avg = (sum/$$('video').length);
				position = (avg / duration * pwidth );
				if (position > Number.from($('slider_prog').getStyle('width'))) {
					$('slider_prog').setStyle('width',position);
				}
			}
		}
	});
});