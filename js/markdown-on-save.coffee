do ($ = jQuery) ->
	app = window.markdownOnSaveApp =
		on: ->
			$('body').addClass 'cws-markdown'
			@html.click()
			@checkbox.attr 'checked', yes
			@buttonOn.show()
			a.hide() for a in [ @buttonOff, @html, @visual, @htmlButtons ]
		off: ->
			$('body').removeClass 'cws-markdown'
			@checkbox.attr 'checked', no
			@buttonOn.hide()
			a.show() for a in [ @buttonOff, @html, @visual, @htmlButtons ]
		delay: (ms, f) -> setTimeout f, ms
		start: ->
			context    = $ '#cws-markdown'
			context.detach().insertBefore('#submitdiv h2 span, #submitdiv h3 span').show()
			@buttonOn  = $ 'img.markdown-on',  context
			@buttonOff = $ 'img.markdown-off', context
			@checkbox  = $ '#cws_using_markdown'
			@html      = $ '#content-html'
			@visual    = $ '#content-tmce'
			@htmlButtonsString = ('#qt_content_' + a for a in [
				'strong'
				'em'
				'link'
				'block'
				'del'
				'ins'
				'img'
				'ul'
				'ol'
				'li'
				'code'
				'close'
			]).join ', '
			@htmlButtons = $ @htmlButtonsString
			@events()
			@setFromCheckbox()
		setFromCheckbox: ->
			if app.checkbox.is ':checked' then app.on() else app.off()
		events: ->
			$([@buttonOn, @buttonOff]).each -> $(@).click (e) ->
				e.stopPropagation() # Keep metabox from toggling
				app.checkbox.click()
			@checkbox.change @setFromCheckbox
	# Kind of hacky, but this allows for Quicktags to be added to the DOM first
	$ -> app.delay 0, -> app.start()
