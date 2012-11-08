(function($) {

var LoginForm = Blocks.Base.extend({

	$form: null,
	$loginNameInput: null,
	$loginFields: null,
	$passwordPaneItem: null,
	$passwordInput: null,
	$rememberMeCheckbox: null,
	$submitBtn: null,
	$spinner: null,
	$error: null,

	forgotPassword: false,
	loading: false,

	init: function()
	{
		this.$form = $('#login-form'),
		this.$loginNameInput = $('#loginName');
		this.$loginFields = $('#login-fields');
		this.$passwordPaneItem = this.$loginFields.children();
		this.$passwordInput = $('#password');
		this.$submitBtn = $('#submit');
		this.$spinner = $('#spinner');
		this.$rememberMeCheckbox = $('#rememberMe');

		this.addListener(this.$loginNameInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$passwordInput, 'keypress,keyup,change,blur', 'onInputChange');
		this.addListener(this.$submitBtn, 'activate', 'onSubmit');
		this.addListener(this.$form, 'submit', 'onSubmit');
	},

	validate: function()
	{
		if (this.$loginNameInput.val() && (this.forgotPassword || this.$passwordInput.val().length >= 6))
		{
			this.$submitBtn.enable();
			return true;
		}

		this.$submitBtn.disable();
		return false;
	},

	onInputChange: function()
	{
		this.validate();
	},

	onSubmit: function(event)
	{
		// Prevent full HTTP submits
		event.preventDefault();

		if (!this.validate())
			return;

		this.$submitBtn.addClass('active');
		this.$spinner.removeClass('hidden');
		this.loading = true;

		if (this.$error)
			this.$error.remove();

		if (this.forgotPassword)
			this.submitForgotPassword();
		else
			this.submitLogin();
	},

	submitForgotPassword: function()
	{
		var data = {
			loginName: this.$loginNameInput.val()
		};

		Blocks.postActionRequest('account/forgotPassword', data, $.proxy(function(response) {
			if (response.success)
			{
				new MessageSentModal();
			}
			else
			{
				this.showError(response.error);
			}

			this.onSubmitResponse();
		}, this));
	},

	submitLogin: function()
	{
		var data = {
			loginName: this.$loginNameInput.val(),
			password: this.$passwordInput.val(),
			rememberMe: (this.$rememberMeCheckbox.attr('checked') ? 'y' : '')
		};

		Blocks.postActionRequest('account/login', data, $.proxy(function(response) {
			if (response.success)
			{
				window.location = response.redirectUrl;
			}
			else
			{
				Blocks.shake(this.$form);
				this.onSubmitResponse();

				// Add the error message
				this.showError(response.error);

				var $forgotPasswordLink = this.$error.find('a');
				if ($forgotPasswordLink.length)
					this.addListener($forgotPasswordLink, 'mousedown', 'onForgetPassword');
			}
		}, this));

		return false;
	},

	onSubmitResponse: function()
	{
		this.$submitBtn.removeClass('active');
		this.$spinner.addClass('hidden');
		this.loading = false;
	},

	showError: function(error)
	{
		if (!error)
			error = Blocks.t('An unknown error occurred.');

		this.$error = $('<p class="error" style="display:none">'+error+'</p>').appendTo(this.$form);
		this.$error.fadeIn();
	},

	onForgetPassword: function(event)
	{
		event.preventDefault();
		this.$loginNameInput.focus();

		this.$error.remove();

		var formTopMargin = parseInt(this.$form.css('margin-top')),
			loginFieldsHeight = this.$loginFields.height(),
			newFormTopMargin = formTopMargin + Math.round(loginFieldsHeight/2);

		this.$form.animate({marginTop: newFormTopMargin}, 'fast');
		this.$loginFields.animate({height: 0}, 'fast');

		this.$submitBtn.find('span').html(Blocks.t('Reset Password'));
		this.$submitBtn.enable();
		this.$submitBtn.removeAttr('data-icon');

		this.forgotPassword = true;
		this.validate();
	}
});


var MessageSentModal = Blocks.ui.Modal.extend({

	init: function()
	{
		var $container = $('<div class="pane email-sent">'+Blocks.t('We’ve sent you an email with instructions to reset your password.')+'</div>')
			.appendTo(Blocks.$body);

		this.base($container);
	},

	hide: function()
	{
	}

});


var loginForm = new LoginForm();

})(jQuery);
