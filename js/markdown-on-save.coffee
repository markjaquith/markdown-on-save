do ($ = jQuery, window) ->
	app = window.markdownOnSaveApp =
		on: ->
			@checkbox.attr 'checked','checked'
			@buttonOn.hide()
			@buttonOff.show()
		off: ->
			@checkbox.removeAttr 'checked'
			@buttonOff.hide()
			@buttonOn.show()
		start: ->
			context    = $ '#cws-markdown'
			context.detach().insertBefore '#submitdiv h3 span'
			@buttonOn  = $ 'img.markdown-on',  context
			@buttonOff = $ 'img.markdown-off', context
			@checkbox  = $ '#cws_using_markdown'
			@events()
		events: ->
			@buttonOn.click (e) ->
				e.stopPropagation() # Keep metabox from toggling
				app.on()
			@buttonOff.click (e) ->
				e.stopPropagation() # Keep metabox from toggling
				app.off()
			@checkbox.change ->
				if $(@).attr 'checked' then app.on() else app.off()
	$ -> app.start()
