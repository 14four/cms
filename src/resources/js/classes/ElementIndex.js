/**
 * Element index class
 */
Craft.ElementIndex = Garnish.Base.extend({

	elementType: null,
	state: null,
	stateStorageId: null,
	searchTimeout: null,

	$container: null,
	$scroller: null,
	$mainSpinner: null,
	$loadingMoreSpinner: null,
	$sidebar: null,
	$sources: null,
	$sourceToggles: null,
	$search: null,
	$elements: null,
	$tbody: null,

	init: function(elementType, $container, settings)
	{
		this.elementType = elementType;
		this.$container = $container;
		this.setSettings(settings, Craft.ElementIndex.defaults);

        // Set the state object
        this.state = {};

        if (typeof Storage !== 'undefined' && this.settings.id)
        {
        	this.stateStorageId = 'Craft.ElementIndex.'+this.settings.id;

        	if (typeof localStorage[this.stateStorageId] != 'undefined')
        	{
        		$.extend(this.state, JSON.parse(localStorage[this.stateStorageId]));
        	}
        }

    	// Find the DOM elements
    	this.$mainSpinner = this.$container.find('.toolbar:first .spinner:first');
    	this.$loadingMoreSpinner = this.$container.find('.spinner.loadingmore')
    	this.$sidebar = this.$container.find('.sidebar:first');
    	this.$sources = this.$sidebar.find('nav a');
    	this.$sourceToggles = this.$sidebar.find('.toggle');
    	this.$search = this.$container.find('.search:first input:first');
    	this.$elements = this.$container.find('.elements:first');

    	if (this.settings.mode == 'index')
    	{
    		this.$scroller = Garnish.$win;
    	}
    	else
    	{
    		this.$scroller = this.$container.find('.main');
    	}

    	// Select the initial source
    	var source = this.getState('source');

    	if (source)
    	{
    		var $source = this.getSourceByKey(source);

    		if ($source)
    		{
    			// Expand any parent sources
    			var $parentSources = $source.parentsUntil('.sidebar', 'li');
    			$parentSources.not(':first').addClass('expanded');
    		}
    	}

    	if (!source || !$source)
    	{
    		// Select the first source by default
    		var $source = this.$sources.first();
    	}

    	// Select it and load up the elements!
    	this.selectSource($source);

    	// Add some listeners
    	this.addListener(this.$sourceToggles, 'click', function(ev)
		{
			$(ev.currentTarget).parent().toggleClass('expanded');
		});

    	this.addListener(this.$sources, 'click', function(ev)
		{
			this.selectSource($(ev.currentTarget));
		});

    	this.addListener(this.$search, 'textchange', $.proxy(function()
    	{
    		if (this.searchTimeout)
    		{
    			clearTimeout(this.searchTimeout);
    		}

    		this.searchTimeout = setTimeout($.proxy(this, 'updateElements'), 500);
    	}, this));
	},

	getState: function(key)
	{
		if (typeof this.state[key] != 'undefined')
		{
			return this.state[key];
		}
		else
		{
			return null;
		}
	},

	setState: function(key, value)
	{
		if (typeof key == 'object')
		{
			$.extend(this.state, key);
		}
		else
		{
			this.state[key] = value;
		}

		if (this.stateStorageId)
		{
		    localStorage[this.stateStorageId] = JSON.stringify(this.state);
		}
	},

	getControllerData: function()
	{
		return {
			elementType:        this.elementType,
			mode:               this.settings.mode,
			disabledElementIds: this.settings.disabledElementIds,
			state:              this.state,
			search:             (this.$search ? this.$search.val() : null)
		};
	},

	updateElements: function()
	{
		this.$mainSpinner.removeClass('hidden');
		this.removeListener(this.$scroller, 'scroll');

		var data = this.getControllerData();

		Craft.postActionRequest('elements/getElements', data, $.proxy(function(response)
		{
			this.$mainSpinner.addClass('hidden');

			this.$elements.html(response.elementContainerHtml);

			var $headers = this.$elements.find('thead:first th');
			this.addListener($headers, 'click', 'onSortChange');

			this.$tbody = this.$elements.find('tbody:first');

			this.setNewElementDataHtml(response);
		}, this));
	},

	setNewElementDataHtml: function(response, append)
	{
		if (append)
		{
			this.$tbody.append(response.elementDataHtml);
		}
		else
		{
			this.$tbody.html(response.elementDataHtml);
		}

		$('head').append(response.headHtml);

		// More?
		if (response.more)
		{
			this.totalVisible = response.totalVisible;

			this.addListener(this.$scroller, 'scroll', function()
			{
				if (
					(this.$scroller[0] == Garnish.$win[0] && ( Garnish.$win.innerHeight() + Garnish.$bod.scrollTop() >= Garnish.$bod.height() )) ||
					(this.$scroller.prop('scrollHeight') - this.$scroller.scrollTop() == this.$scroller.outerHeight())
				)
				{
					this.$loadingMoreSpinner.removeClass('hidden');
					this.removeListener(this.$scroller, 'scroll');

					var data = this.getControllerData();
					data.offset = this.totalVisible;

					Craft.postActionRequest('elements/getElements', data, $.proxy(function(response)
					{
						this.$loadingMoreSpinner.addClass('hidden');

						this.setNewElementDataHtml(response, true);
					}, this));
				}
			});
		}

		this.settings.onUpdateElements(append);
	},

	onSortChange: function(ev)
	{
		var $th = $(ev.currentTarget),
			attribute = $th.attr('data-attribute');

		if (this.getState('order') == attribute)
		{
			if (this.getState('sort') == 'asc')
			{
				this.setState('sort', 'desc');
			}
			else
			{
				this.setState('sort', 'asc');
			}
		}
		else
		{
			this.setState({
				order: attribute,
				sort: 'asc'
			});
		}

		this.updateElements();
	},

	getSourceByKey: function(key)
	{
		for (var i = 0; i < this.$sources.length; i++)
		{
			var $source = $(this.$sources[i]);

			if ($source.data('key') == key)
			{
				return $source;
			}
		}
	},

	selectSource: function($source)
	{
		if (this.$source)
		{
			this.$source.removeClass('sel');
		}

		this.$source = $source.addClass('sel');

		this.setState('source', this.$source.data('key'));
		this.updateElements();
	},

	rememberDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index == -1)
		{
			this.settings.disabledElementIds.push(elementId);
		}
	},

	forgetDisabledElementId: function(elementId)
	{
		var index = $.inArray(elementId, this.settings.disabledElementIds);

		if (index != -1)
		{
			this.settings.disabledElementIds.splice(index, 1);
		}
	},

	enableElements: function($elements)
	{
		$elements.removeClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.forgetDisabledElementId(elementId);
		}

		this.settings.onEnableElements($elements);
	},

	disableElements: function($elements)
	{
		$elements.removeClass('sel').addClass('disabled');

		for (var i = 0; i < $elements.length; i++)
		{
			var elementId = $($elements[i]).data('id');
			this.rememberDisabledElementId(elementId);
		}

		this.settings.onDisableElements($elements);
	},

	getElementById: function(elementId)
	{
		return this.$tbody.children('[data-id='+elementId+']:first');
	},

	enableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.enableElements($element);
			}
			else
			{
				this.forgetDisabledElementId(elementId);
			}
		}
	},

	disableElementsById: function(elementIds)
	{
		elementIds = $.makeArray(elementIds);

		for (var i = 0; i < elementIds.length; i++)
		{
			var elementId = elementIds[i],
				$element = this.getElementById(elementId);

			if ($element.length)
			{
				this.disableElements($element);
			}
			else
			{
				this.rememberDisabledElementId(elementId);
			}
		}
	}
},
{
	defaults: {
		mode: 'index',
		id: null,
		disabledElementIds: null,
		onUpdateElements: $.noop,
		onEnableElements: $.noop,
		onDisableElements: $.noop
	}
});
